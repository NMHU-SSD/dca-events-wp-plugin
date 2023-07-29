<?php 
/**
 * single-event.php
 * The template for displaying singe event
 *
*/

get_header();

//retrieve data
//echo var_dump($data);
$event = $data["event"];

?>
<!-- Start container -->
<main class="container">
	<header class="container-fluid">
		<?php 
			echo "<h1 >".$event->title ."</h1>";
		?>
	</header><!-- End header -->

	<div class="container-fluid">
		<!-- Start row -->
		<div class='row p-0 ms-0 ml-0 mt-5 mb-5'>
			<!-- Start 1st column -->
			<div class='col-12 col-md-6 p-0'>
				<?php 
				echo "<img src='" . $event->image->url . "'  style='min-height: 200px;  height: 100%; width: 100%; object-fit: cover;'   >";
				?>
			</div>
			<!-- Start 2nd column -->
			<div class='col-12 col-md-6'>
				
				<?php 
		
				$output = "<p class='mt-3'><b>" . $event->venue->venue . "</b></p>";
				$output .= "<p><b>Address: </b>" . $event->venue->address . "</p>";
				$d = formatEventDate($event->start_date);
				$t = formatEventTime($event->start_date);
				$output .= "<p><b>Date: </b>" . $d . "</p>";
				$output .= "<p><b>Time: </b>" . $t . "</p>";

				if ($event->cost == null) {
					$output .= "<p><b>Cost: </b> $0.00</p>";
				} else {
					$output .= "<p><b>Cost: </b>" . $event->cost . " </p>";
				}
				echo $output;
				?>
			
			</div>

			<!-- Start 3rd column -->
			<div class='col-12 mt-3'>
				<?php 
				echo "<p>" . $event->description . "</p>";
				?>
			</div> <!-- End 3rd column -->
			
		</div><!-- end row -->
		
	</div><!-- end row -->
	
</main><!-- end container -->
	

<?php
get_footer();