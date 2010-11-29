<?php
/*
 * Plugin Name: ENSO Graph Widget
 * Version: 1.0
 * Plugin URI: http://open.agroclimate.org/downloads/
 * Description: A small pie graph which displays the current ENSO Prediction from the IRI
 * Author: The Open AgroClimate Project
 * Author URI: http://open.agroclimate.org/
 * License: BSD Modified
 */

class ENSOGraphWidget extends WP_Widget {
	static function lookup_enso() {
		$enso_array = array();
		
		// Retrieve the data from IRI	
		$enso_uri = 'http://iri.columbia.edu/climate/ENSO/currentinfo/figure3.html';
		$raw_html = wp_remote_fopen( $enso_uri );
		
		// Parse the table with the prediction data
		$result = preg_match("/<tr><td>(?P<prediction_date>[JFMASOND]{3}\ [0-9]{4})<\/td><td>(?P<lanina>[0-9\. ]{1,4})%<\/td><td>(?P<neutral>[0-9\. ]{1,4})%<\/td><td>(?P<elnino>[0-9\. ]{1,4})%<\/td><\/tr>/", $raw_html, $parsed_data);
		
		// Return FALSE if any errors, otherwise return the $enso_array
		if( $result ):
			// If we get a match back, then store this information in the database as well as update the current timestamp.
			$enso_array['la_nina_prediction'] = floatval( $parsed_data['lanina'] );
			$enso_array['neutral_prediction'] = floatval( $parsed_data['neutral'] );
			$enso_array['el_nino_prediction'] = floatval( $parsed_data['elnino'] );
			
			// Guess the current phase based on the maximum value of the above
			$current_phase = array_search( max( $enso_array ), $enso_array );
			switch( substr( $current_phase, 0, 1 ) ) {
				case 'l': // La Nina Phase
					$enso_array['current_phase'] = __( 'La Ni&#241;a' );
					break;
				case 'n': // Neutral Phase
					$enso_array['current_phase'] = __( 'Neutral' );
					break;
				case 'e': // El Nino Phase
					$enso_array['current_phase'] = __( 'El Ni&#241;o' );
					break;
				default:
					$enso_array['current_phase'] = __( 'Unknown' );
					break;
			}
			
			// Find the current prediciton period ( localized )
			$month_list    = 'JFMAMJJASONDJF';
			$pred_month_index  = stripos( $month_list, substr( $parsed_data['prediction_date'], 0, 3 ) ) + 1;
			$current_period = array();
			for( $i=0; $i < 3; $i++ ) {
				$current_period[] = date_i18n( 'M', strtotime( ( ( $pred_month_index + $i ) % 12 ).'/1/'.date( 'Y' ) ) );
			}
			$enso_array['current_period'] = implode( '-', $current_period );
			
			// Set the last_updated to the current time	
			$enso_array['last_updated'] = strtotime( 'now' );
			return $enso_array;
		else:
			// Email the site administrator(s) to let them know there is a problem
			return false;
		endif;
	}

	function ENSOGraphWidget() {
		$enso_array = array();
		$new_data   = false; // Set to true if new data is retrieved
		
		// Should I do my checks here or not?
		if( $enso_array = get_option( 'oac_current_enso_data' ) ) {
			// Check the timestamp (if there is one) for freshness (2 weeks)
			if( strtotime( '+2 weeks', $enso_array['last_updated'] ) < strtotime( 'now' ) ) {
				// If there isn't an error getting the ENSO data, save it otherwise, keep our old data (the administrator got an email anyways)
				$new_enso_array = ENSOGraphWidget::lookup_enso();
				if( $new_enso_array != false ) {
					$enso_array = $new_enso_array;
					$new_data = true;
				}
			}
		} else {
			// Lookup the data, save it and move on
			$enso_array = ENSOGraphWidget::lookup_enso();
			if( $enso_array != false ) {
				$new_data = true;
			}
		} //if ( get_option ... )
		if( $new_data )
			update_option( 'oac_current_enso_data', $enso_array );

		if ( count( $enso_array ) != 0 ) {
			// Enqueue all the scripts necessary to render the pie chart
			wp_enqueue_script('');
			wp_enqueue_script('');
			wp_enqueue_script('');
			wp_enqueue_script('');

		}
		parent::WP_Widget( false, $name = 'ENSOGraphWidget' );
	}
	
	function widget( $args, $instance ) {
		extract( $args );
		$enso_array = get_option( 'oac_current_enso_data', false );
		echo $before_widget;
		echo $before_title.'ENSO Prediction'.$after_title; 
		
		if( $enso_array ) {
			
			echo 'Current Phase: <em>'.$enso_array['current_phase']."</em><br />\n";
			echo "Current Prediction Period:<br />\n<em>".$enso_array['current_period']."</em><br />\n";
			echo "<div id=\"enso_prediction_chart\"></div>\n";
		} else {
			echo "No data available.";
		}
		echo $after_widget;
	}
} // class ENSOGraphWidget

function ENSOGraphInit() {
	register_widget( 'ENSOGraphWidget' );

add_action( 'widgets_init', 'ENSOGraphInit' );
?>
