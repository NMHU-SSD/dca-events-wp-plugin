<?php 
/**
 * events.php
 * The template for displaying all events
 *
*/

get_header();

?>

<main class="container">
<header class="container-fluid">
	<?php 
		echo "<h1 >DCA Events</h1>";
	?>
</header><!-- header -->

<div class="container-fluid">

<?php

/*
 * events as list
*/

echo $data['events'];

?>
</div>
</main>

<?php
get_footer();
