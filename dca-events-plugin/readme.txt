=== DCA Events Plugin ===
Contributors: NMDCA - Doug Patinka, NMHU SSD - Anita Martin, Rianne Trujillo
Donate link: N/A
Tags: events, shortcode, plugin
Requires at least: 4.5
Tested up to: 6.2.2
Requires PHP: 5.6
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wordpress Plugin for New Mexico Department of Cultual Affairs

== Description ==

Display events from the New Mexico Department of Cultural Affairs Events API. 


A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working. e.g.

1. Upload `dca-events-plugin.zip` in the 'Plugins' menu or unzip folder in the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use Plugin =

The following shortcode options can be set:

- id = set id number for site
- limit = set number of events to display
- today = set true to show events for today
- current-month = set true to show events for this month
- date-range = set true to show events for range of dates
- range-start = set start date in mm-dd-yyyy format
- range-end = set end date in mm-dd-yyyy format

= Where to download =

[Github](https://github.com/NMHU-SSD/dca-events-wp-plugin)

== What is the NMDCA Events API ==

The NMDCA Media Center is a wordpress website and REST API for events and exhibitions for all NMDCA Cultural and Historic Sites. 

Endpoint:
- https://nmdcamediadev.wpengine.com/wp-json/tribe/events/v1/events/

You can see these parameters:
- per_page
- start_date
- end_date
- venue

Query example:
- http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?per_page=25&start_date=2023-06-26&end_date=023-06-29&venue=108

Docs:
- https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/

== Changelog ==

= 0.1.0 =
This is the first release. 