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
 * @package         DCA_Events_Plugin
 */


/*------------------------------------
 *
 *	Helper functions
 *
 */

// Formats date in shortcode
function formatShortcodeDate($date)
{
	return DateTime::createFromFormat('Y-m-d', $date)->format('Y-m-d');
}

// Formats event date in output 
function formatEventDate($date)
{
	return date("F d, Y", strtotime($date));
}

// Formats event time in output 
function formatEventTime($date)
{
	return date("h:i A", strtotime($date));
}


// Function for an API request
function api_request($url)
{
	//context options for request
	$options = array(
	      "ssl" => array(
	        "verify_peer" => false,
	        "verify_peer_name" => false,
	      )
	);  
  
	$context = stream_context_create($options);
	$response = file_get_contents($url,false,$context);
	
	return json_decode($response);
	
}


/*--------------------------------------------
 *
 *	Plugin Shortcode function
 *
 */
function dca_events_plugin($atts = [])
{
	// normalize attribute keys, and lowercase 
	$atts = array_change_key_case((array) $atts, CASE_LOWER);

	// Gets shortcode vals
	$atts = shortcode_atts(
		array(
			'site' => NULL, //site id for museum
			'today' => false, //events by current day
			'current-month' => false, //events by current month
			'date-range' => false, //events by range
			'range-start' => NULL, //start range
			'range-end' => NULL, //end range
			'limit' => NULL //num of events to display,
		),
		$atts
	);

	// Check options and validate - not valid will result in NULL
	$_CURR_DAY_OPT = filter_var($atts['today'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_CURR_MONTH_OPT = filter_var($atts['current-month'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_DATE_RANGE_OPT = filter_var($atts['date-range'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

	// Set correct timezone and set/format range dates
	date_default_timezone_set('America/Denver');
	$_DATE_RANGE_START = ($atts['range-start'] == NULL) ? date("Y-m-d") : formatShortcodeDate($atts['range-start']);
	$_DATE_RANGE_END = ($atts['range-end'] == NULL) ? date("Y-m-d") : formatShortcodeDate($atts['range-end']);

	// Set default limit if null - else typecast shortcode val
	$_LIMIT_OPT = ($atts['limit'] != NULL) ? intval($atts['limit']) : 10;

	// Get events from site option else get default in settings menu
	$_SITE_ID = ($atts['site'] != NULL) ? intval($atts['site']) : get_option('dca_events_plugin_option_name')['venue_id'];

	// Get API results
	// docs: https://developer.wordpress.org/rest-api/using-the-rest-api/
	// sample: http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?page=10&start_date=2023-06-26&end_date=2023-06-29&venue=108

	// Start API string
	$_API_URL = "https://test-dca-mc.nmdca.net/wp-json/tribe/events/v1/events/?";

		
	// Set limit in API url
	$_API_URL .= "per_page=" . $_LIMIT_OPT;
		
	

	// Set dates based on user options
	if ($_CURR_DAY_OPT == true) {
		// get_events_today
		$currentDate = date('Y-m-d');

		// Format to add to url
		$_API_URL .= "&start_date=" . $currentDate . "&end_date=" . $currentDate;

	} else if ($_CURR_MONTH_OPT == true) {
		// get_events_by_current_month
		// Get first and last day of current month as range
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));

		// Format for URL
		$_API_URL .= "&start_date=" . $first . "&end_date=" . $last;

	} else if ($_DATE_RANGE_OPT == true) {
		// get_events_by_range
		$start_date = $_DATE_RANGE_START;
		$end_date = $_DATE_RANGE_END;

		// Format for URL
		$_API_URL .= "&start_date=" . $start_date . "&end_date=" . $end_date;

	} else {
		// default - get_events_by_current_month
		$currentDate = date('Y-m-d');
		$first = date('Y-m-01', strtotime($currentDate));
		$last = date('Y-m-t', strtotime($currentDate));
		$_API_URL .= "&start_date=" . $first . "&end_date=" . $last;
	}


	//check is site id exists before adding to api endpoint
	if ($_SITE_ID != NULL){
		
		// Set venue in API URL
		$_API_URL .= "&venue=" . $_SITE_ID;
		
	}
	
	
	

	// Make an API request
	$response_data = api_request($_API_URL);

	// Start a container
	$output .= '<div class="container-fluid p-0 ">';

	// If response data and events are empty
	if ($response_data == null || $response_data->events == []) {
		// Console out error and display message to user
		$output .= "<script>console.log('Error: " . json_last_error() . "');</script>";
		$output .= "<h3 class='text-error'>" . "Sorry, no events to display. Please try again." . "</h3>" . "<br>";

	} else {
		// Return results  in html format
		foreach ($response_data->events as $event) {
			
			// Create row
			$output .= "<div class='row p-0 mt-5 mb-5 '>";
			
			// Create 1st column
			$output .= "<div class='col-12 col-md-6 p-2'>";
			$output .= "<img src='" . $event->image->url . "'  style='min-height: 200px; height: 100%; width: 100%; object-fit: cover;'   >";
			// End of 1st column
			$output .= "</div>";
			
			// Create 2nd column
			$output .= "<div class='col-12 col-md-6 text-center text-md-start'>";
			
			$output .= "<h3>" . $event->title . "</h3>";
			$output .= "<span class='lead'>" . $event->venue->venue . "</span>";
			$output .= "<br>";
			$url = site_url() . "/events/". $event->id;
			$output .= "<a href='".$url."' ><button class='btn btn-seconday mt-4'>More details</button></a>";
			// End of 2nd column
			$output .= "</div>";
			
			// End of row
			$output .= "</div>";

		}

	}
	// End container
	$output .= '</div>';


	// Return output
	return $output;

}

// Register shortcode
add_shortcode('dca_events', 'dca_events_plugin');

/*-------------------------------------------------
 *
 *	Bootstrap
 *
 */

// https://developer.wordpress.org/reference/functions/wp_enqueue_style/

// Function for adding bootstrap
function addBootStrap()
{
	wp_enqueue_style("bootstrap", "https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css");
	wp_enqueue_script("bootstrapJS", "https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js", array('jquery'),true);
}
// Add bootstrap to templates
add_action("wp_enqueue_scripts", "addBootStrap");
// Add bootstrap to admin settings
add_action( 'admin_init', 'addBootStrap' );


/*--------------------------------------------
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
			// page_title
			'DCA Events Plugin', 
			// menu_title
			'DCA Events Plugin',
			// capability
			'manage_options', 
			// menu_slug
			'dca-events-plugin',
			// function
			array($this, 'dca_events_plugin_create_admin_page'), 
			// icon_url
			'dashicons-admin-generic',
			// position of where the plugin will go
			75 
		);
	}

	// Function for displaying the setting page
	public function dca_events_plugin_create_admin_page()
	{
		$this->dca_events_plugin_options = get_option('dca_events_plugin_option_name'); ?>
		
		<div class="container">
			<h1 class="display-1 mb-2">DCA Events Plugin</h1>
			
			<h3>How to use DCA Events Plugin shortcode</h3>

			<p>Using the shortcode will display limited number of events by site id or date range.</p>									
			
			<h4>Instructions</h4>
			<p>Create a post or page using the [dca_events *options] shortcode with avaliable options.</p>
			
			
			<b>Examples</b>
			<p>[dca_events site="120" today='true'] will return the default number of events (10) for today for the site with an id of 120 (New Mexico Museum of Art) </p>
			<p>[dca_events limit=7 current-month='true'] will return 7 events for the current month</p>
			<p>[dca_events limit=2 date-range='true' range-start=2023-07-19 range-end=2023-07-23] will return 2 events that are happening July 19, 2023 to July 23, 2023 </p>
			
			<b >Shortcode Options Avaliable</b>
			
			<table class="table table-bordered table-sm"  >
				<thead class="thead-light">
					<tr>
						<th scope="col">OPTION</th>
						<th scope="col">DESCRIPTION</th>
						<th scope="col">FORMAT</th>
					</tr>
				</thead>
				<tbody>
				<tr>
			        <th scope="row">site</th>
					<td>set site ID in shortcode or set default in page settngs dropdown</td>
					<td>integer value</td>
				</tr>
				<tr>
					<th scope="row">today</th>
					<td>returns today's events</td>
					<td>TRUE or FALSE</td>
				</tr>
				<tr>
					<th scope="row">current-month</th>
					<td>returns events by current month</td>
					<td>TRUE or FALSE</td>
				</tr>
				<tr>
					<th scope="row">date-range</th>
					<td>return events by specific date range</td>
					<td>TRUE or FALSE</td>
				</tr>
				<tr>
					<th scope="row">range-start</th>
					<td>if date-range is true, define a specific start range</td>
					<td> YYYY-MM-DD</td> 
				</tr>
				<tr>
					<th scope="row">range-end</th>
					<td>if date-range is true, define a specific end range</td>
					<td>YYYY-MM-DD</td>
				</tr>
				<tr>
					<th scope="row">limit</th>
					<td>returns specific number of events to display (default is 10)</td>
					<td>any integer value</td>
				</tr>
				</tbody>
			</table>
			
			
			<form method="post" action="options.php">
				
				
				<?php
				
					settings_fields('dca_events_plugin_option_group');
					
					do_settings_sections('dca-events-plugin-admin');
					
					submit_button();
					?>
			</form>
				
			
		
		</div>
	<?php }

	// Function for intializing fields
	public function dca_events_plugin_page_init()
	{
		// Register the settings
		register_setting(
			// option_group
			'dca_events_plugin_option_group',
			// option_name
			'dca_events_plugin_option_name',
			// sanitize_callback
			array($this, 'dca_events_plugin_sanitize')
		);
		// Add the settings 
		add_settings_section(
			// id
			'dca_events_plugin_setting_section',
			// title
			'Events Page Settings',
			// callback
			array($this, 'dca_events_plugin_section_info'),
			// page
			'dca-events-plugin-admin' 
		);
		// Adding the fields
		add_settings_field(
			// id
			'venue_id',
			// title
			'Site ID',
			// callback
			array($this, 'venue_id_callback'),
			// page
			'dca-events-plugin-admin',
			// section
			'dca_events_plugin_setting_section' 
		);
		
	}

	// Function to sanitize the inputs
	public function dca_events_plugin_sanitize($input)
	{
		$sanitary_values = array();
		if (isset($input['venue_id'])) {
			$sanitary_values['venue_id'] = $input['venue_id'];
		}

		return $sanitary_values;
	}


	public function dca_events_plugin_section_info()
	{ // left blank intentionally
		?>
		
		<p>Events page will display events by the selected site id. </p>									
		
		<p>View <?php echo "<a href='".site_url() . "/events"."'>". site_url() . "/events"."</a>"; ?></p>
		
		<?php		
		
	}

	// Callback function for retreiving venues
	public function venue_id_callback()
	{
		// Base URL for venues
		$url = "https://test-dca-mc.nmdca.net/wp-json/tribe/events/v1/venues";
		$response_data = api_request($url);
		// for loop for looping through all the venues and outputting a dropdown menu
		?> 	
		
		
				<select name="dca_events_plugin_option_name[venue_id]" id="venue_id"> ?>
					
					<?php
					
					 $all = (isset($this->dca_events_plugin_options['venue_id']) && $this->dca_events_plugin_options['venue_id'] == NULL )? 'selected' : '';
					 
					?>
					
					<option value="" <?php echo $all; ?> > 
						All Sites
					</option>
				<?php foreach ($response_data->venues as $venue) { ?>
		
									<?php
									$selected = (isset($this->dca_events_plugin_options['venue_id']) &&
										$this->dca_events_plugin_options['venue_id'] == $venue->id) ? 'selected' : ''; ?>
									<option value="<?php echo $venue->id ?>" <?php echo $selected; ?> > 
										<?php echo $venue->id . " - " . $venue->venue ?>
									</option>
		
				<?php } ?>
					</select>
				<?php
	}

}
// Plugin will only show if you are logged in as the administrator
if (is_admin())
	$dca_events_plugin = new DCAEventsPlugin();







/*--------------------------------------------------
 *
 *	URL Rewrite and Custom Template
 *
 */
// Function for Rewrite Events
function customRewriteEvent()
{
	
	/** @global WP_Rewrite $wp_rewrite */
	global $wp_rewrite;
	

	// create the rules for rewriting the endpoints for /events and /events/id
	$newRules = array(
		'events/?$' => 'index.php?custom_page=events',
        'events/(\d+)/?$' => sprintf(
            'index.php?custom_page=events&event_id=%s',
            $wp_rewrite->preg_index(1)
        ),
	);

	$wp_rewrite->rules = $newRules + (array) $wp_rewrite->rules;
	
}
// Adding the hook for customRewriteEvents
add_action('generate_rewrite_rules', 'customRewriteEvent');


/**
 * Flush rewrite rules on activation
 */
function wpdocs_flush_rewrites() {
	// call your CPT registration function here (it should also be hooked into 'init')
	wpdocs_custom_post_types_registration();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'wdocs_flush_rewrites' );



// Function for the theme redirect
function customThemeRedirect()
{
	// Current working directory
	$plugindir = dirname(__FILE__);
	// plugin name
	$prefix = 'dca-events-plugin';
	// Sub directory in your plugin to put all your template files
	$themeFilesDir = 'templates'; 
	// custom var page name
	$page = get_query_var('custom_page');
	// custom var event name
	$event_id = (int) get_query_var('event_id', 0);

	//if there is no id show all events
	if ($page == 'events' && empty($event_id)) {

		//use shortcode function to pass output of events
		$data = array(
			'events' => dca_events_plugin()
		);
		 // filename of template
		$filename = 'events.php';
		// Full path name
		$fullTemplatePath = TEMPLATEPATH . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $filename;
		// return the template
		$returnTemplate = (file_exists($fullTemplatePath)) ? $fullTemplatePath : $plugindir . DIRECTORY_SEPARATOR . $themeFilesDir . DIRECTORY_SEPARATOR . $filename;
		// call function doCustomThemeRedirect
		doCustomThemeRedirect($returnTemplate, true, $data);
		return;

	} 
	// If there is an id do the following
	elseif ($page == 'events' && !empty($event_id)) 
	{
		
		// query the single event using API and send as data to template
		$_API_URL = "https://test-dca-mc.nmdca.net/wp-json/tribe/events/v1/events/" . $event_id;
		$response_data = api_request($_API_URL);
		
		// pass event data to the template
		$data = array(
			'event' => $response_data
		);
		// filename of template
		$filename = 'single-event.php'; 
		// full path to the template
		$fullTemplatePath = TEMPLATEPATH . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $filename;
		// return the template
		$returnTemplate = (file_exists($fullTemplatePath)) ? $fullTemplatePath : $plugindir . DIRECTORY_SEPARATOR . $themeFilesDir . DIRECTORY_SEPARATOR . $filename;
		// call function doCustomThemeRedirect
		doCustomThemeRedirect($returnTemplate, true, $data);
		return;
		
	}
	
}

/*
 *
 * Process theme redirect
 *
 */
// Function to process the redirect 
function doCustomThemeRedirect($path, $force = false, $data = array())
{
	global $post, $wp_query;
	// if there are post get data
	if (have_posts() || $force) {
		if (!empty($data)) extract($data);
		include($path);
		die();
	// else return error code
	} else {
		$wp_query->is_404 = true;
	}
}
// Add hook for the theme redirect
add_action('template_redirect', 'customThemeRedirect');

/*
 *
 * Register custom query vars
 *
 */
// function needed to allow custom rewrite rules 
function registerQueryVars($vars)
{
	// make vars publicly avaliable 
	$vars[] = 'custom_page';
	$vars[] = 'event_id';
	return $vars;
}
// add to filter
add_filter('query_vars', 'registerQueryVars');