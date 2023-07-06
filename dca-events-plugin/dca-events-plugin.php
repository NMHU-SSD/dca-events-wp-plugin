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
			'site' => NULL, //site id for museum
			'today' => false, //events by current day
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
	$_CURR_DAY_OPT = filter_var($atts['today'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_CURR_MONTH_OPT = filter_var($atts['current-month'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_DATE_RANGE_OPT = filter_var($atts['date-range'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);


	//set correct timezone and set/format range dates
	date_default_timezone_set('America/Denver');
	$_DATE_RANGE_START = ($atts['range-start'] == NULL) ? date("Y-m-d") : reformatDate($atts['range-start']);
	$_DATE_RANGE_END = ($atts['range-end'] == NULL) ? date("Y-m-d") : reformatDate($atts['range-end']);

	// set default limit if null - else typecast shortcode val
	$_LIMIT_OPT = ($atts['limit'] == NULL) ? 10 : intval($atts['limit']);


	//get events from site option else get default in settings menu
	$_SITE_ID = ($atts['site'] == NULL) ? intval($atts['site']) :  get_option('dca_events_plugin_option_name')['venue_id_0'] ;
	
	// TODO: should I set a default for all events in the dropdown?
	//       if so how do I do that its just with no ?venues in the api right?

	//get api results
	//docs: https://developer.wordpress.org/rest-api/using-the-rest-api/
	//sample: http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?page=10&start_date=2023-06-26&end_date=2023-06-29&venue=108

	//start api string
	$_API_URL = "http://nmdcamediadev.wpengine.com/wp-json/tribe/events/v1/events/?";

	//set limit in api url
	$_API_URL .= "per_page=" . $_LIMIT_OPT;

	//set dates in based on options
	if ($_CURR_DAY_OPT == true) {
		//get_events_today
		$currentDate = date('Y-m-d');

		//format for url
		$_API_URL .= "&start_date=" . $currentDate . "&end_date=" . $currentDate;

	} else if ($_CURR_MONTH_OPT == true) {
		//get_events_by_current_month
		//get first and last day of this month as range
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));

		//format for url
		$_API_URL .= "&start_date=" . $first . "&end_date=" . $last;

	} else if ($_DATE_RANGE_OPT == true) {
		//get_events_by_range
		$start_date = $_DATE_RANGE_START;
		$end_date = $_DATE_RANGE_END;

		//format for url
		$_API_URL .= "&start_date=" . $start_date . "&end_date=" . $end_date;

	} else {
		//default - get_events_by_current_month
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));
		$_API_URL .= "&start_date=" . $first . "&end_date=" . $last;
	}

	//set venue in api url
	$_API_URL .= "&venues=" . $_SITE_ID;

	//make API request
	$json_data = file_get_contents($_API_URL);
	$response_data = json_decode($json_data);
	$event_data = $response_data->events;
	
	
	echo ("<script>console.log('URL: " . $_API_URL . "');</script>");
	//echo var_dump($event_data);


	if ($event_data == null) {
		echo json_last_error() . "<br>";
		
	} else {

		// return results (html output)
		foreach ($event_data as $events) {

			$output .= '<div class="dca-event">';
			// title, description, event dates, venue name, address
			$output .= "<h5>" . "Title: " . $events->title . "</h5>" . "<br> ";
			$output .= "<p>" . "Description: " . $events->description . "</p>" . "<br>";
			$output .= "<p>" . "Date: " . $events->date . "</p>" . "<br>";
			$output .= "<p>" . "Venue: " . $events->venue->venue . "</p>" . "<br> ";
			$output .= "<p>" . "Address: " . $events->venue->address . "</p>" . "<br> ";

			$output .= '</div>';
		}
	}
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
class DCAEventsPlugin
{
	private $dca_events_plugin_options;

	public function __construct()
	{
		add_action('admin_menu', array($this, 'dca_events_plugin_add_plugin_page'));
		add_action('admin_init', array($this, 'dca_events_plugin_page_init'));
	}

	public function dca_events_plugin_add_plugin_page()
	{
		add_menu_page(
			'DCA Events Plugin', // page_title
			'DCA Events Plugin', // menu_title
			'manage_options', // capability
			'dca-events-plugin', // menu_slug
			array($this, 'dca_events_plugin_create_admin_page'), // function
			'dashicons-admin-generic', // icon_url
			75 // position
		);
	}

	public function dca_events_plugin_create_admin_page()
	{
		$this->dca_events_plugin_options = get_option('dca_events_plugin_option_name'); ?>

				<div class="wrap">
					<h2>DCA Events Plugin</h2>
					<p></p>
					<?php settings_errors(); ?>

					<form method="post" action="options.php">
						<?php
						settings_fields('dca_events_plugin_option_group');
						do_settings_sections('dca-events-plugin-admin');
						submit_button();
						?>
					</form>
				</div>
		<?php }

	public function dca_events_plugin_page_init()
	{
		register_setting(
			'dca_events_plugin_option_group', // option_group
			'dca_events_plugin_option_name', // option_name
			array($this, 'dca_events_plugin_sanitize') // sanitize_callback
		);

		add_settings_section(
			'dca_events_plugin_setting_section', // id
			'Settings', // title
			array($this, 'dca_events_plugin_section_info'), // callback
			'dca-events-plugin-admin' // page
		);

		add_settings_field(
			'venue_id_0', // id
			'Venue ID', // title
			array($this, 'venue_id_0_callback'), // callback
			'dca-events-plugin-admin', // page
			'dca_events_plugin_setting_section' // section
		);
	}

	public function dca_events_plugin_sanitize($input)
	{
		$sanitary_values = array();
		if (isset($input['venue_id_0'])) {
			$sanitary_values['venue_id_0'] = $input['venue_id_0'];
		}

		return $sanitary_values;
	}

	public function dca_events_plugin_section_info()
	{

	}

	public function venue_id_0_callback()
	{
		?> <select name="dca_events_plugin_option_name[venue_id_0]" id="venue_id_0">
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '93') ? 'selected' : ''; ?>
			<option value="93" <?php echo $selected; ?>> 93 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '94') ? 'selected' : ''; ?>
			<option value="94" <?php echo $selected; ?>> 94 Bosque Redondo Memorial at Fort Sumner Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '95') ? 'selected' : ''; ?>
			<option value="95" <?php echo $selected; ?>> 95 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '96') ? 'selected' : ''; ?>
			<option value="96" <?php echo $selected; ?>> 96 Coronado Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '97') ? 'selected' : ''; ?>
			<option value="97" <?php echo $selected; ?>> 97 El Camino Real Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '98') ? 'selected' : ''; ?>
			<option value="98" <?php echo $selected; ?>> 98 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '99') ? 'selected' : ''; ?>
			<option value="99" <?php echo $selected; ?>> 99 Fort Selden</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '100') ? 'selected' : ''; ?>
			<option value="100" <?php echo $selected; ?>> 100 Fort Stanton Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '101') ? 'selected' : ''; ?>
			<option value="101" <?php echo $selected; ?>> 101 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '102') ? 'selected' : ''; ?>
			<option value="102" <?php echo $selected; ?>> 102 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '103') ? 'selected' : ''; ?>
			<option value="103" <?php echo $selected; ?>> 103 Jemez Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '104') ? 'selected' : ''; ?>
			<option value="104" <?php echo $selected; ?>> 104 Lincoln Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '105') ? 'selected' : ''; ?>
			<option value="105" <?php echo $selected; ?>> 105 Los Luceros Historic Site</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '106') ? 'selected' : ''; ?>
			<option value="106" <?php echo $selected; ?>> 106 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '107') ? 'selected' : ''; ?>
			<option value="107" <?php echo $selected; ?>> 107 Museum Hill Partners</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '108') ? 'selected' : ''; ?>
			<option value="108" <?php echo $selected; ?>> 108 Museum of Indian Arts & Culture</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '109') ? 'selected' : ''; ?>
			<option value="109" <?php echo $selected; ?>> 109 Museum of International Folk Art</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '110') ? 'selected' : ''; ?>
			<option value="110" <?php echo $selected; ?>> 110 Museum fo New Mexico</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '111') ? 'selected' : ''; ?>
			<option value="111" <?php echo $selected; ?>> 111 Museum of New Mexico Foundation</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '112') ? 'selected' : ''; ?>
			<option value="112" <?php echo $selected; ?>> 112 Museum Resources</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '113') ? 'selected' : ''; ?>
			<option value="113" <?php echo $selected; ?>> 113 National Hispanic Cultural Center</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '114') ? 'selected' : ''; ?>
			<option value="114" <?php echo $selected; ?>> 114 New Mexico Arts</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '115') ? 'selected' : ''; ?>
			<option value="115" <?php echo $selected; ?>> 115 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '116') ? 'selected' : ''; ?>
			<option value="116" <?php echo $selected; ?>> 116 New Mexico Department of Cultural Affairs</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '117') ? 'selected' : ''; ?>
			<option value="117" <?php echo $selected; ?>> 117 New Mexico Farm and Ranch Heritage Museum</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '118') ? 'selected' : ''; ?>
			<option value="118" <?php echo $selected; ?>> 118 New Mexico Historic Sites</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '119') ? 'selected' : ''; ?>
			<option value="119" <?php echo $selected; ?>> 119 New Mexico History Museum</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '120') ? 'selected' : ''; ?>
			<option value="120" <?php echo $selected; ?>> 120 New Mexico Museum of Art</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '121') ? 'selected' : ''; ?>
			<option value="121" <?php echo $selected; ?>> 121 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '122') ? 'selected' : ''; ?>
			<option value="122" <?php echo $selected; ?>> 122 No name</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '123') ? 'selected' : ''; ?>
			<option value="123" <?php echo $selected; ?>> 123 New Mexico Museum of Space History</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '124') ? 'selected' : ''; ?>
			<option value="124" <?php echo $selected; ?>> 124 New Mexico State Library</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '125') ? 'selected' : ''; ?>
			<option value="125" <?php echo $selected; ?>> 125 Office of Archaeological Studies</option>
			<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) && $this->dca_events_plugin_options['venue_id_0'] === '126') ? 'selected' : ''; ?>
			<option value="126" <?php echo $selected; ?>> 126 Taylor Mesilla Historic Site</option>
		</select> <?php
	}

}
if (is_admin())
	$dca_events_plugin = new DCAEventsPlugin();
