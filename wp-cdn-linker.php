<?php
/*
Plugin Name: CDN Speed Cache
Plugin URI: http://speedcache.arcostream.com
Description: The one-click speed boost to your WordPress site.  Decreases load on your server, reduces load time of multimedia uploaded to your blog and improves serving speed by automatically setting up and configuring the CDN for you.
Version: 2.0.6
*/

/**
 * URL of the CDN Signup Automator, w/o trailing slash.
 *
 * Because someone could spy on the customer knowing the token,
 * the connection must be encrypted ("https://").
 */
$arcostream_automator = 'http://wpcdn.arcostream.com';

if ( @include_once('cdn-linker-base.php') ) {
	add_action('template_redirect', 'do_ossdl_off_ob_start');
}

include_once('cdn-linker-upstream.php');

/**
 * Regulates how often the plugin's cronjob is run.
 */
function ossdl_off_reschedule_cronjob($account_status) {
	if (wp_next_scheduled('ossdl_off_periodic_checks')) {
		wp_clear_scheduled_hook('ossdl_off_periodic_checks');
	}

	if ($account_status == 'pending') {
		// although 'hourly' is set, it is actually every 15 minutes
		wp_schedule_event(time() + 15*60, 'hourly', 'ossdl_off_periodic_checks');
	} else if ($account_status != 'unknown') { // 'ok' or anything other (e.g. 'suspended')
		wp_schedule_event(time() + 12*60*60, 'twicedaily', 'ossdl_off_periodic_checks');
	}
}

/**
 * Responsible for the auto-update of this plugin's settings.
 *
 * This is called whenever account status has to be checked.
 */
function ossdl_off_update_data_from_upstream() {
	global $arcostream_automator;
	$data = new TokenData(get_option('arcostream_token'), $arcostream_automator);
	if ($data->exists) {
		if (get_option('arcostream_account_status') != $data->status_account) {
			ossdl_off_reschedule_cronjob($data->status_account);
		}
		update_option('ossdl_off_cdn_url', $data->cdn_url);
		update_option('arcostream_account_status', $data->status_account);
	}
	return $data;
}

/**
 * Cronjob, will be run periodically by WP.
 *
 * Reponsible for stopping the loading of static files from CDN
 * when the account got suspended or expired.
 */
function ossdl_off_cronjob() {
	ossdl_off_update_data_from_upstream();
	if (get_option('arcostream_subscribe_fragment')) {
		delete_option('arcostream_subscribe_fragment');
	}
}

/********** WordPress Administrative ********/

function ossdl_off_activate() {
	add_option('ossdl_off_cdn_url', get_option('siteurl'));
	add_option('ossdl_off_include_dirs', 'wp-content,wp-includes');
	add_option('ossdl_off_exclude', '.php');
	add_option('ossdl_off_rootrelative', '');
	add_option('arcostream_account_status', 'unknown');
	if (!get_option('arcostream_token')) {
		add_option('arcostream_token', generate_random_token());
	} else {
		ossdl_off_update_data_from_upstream();
	}
	add_action('ossdl_off_periodic_checks', 'ossdl_off_cronjob');
	// issueing that here will have the cronjob started at least once
	wp_schedule_event(time() + 12*60*60, 'twicedaily', 'ossdl_off_periodic_checks');
}
register_activation_hook( __FILE__, 'ossdl_off_activate');

function ossdl_off_deactivate() {
	delete_option('ossdl_off_cdn_url');
	delete_option('ossdl_off_include_dirs');
	delete_option('ossdl_off_exclude');
	delete_option('ossdl_off_rootrelative');
	if (get_option('arcostream_account_status') == 'unknown') {
		delete_option('arcostream_token');
	}
	delete_option('arcostream_account_status');
	if (get_option('arcostream_subscribe_fragment')) {
		delete_option('arcostream_subscribe_fragment');
	}
	if (wp_next_scheduled('ossdl_off_periodic_checks')) {
		wp_clear_scheduled_hook('ossdl_off_periodic_checks');
	}
	remove_action('ossdl_off_periodic_checks', 'ossdl_off_cronjob');
}
register_deactivation_hook( __FILE__, 'ossdl_off_deactivate');

/********** WordPress Interface ********/
add_action('admin_menu', 'ossdl_off_menu');
add_action('admin_head', 'admin_register_head');

function ossdl_off_menu() {
	add_options_page('Speed Cache', 'Speed Cache', 8, __FILE__, 'ossdl_off_options');
}

