=== CDN Speed Cache ===
Contributors:  wmark, arcostream
Info: See http://speedcache.arcostream.com for the latest source.
Tags: CDN,edgecast,accelerator,content,speed,cloud,cloudfiles,cloudfront,delivery,network,limelight,highwinds,performance,sideload,distribution,simplecdn,offload
Requires at least: 2.7
Tested up to: 4.0
Stable Tag:  2.0.6

The one-click speed boost to your WordPress site.  Decreases load on your web server, improves serving speed; configures the CDN for you.

== Description ==
CDN Speed Cache is a Wordpress plugin is designed to enable advanced level CDN caching services that are typically leveraged by medium to large businesses.  With this simple plugin you can enjoy site accelleration through cloud caching!

This plugin is based on the CDN-Linker plugin by Mark Kubacki.  This version of the plugin includes easypay through Paypal and a one-click setup for those who aren't interested in dealing with all of the fancy configuration that other plugins require.  This plugin uses the most well recognized settings to optimize the viewer experience using methods that have the lowest possible chance of interfering with other plugins.  You don't have to worry about it, we have it under control!

This plugin installs in 1 minute and takes effect in approximately 35 minutes because of the DNS propagation that needs to occur before caching can begin on the CDN.  When the plugin gives all green `OK` message, the CDN begins caching.  Approximately 15 minutes to one hour later the site will be fully accellerated assuming DNS propagates to all users within that time frame.

This plugin currently features the Edgecast CDN and features this top-tier CDN's nodes all over the world, not just the United States as many of the other plugins do.  This plugin is ideal for anyone who has web visitors from Europe, North America, South America, Japan, China or Australia.  This plugin truely gives you global reach with your website!

Keep an eye out for other versions of this tool featuring other CDNs!

== Installation ==
1. Upload the plugin to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Under `Settings`, click on `Sign Up` or enter your existing site identifier.
4. For your convenience, the only payment method available is Paypal.  You agree to a monthly subscription that can be cancelled any time via Paypal.
5. After clicking the link to Paypal, enter the necessary information, agree to the monthly subscription and click `Submit`.
6. Define any additional directories you'd like cached on the plugin setup page.  This is an optional feature as the plugin takes care of nearly all items that need to be cached automatically.
7. Wait 40-45 minutes as DNS propagates for your CDN service's new CNAME.  After this propagates the plugin will automatically engage and the last green indicator will show in the plugin interface.
8. It will also take a bit of time for the CDN to cache your files from your origin, so immediately following the install for the next hour there may not be much of a performance benefit.  Once the CDN receives a copy of the cached items (it receives this through the first access from a user) watch your WordPress install begin to FLY!
9. DO NOT ENABLE SPEEDCACHE IF YOU ARE STILL DEVELOPING OR EXPERIMENTING WITH IMAGES OR CSS FILES.  If you replace files that are already cached it may take up to one hour for the new copy of the file to appear from the CDN.  To avoid this issue, PLEASE DEACTIVATE SPEED CACHE BEFORE YOU DEVELOP AND FOR THE DURATION OF YOUR DEVELOPMENT.  When you are finished with your work, re-activate the plugin and you will be good to go!

That's it! Everything else is done automatically, including the setup of which files get cached.

== Screenshots ==
1. Speedcache Plugin before configuration takes place in Wordpress 3.2.1
2. Speedcache Plugin after configuration takes place in Wordpress 3.2.1

== Frequently Asked Questions ==

= I want the $9.99 plan but the plugin defaults to $29.  What do I do? =

There's a link directly under the subscribe button that says, "See All Available Plans."  Sign up from that page.  It's on the Settings > Speed Cache page.

= Are there any reviews of this plugin online? =
Justin Germino writes about the plugin here:  http://www.dragonblogger.com/cdn-speed-cache-easiest-wordpress-cdn-setup/
Have you written a review?  Notify us and we'll link to it!

= I'm using the NextGEN Gallery and as soon as I enable Speed Cache, the Image Rotator breaks in my NextGEN Gallery install!  HELP!? =
This is a well documented problem, feel free to Google about it.  This is a known issue and is a cross domain policy problem with flash.

To fix this issue, add the filename `imagerotator.swf` to the field marked, `exclude if substring` one the settings page for the CDN Speed Cache plugin.

= I'm replacing image files and I see the old version appearing on the website still!  What gives? =
It's best to utilize Speed Cache in a production environment only.  Installation instructions let you know to use CDN site accelleration in a production environment.

If you have an emergency we can manually purge for you.  If you are developing on your website we recommend disabling the plugin until you are in production with your WordPress install.  If you have lingering issues with old versions of files showing up for long periods of time, please contact support@arcostream.com with the URL of the problem file and we will purge it manually from the CDN's cache.

We have also planned to add purge functionality to the next version of CDN Speed Cache.

= How does it work? =
After your blog pages have been rendered but before sending them to the visitor, it will rewrite links pointing to `wp-content` and `wp-includes` and any other directory define din the setup. That rewriting will simply replace your blog URL with a CDN's address that is generated for you and will pull the cached files from the CDN rather than your web server.  This reduces overall load on your web host and also speeds up the customer's web browsing experience considerably because there is less distance between your website and your viewer, no matter where THEY are  :)

= Do I need to find my own CDN? =
No. By signing up with us through the plugin our CDN will be configured for you automatically.  We have all of the top CDNs partnered with us including Akamai, Limelight, Highwinds, Edgecast, Mirror-Image and many others!  We set up a CNAME for you and make all the settings you need. Trust the experts.  This plugin is a true one-click install.

= Is it compatible to plugin XY? =
Yes, by design it is compatible with all plugins. It hooks into a PHP function ob_start (http://us2.php.net/manual/en/function.ob-start.php) and there does the string replacement. Therefore, no Wordpress function is altered, overwritten or modified in any way.

= What other plugins do you recommend? =
Now that you can offload all the files such as images, music or CSS, you should serve your blog posts as static files to decrease load on your server. We recommend SuperCache-Plus (http://murmatrons.armadillo.homeip.net/features/experimental-eaccelerator-wp-super-cache) as it will maintain, update and create that static files from dynamic content for you.  It's pretty nifty!

= I need support! =
Don't hesitate to contact us!  Currently Speed Cache is only eligible for email support, but if you require more let us know and we can accommodate you!  (http://speedcache.arcostream.com)  We are happy to grow with your needs as we are used to extremely large clients like ESPN, Sundance Channel, LionsGate, CBS Paramount, Music Choice, eBay, Intel, and Microsoft, among others.

= This plugin is too simple!  I'd rather use a different one with more options and configuratbility. =
We totally understand!  Some of us need all the bells and whistles we can get! ;)  We wanted to market this plugin to the newbies since there were already a couple of other really good CDN plugins without bundled service.  We of course recommend WP Super Cache as it simply creates static HTML files that allow CDNs to leverage easier than even W3 Total Cache!

= Does SpeedCache work with Wordpress 4.0? =
Absolutely!  Tested perfect.

Once you are signed up you can use our CDN services in any plugin you'd like so long as your subscription remains in tact in Paypal!  You can enter your assigned `.arcostream.com` CDN Caching prepend URL in to any other CDN plugin and use it, you do not have to run this plugin!

To purchase only the plan we recommend installing CDN Speed Cache, subscribing through Paypal, waiting for the plugin to show all green `OK` messages, and then switching out to your preferred plugin, but retaining the configuration data provided to you during the Speed Cache install.
