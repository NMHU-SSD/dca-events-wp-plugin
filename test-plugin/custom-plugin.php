<?php
/**
 * Plugin Name: DCA Events Plugin Settings
 * Plugin URI: https://www.nhccnm.org/events/feed/
 * Description: In-Testing stage
 * Version: 0.1
 * Author: Anita
 * Author URI: https://www.nhccnm.org/events/feed/
 **/

function dca_events_plugin($dca_atts)
{
	extract(
		$dca_atts = shortcode_atts(
			array(
				'current' => '', // default - current month or day
				'limit' => 5 // default - num of events to display (for now 5 if empty)
			),
			$dca_atts
		)
	);

	$feed_setting = get_option('rss_events_page_option_name')['rss_feed_0'];
	$get_feed = file_get_contents($feed_setting);
	$xml = simplexml_load_string($get_feed);

	// start div box
	$div_box = '<div class="dca-event-box">';

	foreach ($xml->children() as $events) {
		$div_box .= "<h1>" . $events->title . "<br> " . "</h1>";
		$div_box .= "<h5>" . "Link: " . $events->link . "<br> " . "</h5>";
		$div_box .= "<h5>" . $events->description . "<br>" . "</h5>";

		// check the value of current - day or month
		$user_curr = ($dca_atts['current']);
		$div_box .= "<h5>" . "Current: " . $user_curr  ."<br>" . "</h5>";

		// check the value of limit - number
		$maxevents = ($dca_atts['limit']);
		$div_box .= "<h5>" . " Limit: " . $maxevents . "<br>" . "</h5>";

		// check the current date
		$currentDate = date('m-d-Y');
		$div_box .= "<h5>" . "Current date: " . $currentDate . "<br>" . "</h5>";

		// check the limit output for the num of event to display
		foreach (new LimitIterator($events->item, 0, $maxevents) as $itm) {
			$link_title = $itm->title;
			$link_date = $itm->pubDate;
			$link_description = $itm->description;
			
			$curr_month = idate('m');
			$curr_day = idate('d');
			$timestamp_month = idate('m', $xml->$events->item->pubDate);
			$timestamp_day = idate('d', $xml->$events->item->pubDate);
			
			// If current month equals timestamp month do the following
			if ($user_curr == 'month' && $curr_month == $timestamp_month) {
				$div_box .= "<h4>" . "Current Month Events " . "<br>" . "</h4>";
				$div_box .= "<h4>" . "Title: " . $link_title . "<br>" . "</h4>";
				$div_box .= "<p>" . $link_date . "<br>" . "</p>";
				$div_box .= "<p>" . "Description: " . $link_description . "<br>" . "</p>";
			}
			// default: 
			// else if current day equals timestamp day & current month equals timestamp month
			// do the following
			elseif ($user_curr == 'date' && $curr_day == $timestamp_day && $curr_month == $timestamp_month)
			{
				$div_box .= "<h4>" . "Current Date Events " . "<br>" . "</h4>";
				$div_box .= "<h4>" . "Title: " . $link_title . "<br>" . "</h4>";
				$div_box .= "<p>" . $link_date . "<br>" . "</p>";
				$div_box .= "<p>" . "Description: " . $link_description . "<br>" . "</p>";
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
							<p></p>
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