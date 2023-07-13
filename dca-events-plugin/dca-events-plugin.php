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
		return DateTime::createFromFormat('m-d-Y', $date)->format('Y-m-d');
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

	//get events from set default in settings menu
	$_SITE_ID = get_option('dca_events_plugin_option_name')['venue_id_0'];

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
		// TESTING:  [dca_events limit =4 current-day='true']
		$currentDate = date('Y-m-d');

		//format for url
		$_API_URL .= "&start_date=" . $currentDate . "&end_date=" . $currentDate;

	} else if ($_CURR_MONTH_OPT == true) {
		//get_events_by_current_month
		//get first and last day of this month as range
		// TESTING:  [dca_events limit=7 current-month='true']
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));

		//format for url
		$_API_URL .= "&start_date=" . $first . "&end_date=" . $last;

	} else if ($_DATE_RANGE_OPT == true) {
		// TESTING  
		// WORKs: [dca_events limit=7 date-range='true' range-start=2023-07-19 range-end=2023-07-19]
		// Works: [dca_events limit=7 date-range='true' range-start=7-24-2023 range-end=7-24-2023]
		// DOES NOT WORK: [dca_events limit=7 date-range='true' range-start=7-19-23 range-end=7-19-23]
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
	$_API_URL .= "&venue=" . $_SITE_ID;

	//make API request
	$json_data = file_get_contents($_API_URL);
	$response_data = json_decode($json_data);
	$output = "<script>console.log('PHP: " . $_API_URL . "');</script>";

	if ($response_data == null || $response_data->events == []) {
		$output .= '<div class="display-error">';
		$output .= "<script>console.log('Error: " . json_last_error() . "');</script>";
		$output .= "<h3>" . "Sorry no events to display please try again." . "</h3>" . "<br>";
		$output .= '</div>';

	} else {
		// return results (html output)
		$output .= "<div class='container-fluid'>";
		foreach ($response_data->events as $event) {

			$output .= "<div class='row p-0 mt-d mb-5'>";

			$output .= "<div class='col-12 col-md-6 p-0' style='min-height: 400px; '>";
			$output .= "<img src='".$event->image->url."' class='img-fluid object-fit-cover'>";
			$output .= "</div>";
			
			$output .= "<div class='col-12 col-md-6'>";
			$output .= "<span class='lead text-warning'>".$event->venue->venue ."</span>";
			$output .= "<h3 class='text-secondary'>".$event->title."</h3>";			
			$output .= "<p>".$event->description ."</p>";
			$output .= "<p>". "When: " .$event->start_date."</p>";
			$output .= "<p>". "Where: " .$event->venue->address."</p>";
			$output .= "<a href='".$event->url."'><button class='btn btn-seconday'>More details</button></a>";
			$output .= '</div>';

			$output .= '</div>';
		}
		$output .= '</div>';
	}
	// return output
	return $output;

}

//register shortcode
add_shortcode('dca_events', 'dca_events_plugin');

/*
 *
 *	Bootstrap
 *
 */

// https://developer.wordpress.org/reference/functions/wp_enqueue_style/

function addBootStrap() {
	wp_enqueue_style("bootstrapCSS", "https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css");
	wp_enqueue_script("bootstrapJS", "https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js");
}
add_action("wp_enqueue_scripts", "addBootStrap");

/*
 *
 *	Plugin Settings Page
 *
 */

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
		// TODO: Add in instuctions on how to use plug-in, format, examples, etc
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
		$request = file_get_contents("https://nmdcamediadev.wpengine.com/wp-json/tribe/events/v1/venues");
		$response = json_decode($request);

		// TODO: set a default for all events in the dropdown??

		?> <select name="dca_events_plugin_option_name[venue_id_0]" id="venue_id_0"> ?>
		<?php foreach ($response->venues as $venue) { ?>
				<?php $selected = (isset($this->dca_events_plugin_options['venue_id_0']) &&
					$this->dca_events_plugin_options['venue_id_0'] === $venue->id) ? 'selected' : ''; ?>
				<option value="<?php echo $venue->id ?>" <?php echo $selected; ?>><?php echo $venue->id ." " .$venue->venue ?></option>
		<?php } ?>
			</select>
		<?php
	}

}
if (is_admin())
	$dca_events_plugin = new DCAEventsPlugin();