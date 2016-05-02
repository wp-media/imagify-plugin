=== Imagify Image Optimizer ===
Contributors: wp_media
Tags: compress image, images, performance, optimization, photos, upload, resize, gif, png, jpg, reduce image size, retina
Requires at least: 3.7.0
Tested up to: 4.5.1
Stable tag: 1.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Dramatically reduce image file sizes without losing quality, make your website load faster, boost your SEO and save money on your bandwidth.

== Description ==

Speed up your website with lighter images without losing quality.

Imagify is the most advanced image compression tool, you can now use this power directly in WordPress. 
After enabling it all your images including thumbnails and retina images from WP Retina x2 will be automatically optimized on the fly when you will add in into WordPress.

WooCommerce and NextGen Gallery compatible.
​
= What is Image Compression? =

Learn more about image compression, check that: <a href="https://imagify.io/images-compression">https://imagify.io/images-compression</a>

= Why use Imagify to optimize you images? =

You already have a lots of unoptimized images? Not a problem, you will love the Bulk Optimizer to optimize all your existing images in one click.  
​  
Imagify can directly resize your images, **you won't have to lose time anymore on resizing your images before uploading them**.  
​  
Three level of compression are available:  
​  
- Normal, a lossless compression algorithm. The image quality won't be altered at all.  
- Agressive, a lossy compression algorithm. Stronger compression with a tiny loss of quality most of the time this is not even noticeable at all.  
- Ultra, our strongest compression method using a lossy algorithm.  
​
With the backup option, you can change your mind whenever you want by restoring your images to their original version or optimize them to another compression level.

= What our users think of Imagify? =
  
