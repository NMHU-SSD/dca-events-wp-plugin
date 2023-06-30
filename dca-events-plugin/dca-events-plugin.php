<?php
/**
 * Plugin Name:     DCA Events Plugin
 * Plugin URI:      https://github.com/NMHU-SSD/dca-events-wp-plugin
 * Description:     New Mexico Department of Cultural Affairs Events
 * Author:          NMHU SSD intern Anita Martin
 * Author URI:      https://github.com/NMHU-SSD
 * Text Domain:     dca-events-plugin
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Dca_Events_Plugin
 */


// @Anita add plugin shortcode goes here - see starter vars and comments:

/*
 *
 *	Plugin Shortcode function
 *
 */

function dca_events_plugin($atts = [])
{
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array) $atts, CASE_LOWER);

	//gets shortcode vals
	$atts = shortcode_atts(
		array(
			'id' => NULL, //site id for museum
			'current-day' => false, //events by current day
			'current-month' => false, //events by current month
			'date-range' => false, //events by range
			'range-start' => NULL, //start range
			'range-end' => NULL, //end range
			'limit' => NULL // num of events to display
		),
		$atts
	);

	// helper functions for dca_events shortcode
	function reformatDate($date)
	{
		return DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d');
	}

	// check options and validate - not valid will result in NULL
	$_CURR_DAY_OPT = filter_var($atts['current-day'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_CURR_MONTH_OPT = filter_var($atts['current-month'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_DATE_RANGE_OPT = filter_var($atts['date-range'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);


	//set correct timezone and set/format range dates
	date_default_timezone_set('America/Denver');
	$_DATE_RANGE_START = ($atts['range-start'] == NULL) ? date("Y-m-d") : reformatDate($atts['range-start']);
	$_DATE_RANGE_END = ($atts['range-end'] == NULL) ? date("Y-m-d") : reformatDate($atts['range-end']);

	// set default limit if null - else typecast shortcode val
	$_LIMIT_OPT = ($atts['limit'] == NULL) ? 10 : intval($atts['limit']);

	//if null get events from all venues or set default in settings menu? (todo: get id from settings dropdown)
	//using hardcoded id for now
	$_SITE_ID = ($atts['id'] == NULL) ? 108 : intval($atts['id']);

	//get api results
	//docs: https://developer.wordpress.org/rest-api/using-the-rest-api/
	//sample: http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?page=10&start_date=2023-06-26&end_date=2023-06-29&venue=108

	//start api string
	$_API_URL = "http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?";

	//set limit in api url
	$_API_URL .= "per_page=" . $_LIMIT_OPT;

	//set dates in based on options
	if ($_CURR_DAY_OPT == true) {
		//get_events_today
		//get todays date ajd format
		$currentDate = date('Y-m-d');

		//format for url
		$_API_URL .= "&start_date=". $currentDate ."&end_date=". $currentDate;
		
	} else if ($_CURR_MONTH_OPT == true) {
		//get_events_by_current_month
		//get first and last day of this month as range
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));
		
		//format for url
		$_API_URL .= "&start_date=". $first ."&end_date=". $last;

	} else if ($_DATE_RANGE_OPT == true) {
		//get_events_by_range
		$start_date = $_DATE_RANGE_START;
		$end_date = $_DATE_RANGE_END;
		
		//format for url
		$_API_URL .= "&start_date=". $start_date ."&end_date=". $end_date;

	} else {
		//default - get_events_by_current_month
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));
		$_API_URL .= "&start_date=". $first ."&end_date=". $last;
	}

	//set venue in api url
	$_API_URL .= "&venue=" . $_SITE_ID;

	//make API request
	// USE LATER with dropdown: $feed_setting = get_option('rss_events_page_option_name')['rss_feed_0'];
	// USE LATER with dropdown: $get_feed = file_get_contents($feed_setting + $_API_URL);

	// Testing [dca_events date-range='true' range-start=2023-06-26 range-end=2023-06-29]

	$json_data = file_get_contents($_API_URL);
	$response_data = json_decode($json_data);
	$event_data = $response_data;

	// return results (html output)
	// start div box
	echo("<script>console.log('PHP: " . $_API_URL . "');</script>");
	
	$output = '<div class="dca-events">';

	foreach ($event_data as $events) {
			$output = '<div class="dca-event">';
			$output .= "<b>" . "Venue: " . $events->venue->url->venue. "</b>" . "<br> " ;
			$output .= "<h5>" . "Title: " . $events->title . "</h5>" . . "<br> ";
			$output .= "<p>" . "Description: " . $events->venue->url->description . "</p>" . . "<br>";
			$output = '</div>';
	}
	// end div box
	$output .= '</div>';

	// return output
	return $output;

}



//register shortcode
add_shortcode('dca_events', 'dca_events_plugin');


/*
 *
 *	Plugin Settings Page
 *
 */


// @Anita Your plugin settings page code goes here:

class RSSEventsPage
{
	private $rss_events_page_options;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'rss_events_page_add_plugin_page'));
		add_action('admin_init', array($this, 'rss_events_page_page_init'));
	}

	public function rss_events_page_add_plugin_page()
	{
		add_options_page(
			'DCA Events Plugin', // page_title
			'DCA Events Plugin', // menu_title
			'manage_options', // capability
			'rss-events-page', // menu_slug
			array($this, 'rss_events_page_create_admin_page') // function
		);
	}

	public function rss_events_page_create_admin_page()
	{
		$this->rss_events_page_options = get_option('rss_events_page_option_name'); ?>

												<div class="wrap">
													<h2>DCA Events Plugin</h2>
													<h3>Shortcode Options</h3>
														<ul>
														<li>&emsp; per_page => </li>
														<li>&emsp; start_date => </li>
														<li>&emsp; end_date => </li>
														<li>&emsp; venue => </li>
														</ul> 
													<?php settings_errors(); ?>

													<form method="post" action="options.php">
														<?php
														settings_fields('rss_events_page_option_group');
														do_settings_sections('rss-events-page-admin');
														submit_button();
														?>
													</form>
												</div>
						<?php }

	public function rss_events_page_page_init()
	{
		register_setting(
			'rss_events_page_option_group',
			// option_group
			'rss_events_page_option_name',
			// option_name
			array($this, 'rss_events_page_sanitize') // sanitize_callback
		);

		add_settings_section(
			'rss_events_page_setting_section',
			// id
			'Settings',
			// title
			array($this, 'rss_events_page_section_info'),
			// callback
			'rss-events-page-admin' // page
		);

		add_settings_field(
			'rss_feed_0', // id
			'RSS Feed', // title
			array($this, 'rss_feed_0_callback'), // callback
			'rss-events-page-admin', // page
			'rss_events_page_setting_section' // section
		);
	}

	public function rss_events_page_sanitize($input)
	{
		$sanitary_values = array();
		if (isset($input['rss_feed_0'])) {
			$sanitary_values['rss_feed_0'] = sanitize_text_field($input['rss_feed_0']);
		}
		return $sanitary_values;
	}

	public function rss_events_page_section_info()
	{

	}

	public function rss_feed_0_callback()
	{
		printf(
			'<input class="regular-text" type="text" name="rss_events_page_option_name[rss_feed_0]" id="rss_feed_0" value="%s">',
			isset($this->rss_events_page_options['rss_feed_0']) ? esc_attr($this->rss_events_page_options['rss_feed_0']) : ''
		);
	}
}
$rss_events_page = new RSSEventsPage();