<?php
/**
 * Plugin Name: DCA Events Plugin Settings
 * Plugin URI: https://www.nhccnm.org/events/feed/
 * Description: In-Testing stage
 * Version: 0.1
 * Author: Anita
 * Author URI: https://www.nhccnm.org/events/feed/
 **/

function dca_events_plugin($dca_atts = [])
{
	// normalize attibutes keys, to lowercase
	$dca_atts = array_change_key_case((array) $dca_atts, CASE_LOWER);

	$dca_atts = shortcode_atts(
		array(
			'current-day' => false,
			// default - current day is false
			'current-month' => false,
			// default - current month is false
			'date-range' => false,
			// default - date range is set to false
			'range-start' => NULL,
			// default - date start range is set to NULL
			'range-end' => NULL,
			// default - date end range is set to NULL
			'limit' => NULL // default - limit is set to NULL
		),
		$dca_atts
	);

	// check options and validate - not valid will result in NULL
	$_CURR_DAY_OPT = filter_var($dca_atts['current-day'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_CURR_MONTH_OPT = filter_var($dca_atts['current-month'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_DATE_RANGE_OPT = filter_var($dca_atts['date-range'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

	// set correct timezone and set/format range dates
	date_default_timezone_set('America/Denver');
	$_DATE_RANGE_START = ($dca_atts['range-start'] == NULL) ? date('m-d-Y') : reformatDate($dca_atts['range-start']);
	$_DATE_RANGE_END = ($dca_atts['range-end'] == NULL) ? date('m-d-Y') : reformatDate($dca_atts['range-start']);

	// set default limit if null - else typecast
	$_LIMIT_OPT = ($dca_atts['limit'] == NULL) ? 10 : intval($dca_atts['limit']);

	// helper functions for dca_events shortcode
	function reformatDate($date)
	{
		return DateTime::createFromFormat('m-d-Y', $date)->format('m-d-Y');
	}
	function getTodayEvents()
	{

	}

	function getMonthEvents()
	{

	}

	function getRangeEvents()
	{

	}

	$feed_setting = get_option('rss_events_page_option_name')['rss_feed_0'];
	$get_feed = file_get_contents($feed_setting);
	$xml = simplexml_load_string($get_feed);

	// start div box
	$div_box = '<div class="dca-event-box">';

	foreach ($xml->children() as $events) {
		$div_box .= "<h1>" . $events->title . "<br> " . "</h1>";
		$div_box .= "<h5>" . "Link: " . $events->link . "<br> " . "</h5>";
		$div_box .= "<h5>" . $events->description . "<br>" . "</h5>";

		// check the values of all variables make sure it is working - will be taken out later
		// $div_box .= "<h5>" . "Current day = " . $_CURR_DAY_OPT  ."<br>" . "</h5>"; // works
		// $div_box .= "<h5>" . "Current month = " . $_CURR_MONTH_OPT . "<br>" . "</h5>"; // works
		// $div_box .= "<h5>" . "Date range = " . $_DATE_RANGE_OPT . "<br>" . "</h5>"; // works
		// $div_box .= "<h5>" . "Limit = " . $_LIMIT_OPT . "<br>" . "</h5>"; // works
		// $div_box .= "<h5>" . "Date range start = " . $_DATE_RANGE_START . "<br>" . "</h5>"; 
		// $div_box .= "<h5>" . "Date Range end = " . $_DATE_RANGE_END . "<br>" . "</h5>";

		// check the current date
		// $currentDate = date('m-d-Y');
		// $div_box .= "<h5>" . "Today's date: " . $currentDate . "<br>" . "</h5>";


		// check the limit output for the num of event to display
		foreach (new LimitIterator($events->item, 0, $_LIMIT_OPT) as $itm) {
			$timestamp_month = idate('m', $xml->$events->item->pubDate);
			$timestamp_day = date('m-d-Y', $xml->$events->item->pubDate);

			$currentDate = date('m-d-Y');
			// if current-day is true and current date matches pubdate date
			if ($_CURR_DAY_OPT == true && $currentDate == $timestamp_day) {
				// output the events for only the current date only
				$div_box .= "<h4>" . "Event Name: " . $itm->title . "<br>" . "</h4>";
				$div_box .= "<p>" . "Date: " . $itm->pubDate . "<br>" . "</p>";
				$div_box .= "<p>" . "Description: " . $itm->description . "<br>" . "</p>";

			}
			$currentMonth = idate('m');
			// if current-month is true and current month matches pubdate month
			if ($_CURR_MONTH_OPT == true && $currentMonth == $timestamp_month) {
				$div_box .= "<h4>" . $currentMonth . "<br>" . "</h4>";
				$div_box .= "<h4>" . $timestamp_month . "<br>" . "</h4>";

				// output the events for only the current month only
				$div_box .= "<h4>" . "Event Name: " . $itm->title . "<br>" . "</h4>";
				$div_box .= "<p>" . "Date: " . $itm->pubDate . "<br>" . "</p>";
				$div_box .= "<p>" . "Description: " . $itm->description . "<br>" . "</p>";

			}
			//if date-range is true current month is false and current day is false
			if ($_DATE_RANGE_OPT == true) {
				$events_date = $itm->pubDate;
				// output events for the date range selected
				if ($events_date >= $_DATE_RANGE_START && $events_date <= $_DATE_RANGE_END) {
					$div_box .= "<h4>" . "Event Name: " . $itm->title . "<br>" . "</h4>";
					$div_box .= "<p>" . "Date: " . $itm->pubDate . "<br>" . "</p>";
					$div_box .= "<p>" . "Description: " . $itm->description . "<br>" . "</p>";
				}

			}

		}
	}
	// end div box
	$div_box .= '</div>';

	// return output
	return $div_box;
}
add_shortcode('dca_events', 'dca_events_plugin');

function register_shortcodes()
{
	add_shortcode('dca_events', 'dca_events_plugin');
}

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
			'DCA Events Plugin Settings',
			// page_title
			'DCA Events Plugin Settings',
			// menu_title
			'manage_options',
			// capability
			'rss-events-page',
			// menu_slug
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
												<li>&emsp; limit => set number of events to display</li>
												<li>&emsp; current-day => set true to show events for today</li>
												<li>&emsp; current-month => set true to show events for this month</li>
												<li>&emsp; date-range => set true to show events for range of dates</li>
												<li>&emsp; range-start => set start date in mm-dd-yyyy format</li>
												<li>&emsp; range-end => set end date in mm-dd-yyyy format</li>
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
			'rss_feed_0',
			// id
			'RSS Feed',
			// title
			array($this, 'rss_feed_0_callback'),
			// callback
			'rss-events-page-admin',
			// page
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


?>