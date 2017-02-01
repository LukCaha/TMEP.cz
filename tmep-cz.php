<?php
/*
Plugin Name: TMEP.cz widget
Plugin URI: http://www.lukascaha.net
Description: Widget pro zobrazení aktuálně naměřených hodnot teploty a vlhkosti z tmep.cz.
Author: Lukáš Caha
Version: 1.0
Author URI: http://www.lukascaha.net
*/


/**
 * Add TMEP.cz widget.
 */
class Tmep_CZ extends WP_Widget {

	/**
	 * Register widget.
	 */
	function __construct() {
		parent::__construct(
			'tmep', // Base ID
			'TMEP.cz', // Name
			array( 'description' => 'Widget pro zobrazení aktuálně naměřených hodnot tepploty a vlhkosti z tmep.cz' ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 */
	public function widget( $args, $instance ) {
		
		echo $args['before_widget'];
		
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		
		$tmepurl = $instance['tmepurl'];
		$re = "/http(s?):\/\/tmep\.cz\/vystup-json\.php\?id=[0-9]+&export_key=[a-z0-9]{10}/";

		if ( preg_match($re, $tmepurl) ) {

            $contextopt = array( "ssl"=>array( "verify_peer"=>false, "verify_peer_name"=>false ) );

            if ( $json = @file_get_contents($tmepurl, false, stream_context_create($contextopt)) ) {
            	
                $json_dec = json_decode($json);
				
				$teplota = $json_dec->{'teplota'};
				$vlhkost = $json_dec->{'vlhkost'};
				$cas = $json_dec->{'cas'};
				$domena = $json_dec->{'domena'};
				
				echo "<p class=\"tmep-temp\" style=\"text-align: center; font-size: 350%; line-height: 1em;\">";
				
                $t = str_replace(".", ",",sprintf("%0.1f",round($teplota,1)));
                echo $t." &deg;C";

				echo "</p>"; // tmep-temp
				echo "<p class=\"tmep-humdewp\" style=\"text-align: center\">";

	   			if ( $instance['displayhum'] == "on" && $vlhkost != NULL ) {
                	$v = str_replace(".", ",",sprintf("%0.1f",round($vlhkost,1)));
                	echo "Vlhkost: <span class=\"tmep-hum\" style=\"font-size: 150%\">".$v." %</span> RH";
				}
				
	   			if ( $instance['displaydewp'] == "on" && $vlhkost != NULL ) {
	   				if ( $instance['displayhum'] == "on" && $instance['displaydewp'] == "on" ) echo "<br>";
	   				$rb = (243.5*log(($vlhkost/100)*exp((17.67*$teplota)/(243.5+$teplota))))/(17.67-log(($vlhkost/100)*exp((17.67*$teplota)/(243.5+$teplota))));
                	$rb = str_replace(".", ",",sprintf("%0.1f",round($rb,1)));
                	echo "Rosný bod: <span class=\"tmep-dewp\" style=\"font-size: 150%\">".$rb." &deg;C</span>";
				}

				echo "</p>"; // tmep-humdewp

				if ( $instance['displaytime'] == "on" )
	                echo "<p class=\"tmep-time\" style=\"text-align: center\">Čas měření:&nbsp; ".date("G:i\&\\nb\sp; j.n.Y",strtotime($cas))."</p>";
					
				if ( $instance['displaylink'] == "on" )
                	echo "<p class=\"tmep-link\" style=\"text-align: right\"><a href=\"http://".$domena."/\" target=\"_blank\">Podrobné statistiky a grafy &raquo;</a></p>";
				
            } else {
            	echo "<p>Nepodařilo se načíst data.</p>";
			}
	
        } else {
        	echo "<p>Neplatná adresa.</p>";
		}
		
		echo $args['after_widget'];
		
	}

	/**
	 * Back-end widget form.
	 */
	public function form( $instance ) {
		
		//$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Aktuální teplota';
		$title = $instance['title'];
		$tmepurl = $instance['tmepurl'];
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tmepurl'); ?>"><?php _e('Adresa exportu posledního měření:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('tmepurl'); ?>" name="<?php echo $this->get_field_name('tmepurl'); ?>" type="text" value="<?php echo esc_attr( $tmepurl ); ?>">
		</p>
		<p>
			<fieldset style="border: 1px solid #ddd;padding: 0.35em 0.75em;">
			<legend>Zobrazit</legend>
				<input class="checkbox" id="<?php echo $this->get_field_id('displayhum'); ?>" name="<?php echo $this->get_field_name( 'displayhum'); ?>" type="checkbox" <?php checked( $instance['displayhum'], "on" ); ?>>
				<label for="<?php echo $this->get_field_id('displayhum'); ?>">Vlhkost <small>(pokud je měřena)</small></label>
				<br>
				<input class="checkbox" id="<?php echo $this->get_field_id('displaydewp'); ?>" name="<?php echo $this->get_field_name( 'displaydewp'); ?>" type="checkbox" <?php checked( $instance['displaydewp'], "on" ); ?>>
				<label for="<?php echo $this->get_field_id('displaydewp'); ?>">Rosný bod <small>(pokud je měřena vlhkost)</small></label>
				<br>
				<input class="checkbox" id="<?php echo $this->get_field_id('displaytime'); ?>" name="<?php echo $this->get_field_name( 'displaytime'); ?>" type="checkbox" <?php if (is_null($instance['displaytime'])) echo "checked=\"checked\""; else checked( $instance['displaytime'], "on" ); ?>>
				<label for="<?php echo $this->get_field_id('displaytime'); ?>">Čas měření</label>
				<br>
				<input class="checkbox" id="<?php echo $this->get_field_id('displaylink'); ?>" name="<?php echo $this->get_field_name( 'displaylink'); ?>" type="checkbox" <?php if (is_null($instance['displaylink'])) echo "checked=\"checked\""; else checked( $instance['displaylink'], "on" ); ?>>
				<label for="<?php echo $this->get_field_id('displaylink'); ?>">Odkaz na statistiky na tmep.cz</label>
			</fieldset>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 */
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : "";
		$instance['tmepurl'] = ( ! empty( $new_instance['tmepurl'] ) ) ? strip_tags( $new_instance['tmepurl'] ) : "";
		$instance['displayhum'] = ( ! empty( $new_instance['displayhum'] ) ) ? $new_instance['displayhum'] : "off";
		$instance['displaydewp'] = ( ! empty( $new_instance['displaydewp'] ) ) ? $new_instance['displaydewp'] : "off";
		$instance['displaytime'] = ( ! empty( $new_instance['displaytime'] ) ) ? $new_instance['displaytime'] : "off";
		$instance['displaylink'] = ( ! empty( $new_instance['displaylink'] ) ) ? $new_instance['displaylink'] : "off";;
		
		return $instance;
		
	}

} // class Tmep_CZ

function register_tmep_widget() {
    register_widget( 'Tmep_CZ' );
}
add_action( 'widgets_init', 'register_tmep_widget' );

?>