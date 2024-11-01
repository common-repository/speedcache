<?php
/**
 * Strategy for creating CDN URIs.
 */
interface ICdnForItemStrategy
{
	/**
	 * Gets the right CDN URL for the given item.
	 *
	 * By modifying or overwriting this method you could, for example,
	 * have images loaded from CDN 1 and videos from CDN 2.
	 *
	 * @param String $item will either be something like 'http://test.local/xyz/zdf.ext' or '/xyz/zdf.ext'
	 * @return String contains the CDN url - e.g. 'http://cdn.test.local'
	 */
	public function get_for(&$item);

}

class OneCdnForAllStrategy implements ICdnForItemStrategy
{
	/** String: URL of the CDN domain */
	var $cdn_url		= null;

	function __construct($cdn_url) {
		$this->cdn_url = $cdn_url;
	}

	public function get_for(&$item) {
		return $this->cdn_url;
	}

}

class MultipleCdnsDeterministic implements ICdnForItemStrategy
{
	/**
	 * String: URL with pattern %d% for the CDN domains.
	 * 'd' is how many variations exist.
	 */
	var $cdn_pattern	= null;
	/** Number of variations. Derived from the above variable. */
	var $variations		= 0;
	/** Fragment, to speed up replacings. */
	var $fragment		= null;

	function __construct($cdn_pattern) {
		preg_match('/%(\d)%/', $cdn_pattern, $m);
		$this->variations = max($m[1], 1);
		$this->cdn_pattern = $cdn_pattern;
		$this->fragment = $m[0];
	}

	public function get_for(&$item) {
		$n = ( hexdec(substr(md5($item), 0, 1)) % $this->variations ) + 1;
		return str_replace($this->fragment, $n, $this->cdn_pattern);
	}

}

/**
 * Gets an implementation of ICdnForItemStrategy.
 */
function ossdl_off_cdn_strategy_for($pattern) {
	if (preg_match('/%(\d)%/', $pattern)) {
		return new MultipleCdnsDeterministic($pattern);
	} else {
		return new OneCdnForAllStrategy($pattern);
	}
}

/**
 * Reperesents the CDN Linker's rewrite logic.
 *
 * 'rewrite' gets the raw HTML as input and returns the final result.
 * It finds all links and runs them through 'rewrite_singe', which prepends the CDN domain.
 *
 * 'CDNLinksRewriter' contains no WP related function calls and can thus be used in testing or in other software.
 */
class CDNLinksRewriter
{
	/** String: the blog's URL ( get_option('siteurl') ) */
	var $blog_url		= null;
	/** ICdnForItemStrategy: results in URL of a CDN domain */
	var $cdn_url		= null;
	/** String: directories to include in static file matching, comma-delimited list */
	var $include_dirs	= null;
	/** Array: strings which indicate that a given element should not be rewritten (i.e., ".php") */
	var $excludes		= array();
	/** Boolean: if true, modifies root-relative links */
	var $rootrelative	= false;

	/** Constructor. */
	function __construct($blog_url, ICdnForItemStrategy $cdn_url, $include_dirs, array $excludes, $root_relative) {
		$this->blog_url		= $blog_url;
		$this->cdn_url		= $cdn_url;
		$this->include_dirs	= $include_dirs;
		$this->excludes		= $excludes;
		$this->rootrelative	= $root_relative;
	}

	/**
	 * Determines whether to exclude a match.
	 *
	 * @param String $match URI to examine
	 * @return Boolean true if to exclude given match from rewriting
	 */
	protected function exclude_single(&$match) {
		foreach ($this->excludes as $badword) {
			if (stristr($match, $badword) != false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Rewriter of URLs, used as callback for rewriting in {@link ossdl_off_filter}.
	 *
	 * @param String $match An URI as candidate for rewriting
	 * @return String the unmodified URI if it is not to be rewritten, otherwise a modified one pointing to CDN
	 */
	protected function rewrite_single(&$match) {
		if ($this->exclude_single($match[0])) {
			return $match[0];
		} else {
			if (!$this->rootrelative || strstr($match[0], $this->blog_url)) {
				return str_replace($this->blog_url, $this->cdn_url->get_for($match[0]), $match[0]);
			} else { // obviously $this->rootrelative is true and we got a root-relative link - else that case won't happen
				return $this->cdn_url->get_for($match[0]) . $match[0];
			}
		}
	}

	/**
	 * Creates a regexp compatible pattern from the directories to be included in matching.
	 *
	 * @return String regexp pattern for those directories, or empty if none are given
	 */
	protected function include_dirs_to_pattern() {
		$input = explode(',', $this->include_dirs);
		if ($this->include_dirs == '' || count($input) < 1) {
			return 'wp\-content|wp\-includes';
		} else {
			return implode('|', array_map('quotemeta', array_map('trim', $input)));
		}
	}

	/**
	 * Output filter which runs the actual plugin logic.
	 *
	 * @param String $content the raw HTML of the page from Wordpress, meant to be returned to the requester but intercepted here
	 * @return String modified HTML with replaced links - will be served by the HTTP server to the requester
	 */
	public function rewrite(&$content) {
		$dirs = $this->include_dirs_to_pattern();
		$regex = '#(?<=[(\"\'])';
		$regex .= $this->rootrelative
			? ('(?:'.quotemeta($this->blog_url).')?')
			: quotemeta($this->blog_url);
		$regex .= '/(?:((?:'.$dirs.')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
		return preg_replace_callback($regex, array(&$this, 'rewrite_single'), $content);
	}

}

/**
 * The rewrite logic with calls to Wordpress.
 */
class CDNLinksRewriterWordpress extends CDNLinksRewriter
{
	/** Initializes all options calling Wordpress functions. */
	function __construct() {
		$excl_tmp = trim(get_option('ossdl_off_exclude'));
		$excludes = array_map('trim', explode(',', $excl_tmp));

		parent::__construct(
			get_option('siteurl'),
			ossdl_off_cdn_strategy_for(trim(get_option('ossdl_off_cdn_url'))),
			trim(get_option('ossdl_off_include_dirs')),
			$excludes,
			!!trim(get_option('ossdl_off_rootrelative'))
		);
	}

	/**
	 * Registers the output buffer, if needed.
	 *
	 * This function is called by Wordpress if the plugin was enabled.
	 */
	public function register_as_output_buffer() {
		if ($this->blog_url != trim(get_option('ossdl_off_cdn_url'))) {
			ob_start(array(&$this, 'rewrite'));
		}
	}

}

/**
 * This function actually registers the rewriter.
 * It is called by Wordpress.
 */
function do_ossdl_off_ob_start() {
	if (get_option('arcostream_account_status') == 'ok') {
		$rewriter = new CDNLinksRewriterWordpress();
		$rewriter->register_as_output_buffer();
	}
}

/**
 * Generates a random, human-readable string.
 *
 * Use this to generate token.
 */
function random_string($random_string_length) {
	$characters = 'ABCDEFGHKMNPRSTUWXYZabcdefghkmnoprstuwxyz023456789';
	$string = '';
	for ($i = 0; $i < $random_string_length; $i++) {
		$string .= $characters[rand(0, strlen($characters) - 1)];
	}
	return $string;
}
