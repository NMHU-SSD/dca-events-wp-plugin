# DCA Events Wordpress Plugin
Wordpress Plugin for NM DCA Media Center API
Display events via [dca_events] shortcode.

shortcode options:
- site = set id number for museum site (venue)
- limit = set number of events to display
- today = set true to show events for today
- current-month = set true to show events for this month
- date-range = set true to show events for range of dates
- range-start = set start date in yyyy-mm-dd format
- range-end = set end date in yyyy-mm-dd format


## Installation

This section describes how to install the plugin and get it working. e.g.

1. Compress `dca-events-plugin` directory as .zip 
2. Upload `dca-events-plugin.zip` in the 'Plugins' menu or unzip folder into the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress


### NM DCA Media Center API 

The NMDCA Media Center is a wordpress website and REST API for events and exhibitions for all NMDCA Cultural and Historic Sites. 

Endpoint:
- https://nmdcamediadev.wpengine.com/wp-json/tribe/events/v1/events/

Some parameters:
- page
- start_date
- end_date
- venue

Query example:
''' http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?per_page=25&start_date=2023-06-26&end_date=023-06-29&venue=108
'''

API doc:
- https://nmdcamediadev.wpengine.com/wp-json/tribe/events/v1/doc

WPDocs:
- https://developer.wordpress.org/rest-api/using-the-rest-api/

## Internship
Client: New Mexico Department of Cultural Affairs
Student Intern: [Anita Martin](https://github.com/anita-martin5703/)