function ossdl_off_get_basedir() {
	return get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__));
}

function admin_register_head() {
	$css_url = ossdl_off_get_basedir() . '/backend.css';
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$css_url\" />\n";
}

function ossdl_off_class_for_status($status) {
	if (is_null($status)) {
		return 'unknown';
	} else if ($status == true || $status == 'ok') {
		return 'success';
	} else {
		return 'failed';
	}
}

function ossdl_off_options() {
	// handling of the 'advanced settings' input
	if ( isset($_POST['action']) ) switch ($_POST['action']) {
	case 'advanced':
		update_option('ossdl_off_include_dirs', $_POST['ossdl_off_include_dirs'] == '' ? 'wp-content,wp-includes' : $_POST['ossdl_off_include_dirs']);
		update_option('ossdl_off_exclude', $_POST['ossdl_off_exclude']);
		update_option('ossdl_off_rootrelative', !!$_POST['ossdl_off_rootrelative']);
		break;
	case 'clear token':
		update_option('arcostream_token', generate_random_token());
		update_option('arcostream_account_status', 'unknown');
		if (get_option('arcostream_subscribe_fragment')) { delete_option('arcostream_subscribe_fragment'); }
		break;
	case 'new token':
		if (preg_match('/[\d\w]{4,6}\-[\d\w]{4}\-[\d\w]{4}\-[\d\w]{4,4}/', $_POST['arcostream_token'])) {
			echo('You have changed your token from <code>'.get_option('arcostream_token').'</code> to <code>'.$_POST['arcostream_token'].'</code>.');
			if (get_option('arcostream_subscribe_fragment')) { delete_option('arcostream_subscribe_fragment'); }
			update_option('arcostream_token', $_POST['arcostream_token']);
		} else {
			echo('The token you have entered is malformed. The old one <code>'.get_option('arcostream_token').'</code> has not been changed and will stay in effect.');
		}
		break;
	}

	$token_data = ossdl_off_update_data_from_upstream();
	global $arcostream_automator;
	if (get_option('arcostream_account_status') != 'ok'
	    && !$token_data->exists && !get_option('arcostream_subscribe_fragment')) {
		$signup_fragment_url = $arcostream_automator.'/plan/suggested/wp-button?token='.get_option('arcostream_token')
					.'&siteurl='.get_option('siteurl');
		$fragment = get_from_remote($signup_fragment_url);
		if (!!$fragment && strstr($fragment, 'SITE/TOKEN MISMATCH')) {
			update_option('arcostream_token', generate_random_token());
			$fragment = get_from_remote($signup_fragment_url);
			$token_data = ossdl_off_update_data_from_upstream();
		}
		if (!!$fragment) {
			update_option('arcostream_subscribe_fragment', $fragment);
		}
	}
	?><div class="wrap">
		<h2>CDN Speed Cache</h2>

		<div id="step1">
			<table border="0"><tbody><tr>
		<?php if (get_option('arcostream_account_status') != 'ok' && !$token_data->exists) { ?>
			<?php if (get_option('arcostream_subscribe_fragment')) { ?>
				<td valign="top"><?php echo(get_option('arcostream_subscribe_fragment')); ?></td>
			<?php } else { ?>
				<td valign="top">
					Oops. Our servers are down for maintenance.<br />
					We currently cannot accept new subscriptions.<br />
					Many apologies. Please visit this page later.
				</td>
			<?php } ?>
			<td valign="middle">
				OR
			</td>
		<?php } ?>
			<td valign="top">
				<form method="post" action="">
				<?php if (get_option('arcostream_account_status') == 'ok' || $token_data->exists) { ?>
					<label for="arcostream_custid">Your site identifier:</label><br />
					<input type="text" name="arcostream_custid" id="arcostream_token" value="<?php echo($token_data->token); ?>" disabled="1" size="24" class="regular-text code" /><br />
					<input type="hidden" name="action" value="clear token" />
					<input type="submit" class="button-secondary" value="<?php _e('Clear and Unconfigure') ?>" />
				<?php } else { ?>
					<label for="arcostream_custid">Already a Subscriber?</label><br />
					<input type="text" name="arcostream_token" id="arcostream_token" value="your site identifier" size="24" class="regular-text code" /><br />
					<input type="hidden" name="action" value="new token" />
					<input type="reset" class="button-secondary" value="<?php _e('Clear') ?>" /> &mdash;
					<input type="submit" class="button-primary" value="<?php _e('Configure') ?>" />
				<?php } ?><br />
				</form>
			</td>
			</tr></tbody></table>
		</div>
		<div id="step2">
			<ol class="checks">
			<?php if (get_option('arcostream_account_status') != 'ok' && !$token_data->exists) { ?>
				<li id="prereq_account" class="failed">Your account has been created.</li>
				<li id="prereq_payment" class="unknown">We have received payment for the current period.</li>
				<li id="prereq_cdn" class="unknown">CDN is configured.</li>
				<li id="prereq_dns" class="unknown">DNS is configured.</li>
				<li id="prereq_status" class="unknown">Static data now served by CDN.  May take up to 45 minutes initially.</li>
			<?php } else if ($token_data->exists) { ?>
				<li id="prereq_account" class="<?php echo(ossdl_off_class_for_status($token_data->exists)); ?>">
					Your account status is <code><q><?php echo(get_option('arcostream_account_status')); ?></q></code>.
				</li>
				<li id="prereq_payment" class="<?php echo(ossdl_off_class_for_status($token_data->status_account)); ?>">
					The subscription is paid up to <?php echo(substr($token_data->paid_including, 0, 10).' '.$token_data->paid_timezone); ?>.
					<?php if ($token_data->last_period) { ?>Your subscription will expire after that.<?php } ?>
				</li>
				<?php if ($token_data->traffic_used) { ?>
				<li id="prereq_quota" class="<?php echo(ossdl_off_class_for_status($token_data->traffic_used)); ?>">
					You have used <?php echo($token_data->traffic_used); ?> MB of your plan.
					The latter limits you to <code><q><?php echo($token_data->traffic_limits); ?></q></code>.
				</li>
				<?php } ?>
				<li id="prereq_cdn" class="<?php echo(ossdl_off_class_for_status($token_data->status_cdn)); ?>">CDN is configured.</li>
				<li id="prereq_dns" class="<?php echo(ossdl_off_class_for_status($token_data->status_dns)); ?>">
					DNS is configured and your CDN available through <code><?php echo(str_replace('http://', '', $token_data->cdn_url)); ?></code>.
				</li>
				<li id="prereq_status" class="<?php
					echo(ossdl_off_class_for_status(get_option('arcostream_account_status') == 'ok' && $token_data->cdn_url));
					?>">Static data now served by CDN.  May take up to 45 minutes initially.</li>
			<?php } else /* no token_data->exists (for example, most likely upstream is down) */ { ?>
				<li id="prereq_account" class="unknown">
					Your account status is <code><q><?php echo(get_option('arcostream_account_status')); ?></q></code>.
					(Read from cache.)
				</li>
				<li id="prereq_status" class="<?php
					echo(ossdl_off_class_for_status(get_option('arcostream_account_status') == 'ok' && get_option('ossdl_off_cdn_url') ));
					?>">Static data now served by CDN.  May take up to 45 minutes initially.</li>
			<?php } ?>
			</ol>
		</div>

		<h3>Advanced Options</h3>
		<p><form method="post" action="">

		<table class="form-table"><tbod>
			<tr valign="top">
				<th scope="row"><label for="ossdl_off_rootrelative">rewrite root-relative refs</label></th>
				<td>
					<input type="checkbox" name="ossdl_off_rootrelative" <?php echo(!!get_option('ossdl_off_rootrelative') ? 'checked="1" ' : '') ?>value="true" class="regular-text code" />
					<span class="description">Check this if you want to have links like <code><em>/</em>wp-content/xyz.png</code> rewritten - i.e. without your blog's domain as prefix.</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ossdl_off_include_dirs">include dirs</label></th>
				<td>
					<input type="text" name="ossdl_off_include_dirs" value="<?php echo(get_option('ossdl_off_include_dirs')); ?>" size="64" class="regular-text code" />
					<span class="description">Directories to include in static file matching. Use a comma as the delimiter. Default is <code>wp-content, wp-includes</code>, which will be enforced if this field is left empty.</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="ossdl_off_exclude">exclude if substring</label></th>
				<td>
					<input type="text" name="ossdl_off_exclude" value="<?php echo(get_option('ossdl_off_exclude')); ?>" size="64" class="regular-text code" />
					<span class="description">Excludes something from being rewritten if one of the above strings is found in the match. Use a comma as the delimiter. E.g. <code>.php, .flv, .do</code>, always include <code>.php</code> (default).</span>
				</td>
			</tr>
		</tbody></table>
		<input type="hidden" name="action" value="advanced" />
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
		</form></p>
	</div><?php
}
