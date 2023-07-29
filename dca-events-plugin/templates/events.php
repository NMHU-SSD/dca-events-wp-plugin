<?php
/**
 * events.php
 * The template for displaying all events
 *
 */

get_header();

?>
<!-- Start container -->
<main class="container">

	<!-- Start header container -->
<header class="container-fluid">
	<?php
	echo "<h1 >DCA Events</h1>";
	?>
	<!-- End header container -->
</header>

<div class="container-fluid">

<?php

/*
 * events as list
 */
echo $data["events"];

?>


<?php
get_footer();