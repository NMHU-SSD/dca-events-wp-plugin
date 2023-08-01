<?php
/**
 * Plugin Name:     DCA Events Plugin
 * Plugin URI:      https://github.com/NMHU-SSD/dca-events-wp-plugin
 * Description:     New Mexico Department of Cultural Affairs Events
 * Author:          NMHU SSD intern Anita Martin & faculty advisor Rianne Trujillo
 * Author URI:      https://github.com/NMHU-SSD
 * Text Domain:     dca-events-plugin
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         DCA_Events_Plugin
 */


/*------------------------------------
 *
 *	GLOBALS
 *
 */

//base url for API requests
$GLOBALS['API_BASE_URL'] = "https://test-dca-mc.nmdca.net";

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
function dca_events_shortcode($atts = [])
{
	// normalize attribute keys, and lowercase 
	$atts = array_change_key_case((array) $atts, CASE_LOWER);

	// Gets shortcode vals
	$atts = shortcode_atts(
		array(
			'site' => NULL, //venue id for museum
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

	// Get limit from shortcode limit option else get default in plugin settings menu
	$_LIMIT_OPT = ($atts['limit'] != NULL) ? intval($atts['limit']) : intval(get_option('dca_events_plugin_options')['dca_events_plugin_option_limit']);

	
	//if shortcode limit option and settings menu null, set default to 10
	if ( $atts['limit'] == NULL && get_option('dca_events_plugin_options')['dca_events_plugin_option_limit'] == NULL ) {
		$_LIMIT_OPT = 10;
	}
	

	// Get events from shortcode site option else get default from plugin settings menu
	$_SITE_ID = ($atts['site'] != NULL) ? intval($atts['site']) : (get_option('dca_events_plugin_options')['dca_events_plugin_option_venue'] == "" ? NULL : intval(get_option('dca_events_plugin_options')['dca_events_plugin_option_venue']) );
	
	

	// Get API results
	// docs: https://developer.wordpress.org/rest-api/using-the-rest-api/
	// sample: https://test-dca-mc.nmdca.net/wp-json/tribe/events/v1/events/?per_page=10&start_date=2023-06-26&end_date=2023-06-29&venue=108

	// Start API string
	$_API_URL = $GLOBALS['API_BASE_URL'] . "/wp-json/tribe/events/v1/events/?";

		
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

	
	//check is site id is set before adding to api endpoint
	//if null or empty string - shows events for all sites
	if ($_SITE_ID != NULL ){
		// Set venue in API URL
		$_API_URL .= "&venue=" . $_SITE_ID;
	}
	

	// Make the API request
	$response_data = api_request($_API_URL);

	// Start a container
	$output = '<div class="container-fluid p-0 ">';

	// If response data and events are empty
	if ($response_data == null || $response_data->events == []) {
		// display message to user
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
add_shortcode('dca_events', 'dca_events_shortcode');

/*--------------------------------------------
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


/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function dca_events_plugin_settings_init() {
	
	// Register a new setting for "dca_events_plugin" page.
	register_setting( 'dca_events_plugin', 'dca_events_plugin_options' );

	// Register a new section in the "dca_events_plugin" page.
	add_settings_section(
		'dca_events_plugin_section_dev_settings',
		__( 'Plugin Events Page Settings', 'dca_events_plugin' ), 'dca_events_plugin_section_dev_settings_callback',
		'dca_events_plugin'
	);

	// Register a new field in the "dca_events_plugin_section_dev_settings" section, inside the "dca_events_plugin" page.
	add_settings_field(
		'dca_events_plugin_option_venue', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'DCA Site ID', 'dca_events_plugin' ),
		'dca_events_plugin_option_venue_cb',
		'dca_events_plugin',
		'dca_events_plugin_section_dev_settings',
		array(
			'label_for'         => 'dca_events_plugin_option_venue',
			'class'             => 'dca_events_plugin_row',
		)
	);
	
	// Register a new field in the "dca_plugin_section_dev_settings" section, inside the "dca_plugin" page.
	add_settings_field(
		'dca_events_plugin_option_limit', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Limit', 'dca_events_plugin' ),
		'dca_events_plugin_option_limit_cb',
		'dca_events_plugin',
		'dca_events_plugin_section_dev_settings',
		array(
			'label_for'         => 'dca_events_plugin_option_limit',
			'class'             => 'dca_events_plugin_row',
		)
	);
}

/**
 * Register our dca_events_plugin_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'dca_events_plugin_settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function dca_events_plugin_section_dev_settings_callback( $args ) {
	?>
	
	<p class="lead">The events page will display the limited number of events based on the selected site id. </p>									
	
	<p>Preview events page: <em><?php echo "<a target='_blank' href='".site_url() . "/events"."'>". site_url() . "/events"."</a>"; ?></em></p>
	
	<b id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Set the default values in the following options: ', 'dca_events_plugin' ); ?></b>
	<?php
}


/**
 *  Field callback functions
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */


function dca_events_plugin_option_venue_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'dca_events_plugin_options' );
	
	// API URL for venues
	$url = $GLOBALS['API_BASE_URL'] . "/wp-json/tribe/events/v1/venues?per_page=100";
	$response_data = api_request($url);
	
	?>
	
	<div class="form-group row">
		<div class="col-12">
			
			<select class="form-control form-control-lg" name="dca_events_plugin_options[<?php echo esc_attr( $args['label_for'] ); ?>]" id="<?php echo esc_attr( $args['label_for'] ); ?>"> ?>
				
				<?php
				
				$all = (isset($options[ $args['label_for'] ]) && $options[ $args['label_for'] ] == NULL )? 'selected' : '';
				
				?>
				<option value="" <?php echo $all; ?> > 
					All Sites
				</option>
				
				<?php foreach ($response_data->venues as $venue) { ?>
					<?php
					$selected = (isset($options[ $args['label_for'] ]) && $options[ $args['label_for'] ] == $venue->id) ? 'selected' : ''; ?>
					<option value="<?php echo $venue->id ?>" <?php echo $selected; ?> > 
						<?php echo $venue->id . " - " . $venue->venue ?>
					</option>
				<?php } ?>
			</select>
		</div>
	</div>
	
	<p class="description"> 
		<?php  esc_html_e( 'Defines the default site(s) to display events for.', 'dca_events_plugin' ); ?>
	</p>
	
	<?php
	
		if ($options[ $args['label_for'] ] == NULL){
			?>
			<p class="description">Shortcode Usage Example: <small>[dca_events]</small> will display events for all sites.</p>
			<?php
		} else {
			?>
			<p class="description">Shortcode Usage Example: <small>[dca_events site="<?php echo $options[ $args['label_for'] ]; ?>"]</small> shortcode will display list of events for the selected site.</p>
			<?php
		}
	?>
	
	
	<?php
}


function dca_events_plugin_option_limit_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'dca_events_plugin_options' );

	$val = (isset($options[ $args['label_for'] ]) && $options[ $args['label_for'] ] == NULL ) ?  10 : $options[ $args['label_for'] ];
	
	?>
	
	<div class="form-group row">
		<div class="col-3">
			<input class="form-control" type="number" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="dca_events_plugin_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
	       min="1" max="100" value="<?php echo $val; ?>" placeholder="enter number">
		</div>
	</div>
	
	<p class="description">
		<?php esc_html_e( 'Defines the number of events to display. ', 'dca_events_plugin' ); ?>
	</p>
	
	<?php
}