> "Imagify is an awesome tool that is powerful & easy to use. It's fast, rivals and surpasses other established plugins/software. Awesome!" — [Simon Harper](https://twitter.com/SRHDesign/status/663758140505235456)
>
> "If you want to "squeeze" your images as much as possible and "trim out" your website on the highest professional level... Imagify" — [Ivica Delic](https://twitter.com/Free_LanceTools/status/685503950909476865)
>
> "Clearly Imagify is the most awesome WordPress plugin to compress images on your website! A must try" — [Eric Walter](https://twitter.com/EricWaltR/status/679053496382038016)
>

= Does Imagify is Free? =

You can optimize for free 25MB of images (about 250 images) every month and you will receive a 25MB bonus upon registration.

Need more? Have a look at our plans: <a href="https://imagify.io/pricing">https://imagify.io/pricing</a>
​
= What's next? =

Have a look at our upcoming features by following our development roadmap: <a href="https://trello.com/b/3Q8ZnSN6/imagify-roadmap">https://trello.com/b/3Q8ZnSN6/imagify-roadmap</a>

= Who we are? =
​
We are <a href="http://wp-media.me">WP Media</a>, the startup behind WP Rocket the best caching plugin for WordPress. 

Our mission is to improve the web, we are making it faster with <a href="http://wp-rocket.me/">WP Rocket</a> we want to make it lighter with Imagify.

= Get in touch! =

* Website: <a href="https://imagify.io">Imagify.io</a>
* Contact Us: <a href="https://imagify.io/contact">https://imagify.io/contact</a>
* Twitter: <a href="https://twitter.com/imagify">https://twitter.com/imagify</a>

= Related Plugins =
* <a href="http://wp-rocket.me">WP Rocket</a>: Best caching plugin to speed-up your WordPress website.
* <a href="https://wordpress.org/plugins/rocket-lazy-load/">Rocket Lazy Load</a>: Best Lazy Load script to reduce the number of HTTP requests and improves the websites loading time.

== Installation ==

= WordPress Admin Method =
1. Go to you administration area in WordPress `Plugins > Add`
2. Look for `Imagify` (use search form)
3. Click on Install and activate the plugin
4. Optional: find the settings page through `Settings > Imagify`

= FTP Method =
1. Upload the complete `imagify` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optional: find the settings page through `Settings > Imagify`

== Frequently Asked Questions ==

= Which formats can be optimized? =

Imagify can optimize jpg, png and gif (whether animated or not) formats.

= Can I use the plugin with a free account? =

Absolutely. You are limited to a quota of 25 MB of images per month with a free account. Once this quota is reached, you cannot optimize new images until your quota is renewed or you purchase credits.

= On how many websites can I use the plugin? =

You can use the plugin on as many sites as you wish. The only limit is the optimization quota of your account.

= I used Kraken, Optimus, EWWW or WP Smush, will Imagify further optimize my images? =

Absolutely. Most of the time, Imagify will still be able to optimize your images even if you have already compressed them with another tool.

= What is the difference between the Normal, Aggressive and Ultra compression levels? =

Normal compression is a "lossless" optimization. This means there is no loss of image quality. Aggressive and Ultra compression are more powerful, so the picture quality will be somewhat reduced. The weight of the image will be much less.

= Is the EXIF data of images removes? =

By default EXIF data is removed. It is however possible to keep it by enabling the option.

= Will the original images be deleted? =

No. Imagify automatically replaces the images with an optimized image. The backup option allows you to keep the original images and restore them with one click.

= Is it possible to re-optimize images with a different level? =

Yes. By activating the backup option in the plugin, you can re-optimize each image with a different compression level.

= If I use Imagify, do I need to continue optimizing and resizinf my images with Photoshop? =

Do not waste your time resizing and optimizing your images in Photoshop. Imagify takes care of everything!

= What happens when the plugin is disabled? =

When the plugin is disabled, your existing images remain optimized. Backups of the original images are still available if you have enabled the images backup option.

== Screenshots ==

1. Bulk Optimization

2. Settings Page

3. Media Page

== Changelog ==

= 1.5.3 =
* Regression Fix
 * Display the Original Filesize in "View Details" section

= 1.5.2.1 =
* Bug Fix
 * Fix JS error: Uncaught ReferenceError: imagify is not defined in /assets/options.min.js
 * Don't show "Optimize" button during optimizing process in "Edit Media" screen
  
= 1.5.1 =
* Bug Fix
 * Thumbnail sizes in settings page aren't reset anymore on plugin update
 * Fix PHP Warning: Cannot unset offset in a non-array variable in /inc/functions/admin-stats.php on line 23
 * Fix PHP Warning: Invalid argument supplied for foreach() in /inc/functions/admin-stats.php on line 233
 
= 1.5 =
* NEW Features:
 * NextGen Gallery compatibility - Optimize all your images uploaded with NextGen Gallery
 * Asynchronous Optimization - No more latency when you upload new images, Imagify will optimize them in background!
* Interface:
 * Bulk Optimization: Improvements for a better experience

= 1.4.7 =
* Bug Fix
 * Fix issue between Bulk Optimization & WP Engine. The query to get unoptimized images is limited to 2500 images to be able to use the Bulk Optimization on this hosting.
 * Fix SSL certificate problem: unable to get local issuer certificate

= 1.4.6 =
* Bulk Optimization
 * Fix the "All your images have been optimized by Imagify" issue when images still need to be optimized. This issue occurred only since 1.4.5 for some users. Sorry for the inconvenience!

= 1.4.5 =
* Interface
 * Bulk Optimization: optimize all SQL queries and improve by 65% the process time \o/
* Misc 
 * Chart.js library updated
 * Media List JS notice removed

= 1.4.4 =
* Interface
 * Visual fix: CSS prefixed in notices to avoid class conflicts
 * Visual fix: improve Imagify Notices CSS to avoid issue with WP Engine CSS
 * Medias: new "Compare Original VS Optimized" action link in grid view mode
 * Settings: new sample images for visual comparison of compression levels (removes unused sample images)

= 1.4.3 =
* Interface
 * Visual fix: CSS prefixed in notices to avoid class conflicts
 * Medias: new "Compare Original VS Optimized" action link in list view
 * Medias: comparison are now available for image from 36Opx wide
 * Settings: new sample images for visual comparison of compression levels

= 1.4.2 =
* Translation
 * NEW: Add German translation

* Interface
 * NEW: You can define the IMAGIFY_HIDDEN_ACCOUNT constant in wp-config.php to hide all your Imagify account infos in the Admin Bar and Bulk Optimization

* Bug Fix
 * Fix PHP Notice: Undefined index original_size in /inc/functions/admin-stats.php on line 185
 * Fix PHP Notice: Undefined index optimized_size in /inc/functions/admin-stats.php on line 186
 
= 1.4.1 =
* Interface
 * Medias: better comparison for big portrait images
 * Medias: Don't display the "Compare Original VS Optimized" button for images without backup

* Bug Fix
 * WPML: Fix AJAX error caused by WPML to avoid issue during the API key validation process
 * Yoast: Remove JS error caused by Yoast SEO on the attachment edit screen to avoid issue with our "Compare Original VS Optimized"

= 1.4 =
* Interface
 * Medias: Click a button to open images comparison between Original and Optimized (available for big enought images)
* Improvement
 * Add async method to optimize resized images

= 1.3.6 =
* Improvement
 * Optimize attachments resized with the WordPress editor tool
 * Compatibility with the "Replace the file, use new file name and update all links" option from "Enable Media Replace" plugin
 * Add a notice message during the Bulk Optimization if the quota is consumed
* Interface
 * Better styles for compression details next to your images
* Bug Fix
 * No freeze anymore during the Bulk Optimization if an unknown error occurred with an image
 * Add a notice message if we can't get all unoptimized images during the Bulk Optimization process
 * Fix PHP Warning: set_time_limit(): Cannot set time limit in safe mode in ../inc/admin/ajax.php on line 137
 * Details about compressed images in modal media box are now closed by default
* Regression Fix 
 * Get all attachments with the message "You've consumed all your data" during the Bulk Optimization process to be able to optimize them
 
= 1.3.5.2 =
* Regression Fix
 * Check mark displayed better on certain settings pages

= 1.3.5 =
* Bug Fix
 * Check box display issue fixed on Imagify settings page: SVG Icons cleaning

= 1.3.4 =
* NEW: Add Italian translation

= 1.3.3 =
* Bug Fix
 * Fixed behavior in multisite networks where Imagify options would not get saved when the plugin wasn't network-activated, but only activated for specific sites within the network.
 
= 1.3.2 =
* NEW: Add Spanish translation
* Bug Fix
 * Avoid lack of performance in the WordPress administration if the Imagify's servers are down.
 
= 1.3.1 =
* Bug Fix
 * Remove a notice message which causes a lack of performance in the administration. (thanks Kevin Gauthier to warn us)
 
= 1.3 =
* NEW: Add GIF support
* NEW option: You can now decide to keep EXIF data on your images

= 1.2.4 =
* Bug Fix
 * Don't duplicate Imagify data in the attachment edit screen (wp-admin/post.php)

= 1.2.3 =
* Improvement
 * Use AJAX to display the quota in the admin bar to avoid a call to our API on each pages.
 
= 1.2.2 =
* Bug Fix
 * Bulk Optimization: Fix issue when the backup option isn't activated. The compression level applied was "Normal" instead the one saved in the settings.
 * Bulk Optimization: Don't try to re-optimize an image already optimized which has the same compression level than the one saved in the settings.
 
= 1.2.1 =
* Regression Fix
 * Fix the Bulk Optimization issue when you never optimized any images and avoid the message "All your images have been optimized by Imagify. Congratulations!".

= 1.2 =
* Interface
 * NEW compression level: Ultra
 * NEW options: You can now choose to display Admin Bar Imagify's menu, or not.
 * See the differences between Ultra, Agressive and Normal option inside Imagify Options page.
* Bug Fix
 * Admin Bar: Styles are now included in front-end too.
 * Admin Bar: Better styles in certain cases.
 * Deactivate a conflict plugin doesn't return a blank page anymore!
 * Display the right original image size after a resize (meta data)
* Regression Fix
 * Bulk Optimization: update in live the unconsumed credit during a bulk optimization.

= 1.1.6 =
* Interface
 * Quick access to your profile informations (quota) in Admin Bar > Imagify
 * More precise information about global size saved using Imagify (bulk optimization page)
 * When your bulk optimization is over, success message isn't inside the table anymore
 * To quit the bulk optimization processing you have to confirm your action
* Bug Fix
 * JS: `console` undefined on some IE browsers
 * PHP Warning: Illegal string offset 'sizes' in ../inc/functions/admin-stats.php  on line 180
 * Don't count GIF & SVG in the Imagify statistics

= 1.1.5 =
* Interface
 * Display a default preview to avoid issues with 404 images and a security restriction on SSL websites on the Bulk Optimization page
 * Don't count all exceeded images to avoid lack of speed on the Bulk Optimization page
* Bug Fix
 * Don't try to re-optimize images with an empty error message or with an already optimized message on the Bulk Optimization
 * Don't generate special chars in the password to avoid issue on the Imagify app log in

= 1.1.4 =
* Interface
 * Don't add the WP Rocket ads if this plugin is activated
* Bug Fix
 * Ignore thumbnails with infinite width like 9999 to avoid an issue with the "Resize larger images" option

= 1.1.3 =
* Bug Fix
 * Fix PHP Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when safe_mode is enabled or an open_basedir is set in ../inc/api/imagify.php on line 218 

= 1.1.2 =
* Regression Fix
 * Fix the "%undefined%" and the overview chart issues on the Bulk Optimization page
 * Fix PHP Warning: Illegal string offset 'sizes' in ../inc/classes/class-attachment.php on line 347
 * Fix PHP Notice: Uninitialized string offset: 0 in ../inc/classes/class-attachment.php on line 347
 * Fix PHP Warning: Illegal string offset 'file' in ../inc/classes/class-attachment.php on line 410
 
= 1.1.1 =
* Interface
 * Add a notice on the Bulk Optimization & Imagify Settings page when the monthly free quota is consumed
* Bug Fix
 * Fix issue on Chrome & Opera on the Bulk Optimization: images are optimized from the newest to the oldest.

= 1.1 =
* Interface
 * Add new option "Resize larger Images"
 * Bulk optimization: results table is not shrinkable to the infinite anymore (scrollable)
 * Better visual in options page
* Bug Fix
 * Check if an attachment exists to avoid an issue which is stopped the Bulk Optimization
 * Really Fix PHP Notice: Undefined offset: 1 in imagify/inc/functions/formatting.php on line 17
 * Double animation in Progress Bar

= 1.0.3 =
* Bug Fix
 * Fix PHP Notice: Undefined offset: 1 in ../inc/functions/formatting.php on line 16

= 1.0.2 =
* Interface
 * Add error descriptions on the Bulk Optimization results
 * Add a notice to switch to the list view in the media library page

= 1.0.1 =
* Interface
 * Add Intercom Live Chat on Imagify Settings and Bulk Optimization pages
 * Better user informations
* Bug Fix
 * PHP 5.2+ compatibility

= 1.0 =
* Initial release.