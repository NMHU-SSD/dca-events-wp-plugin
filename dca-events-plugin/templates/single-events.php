<?php
/**
 * single-event.php
 * The template for displaying a single event
 *
 */

get_header();

?>


<?php

/*
 * event content
 */

// Still cannot disply information but coded it to try and display when avaliable
var_dump($data);

/*

// variable for the single event
$event = $data['event'];

// create row
$output .= "<div class='row p-0 ms-0 ml-0 mt-5 mb-5'>";
// create 1st column
$output .= "<div class='col-12 col-md-6 p-0'>";
$output .= "<img src='" . $event->image->url . "'  style='min-height: 200px; height: 100%; width: 100%; object-fit: cover;'   >";
// end of 1st column
$output .= "</div>";
// start 2nd column
$output .= "<div class='col-12 col-md-6'>";
$output .= "<span class='lead text-warning'>" . $event->venue->venue . "</span>";
$output .= "<h3 class='text-secondary'>" . $event->title . "</h3>";
// end of 2nd column
$output .= "</div>";
$output .= "<div class='col-12 mt-3'>";
$output .= "<p>" . $event->description . "</p>";
$output .= "<p class='mt-3'><b>Address: </b>" . $event->venue->address . "</p>";
$d = formatEventDate($event->start_date);
$t = formatEventTime($event->start_date);
$output .= "<p><b>Date: </b>" . $d . "</p>";
$output .= "<p><b>Time: </b>" . $t . "</p>";
if ($event->cost == null) {
    $output .= "<p><b>Cost: </b> $0.00</p>";
} else {
    $event .= "<p><b>Cost: </b>" . $event->cost . " </p>";
}
// end of rows
$output .= "</div>";

// return output
return $output;

*/

?>


<?php
get_footer();