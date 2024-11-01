=== vjoon WordPress Adapter ===
Contributors: vjoondev
Tags: vjoon, adapter, vjoon seven, vjoon k4
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The vjoon WordPress Adapter is a universal WordPress Plug-in, which establishes the connection to your vjoon K4 system.

== Description ==

The vjoon WordPress Adapter hands content stories, including text, media and meta data that have been created in your vjoon K4 system to WordPress for further processing.
If requested by a vjoon K4 user, the adapter returns a URL that displays a preview of the content story in vjoon K4.

== Installation ==

Log in to your WordPress dashboard, navigate to the Plugins menu and click Add New. In the search field type “vjoon WP Adapter” and click Search Plugins. Click Install Now to install vjoon WP Adapter.

**Connecting to vjoon K4**
Once you have activated “vjoon WP Adapter” for the corresponding site, you have to connect your website with your vjoon K4 publications by providing authorization information within vjoon K4.

1. Go to "Settings > vjoon WP Adapter”.
1. Copy the values from the fields “API URL” and “Application Password” (for K4 versions < 13.0, use “API URL”, “API Key” and “API Secret”). You will need these values to define publication settings in vjoon K4. Refer to the vjoon K4 documentation about the integration with WordPress for details.

*Please Note: If you have generated new API URL, Application Password, API Secret or API Key, make sure to click Save Changes before leaving the vjoon WP Adapter settings dialog.*

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= Version 3.0.0 =

* Added Feature K4-14023 Added Support for start index and reversed order for numbered list
* Tested with WordPress 6.4.2
* change to min. Requirement PHP 8.2

= 2.0.1 =
* Fixed Bug UNI-328: vjoon WordPress Adapter now also works properly for sites hosted on wordpress.com.
* Tested with WordPress 6.0.2

= 2.0.0 =
* Tested with WordPress 6.0
* Usage of Application Password to support Multi-Factor Authentication
* Support of Multi-Site installation

= 1.2.0 =
* Tested with WordPress 5.9
* Minimum requirement changed to PHP 7.4

= 1.1.0 =
* Tested with WordPress 5.8

= 1.0.9 =
* Fixed Bug UNI-249: Alignments of images are now displayed correctly even if the Gutenberg Editor is selected.

= 1.0.8 =
* Tested with WordPress 5.7

= 1.0.7 =
* Fixed Bugs UNI-235, UNI-242: HTML entities added as text are now correctly shown as characters, and are not interpreted as formatting anymore.

= 1.0.6 =
* Tested with WordPress 5.6

= 1.0.5 =
* Tested with WordPress 5.5
* Feature auto-update is supported

= 1.0.4 =
* Tested with WordPress 5.4.1

= 1.0.3 =
* Fixed Bug UNI-185: Menu links to articles of type page are now preserved when an update is published.

= 1.0.2 =
* Tested with WordPress 5.4

= 1.0.1 =
* Tested with WordPress 5.3.3

= 1.0.0 =
* Initial version
