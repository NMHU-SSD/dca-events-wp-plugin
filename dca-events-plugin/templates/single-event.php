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

<main class="container">
	<header class="container-fluid">
		<?php 
			echo "<h1 >".$event->title ."</h1>";
		?>
	</header><!-- header -->

	<div class="container-fluid">
		
		<div class='row p-0 ms-0 ml-0 mt-5 mb-5'>
			
			<div class='col-12 col-md-6 p-0'>
				<?php 
				echo "<img src='" . $event->image->url . "'  style='min-height: 200px;  height: 100%; width: 100%; object-fit: cover;'   >";
				?>
			</div>
			
			<div class='col-12 col-md-6'>
				
				<?php 
		
				$output = "<h3>" . $event->venue->venue . "</h3>";
				$output .= "<p><b>Address: </b>" . $event->venue->address .", ".$event->venue->city.", ".$event->venue->state. ", ".$event->venue->zip. "</p>";
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
		
			<div class='col-12 mt-3'>
				<?php 
				echo "<p>" . $event->description . "</p>";
				?>
			</div>
			
		</div><!-- end row -->
		
	</div>
	
</main>
	

<?php
get_footer();