/**
 * Add the top level menu page.
 */
function dca_events_plugin_options_page() {
	add_menu_page(
		'DCA Events Plugin',
		'DCA Events Plugin Settings',
		'manage_options',
		'dca_events_plugin',
		'dca_events_plugin_options_page_html'
	);
}


/**
 * Register our dca_events_plugin_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'dca_events_plugin_options_page' );


/**
 * Top level menu callback function
 */
function dca_events_plugin_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'dca_events_plugin_messages', 'dca_events_plugin_message', __( 'Settings Saved', 'dca_events_plugin' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'dca_events_plugin_messages' );
	?>
	
	<div class="container">
		<h1 class="display-1 mb-3"><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<h2>Plugin Shortcode</h2>
		<p class="lead">Using the shortcode will display limited number of events by site id or date range.</p>			
			
		<b>Shortcode Options:</b>
		<p>Create a post or page using the [dca_events *options] shortcode with avaliable options.</p>	
		<table class="table table-sm table-bordered "  >
			<thead class="table-light">
				<tr>
					<th scope="col">OPTION</th>
					<th scope="col">DESCRIPTION</th>
					<th scope="col">FORMAT</th>
				</tr>
			</thead>
			<tbody class="table-group-divider">
			<tr>
		        <th scope="row">site</th>
				<td>returns events for a specific site ID (default value can be set in the event page settings dropdown)</td>
				<td>integer value</td>
			</tr>
			<tr>
				<th scope="row">today</th>
				<td>returns events for today's date</td>
				<td>TRUE or FALSE</td>
			</tr>
			<tr>
				<th scope="row">current-month</th>
				<td>returns events for the current month</td>
				<td>TRUE or FALSE</td>
			</tr>
			<tr>
				<th scope="row">date-range</th>
				<td>return events for specific date range</td>
				<td>TRUE or FALSE</td>
			</tr>
			<tr>
				<th scope="row">range-start</th>
				<td>if date-range is true, defines a specific start date</td>
				<td> YYYY-MM-DD</td> 
			</tr>
			<tr>
				<th scope="row">range-end</th>
				<td>if date-range is true, defines a specific end date</td>
				<td>YYYY-MM-DD</td>
			</tr>
			<tr>
				<th scope="row">limit</th>
				<td>returns the number of events to display (default value of 10 can be overridden in the event page settings limit input field)</td>
				<td>any integer value</td>
			</tr>
			</tbody>
		</table>
		
		<b>Shortcode Usage Examples:</b>
		<p class="mb-0"><small>[dca_events site="120" today="true"]</small> will return the default number of events (10) with today's date for the site with an id of 120 (New Mexico Museum of Art)</p>
	
		<p class="mb-0"><small>[dca_events limit="7" current-month="true"]</small> will return 7 events for the current month</p>
		
		<p><small>[dca_events limit="20" date-range="true" range-start="2023-07-19" range-end="2023-07-23"]</small> will return a maximum of 20 events between July 19, 2023 and July 23, 2023.</p>
		
		<hr class="mt-4 mb-4">
		
		
		<form action="options.php" method="post" >
			<?php
			// output security fields for the registered setting "dca_plugin"
			settings_fields( 'dca_events_plugin' );
			// output setting sections and their fields
			// (sections are registered for "dca_plugin", each field is registered to a specific section)
			do_settings_sections( 'dca_events_plugin' );
			// output save settings button
			//submit_button( 'Save Settings' ,  "btn btn-secondary" ); 
			?>
			<input type="submit" name="submit" id="submit" class="btn btn-secondary" value="Save Settings">
			
		</form>
		
	</div>
	<?php
}


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
			'events' => dca_events_shortcode()
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