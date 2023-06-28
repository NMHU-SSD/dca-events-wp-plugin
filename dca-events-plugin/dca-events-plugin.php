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
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );
	
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
	
	// check options and validate - not valid will result in NULL
	$_CURR_DAY_OPT = filter_var($atts['current-day'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_CURR_MONTH_OPT = filter_var($atts['current-month'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	$_DATE_RANGE_OPT = filter_var($atts['date-range'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	
	
	//set correct timezone and set/format range dates
	date_default_timezone_set('America/Denver');
	$_DATE_RANGE_START = ($atts['range-start'] == NULL) ? date("m-d-Y") : reformatDate($atts['range-start']) ;
	$_DATE_RANGE_END = ($atts['range-end'] == NULL) ? date("m-d-Y") : reformatDate($atts['range-end']);
	
	// set default limit if null - else typecast shortcode val
	$_LIMIT_OPT = ($atts['limit'] == NULL) ? 10 : intval($atts['limit']);
	
	//if null get events from all venues or set default in settings menu? (todo: get id from settings dropdown)
	//using hardcoded id for now
	$_SITE_ID = ($atts['id'] == NULL) ? 108 : intval($atts['id']);
	
	
	
	//get api results
	//docs: https://developer.wordpress.org/rest-api/using-the-rest-api/
	//sample: http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?page=10&start_date=2023-06-26&end_date=023-06-29&venue=108
	
	//start api string
	$_API_URL = "http://nmdcamediadev.wpengine.com//wp-json/tribe/events/v1/events/?";
	
	//set limit in api url
	$_API_URL .= "page=".$_LIMIT_OPT;
	
	//set venue in api url
	$_API_URL .= "&venue=".$_SITE_ID;
	
	
	
	//set dates in based on options
	if ($_CURR_DAY_OPT == true) 
	{
		//get_events_today
		//get todays date ajd format for url
		
		
	} 
	else if ($_CURR_MONTH_OPT == true) 
	{
		//get_events_by_current_month
		//get first and last day of this month as range and format for url
		
		
	}  else if ($_DATE_RANGE_OPT == true)
	{
		//get_events_by_range
		//format for url
		
	} 
	else
	{
		//default - get_events_by_current_month
		
	} 
	
	
	//make API request
	
	
	//return results (html output)

}



//register shortcode
add_shortcode('dca_events', 'dca_events_plugin');


/*
*
*	Plugin Settings Page
*
*/


// @Anita Your plugin settings page code goes here: