<?php
/**
 * Plugin Name: Luminate Top Participants
 * Plugin URI: https://wordpress.org/plugins/luminate-top-participants/
 * Description:  This plugin makes showing top participants and top teams on your wordpress website easy using Luminate Online's API.
 * Author: rchiriboga
 * Author URI: http://richardchiriboga.com/
 * Version: 1.0.0
 * Text Domain: custom-post-type-permalinks
 * License: GPL2 or later
 * 
 *
 * @package Luminate_Top_Participants
 * @version 1.0.0
 */

 // If this file is called directly, abort.
 if ( ! defined( 'ABSPATH' ) ) exit;

 define( 'PLUGIN_NAME_VERSION', '1.0.0' );


//require_once(dirname (__FILE__) . '/includes/settings.php');



/**
 * Create the shortcode for the participants
**/
add_shortcode( 'top_participants', 'ltp_display_participants_shortcode' );
function ltp_display_participants_shortcode( $atts, $settings )
{
  // get settings and use them to populate values
  $option_name            = get_option( 'ltp_settings' );
  $luminate_host          = $option_name[ltp_text_field_0];
  $luminate_api_key       = $option_name[ltp_text_field_1];
  $luminate_api_username  = $option_name[ltp_text_field_2];
  $luminate_api_password  = $option_name[ltp_text_field_3];

	// Default short code attributes
	$atts = shortcode_atts( array(
		'type'        => '',
		'output'      => 'table',
		'class'       => 'table table-condensed table-bordered table-striped',
		'id'          => 'ltp-participants',
		'event_id'    => '',
        'title'       => '',
		'limit'       => '',
        'filter_text' =>'',
	), $atts, 'ltp' );

	$tlp_type      = sanitize_text_field( strtolower($atts['type']) );
	$tlp_output    = sanitize_text_field( $atts['output'] );
	$tlp_class     = sanitize_text_field( $atts['class'] );
	$tlp_id        = sanitize_text_field( $atts['id'] );
	$tlp_eventid   = sanitize_text_field( $atts['event_id'] );
    $tlp_title     = sanitize_text_field( $atts['title'] );
	$tlp_limit     = sanitize_text_field( $atts['limit'] );
    $tlp_limit     = ($atts['limit'] !='' ? $atts['limit'] : 10); // returns true
    $tlp_filter    = ($atts['filter_text'] !='' ? $atts['filter_text'] : ''); // returns true


	require_once( 'ConvioOpenAPI.php' );
	// Base Configuration
	$convioAPI 			= new ConvioOpenAPI;
	$convioAPI->host 	= $luminate_host;
	$convioAPI->api_key	= $luminate_api_key;
	// Authentication Configuration
	$convioAPI->login_name     = $luminate_api_username;
	$convioAPI->login_password = $luminate_api_password;
	// Choose Format (If not set, a PHP object will be returned)
	$convioAPI->response_format = 'json'; //xml



  switch ($tlp_type) {
      case "participants":
          $method = 'getTopParticipantsData';
          $params = array('method'=> $method,'fr_id' => $tlp_eventid, 'limit' => $tlp_limit);
          break;
      case "teams":
          $method = 'getTopTeamsData';
          $params = array('method'=> $method,'fr_id' => $tlp_eventid, 'limit' => $tlp_limit);
          break;
      case "crew":
          $fname = '%%';
          $lname = '%';
          $method = 'getParticipants';
          $params = array('method'=> $method,'fr_id' => $tlp_eventid, 'limit' => $tlp_limit);
          array_push($params['first_name']= $fname);
          array_push($params['last_name']= $lname);
          array_push($params['list_filter_column']='reg.participation_id');
          array_push($params['list_filter_text']=$tlp_filter);
          array_push($params['list_sort_column']="total");
          array_push($params['list_ascending']='false');
          break;
      case "walkers":
          $fname = '%%';
          $lname = '%';
          $method = 'getParticipants';
          $params = array('method'=> $method,'fr_id' => $tlp_eventid, 'limit' => $tlp_limit);
          array_push($params['first_name']= $fname);
          array_push($params['last_name']= $lname);
          array_push($params['list_filter_column']='reg.participation_id');
          array_push($params['list_filter_text']=$tlp_filter);
          array_push($params['list_sort_column']="total");
          array_push($params['list_ascending']='false');
          break;
      default:
          $method = 'getTopParticipantsData';
          $params = array('method'=> $method,'fr_id' => $tlp_eventid, 'limit' => $tlp_limit);
  }

  // call the class and push the data
	$response = $convioAPI->call('CRTeamraiserAPI',$params);
	//print_r($response);
	$data = json_decode($response, true);

	// get right into the data we need to get the real array!
  switch ($tlp_type) {
      case "participants":
          $items = $data['getTopParticipantsDataResponse']["teamraiserData"];
          break;
      case "teams":
          $items = $data['getTopTeamsDataResponse']["teamraiserData"];
          break;
      case "crew":
          $items = $data['getParticipantsResponse']["participant"];
          break;
      case "walkers":
          $items = $data['getParticipantsResponse']["participant"];
          break;
      default:

  }

//print_r($items);
/*************************
- CREATE THE OUTPUT AND PUSH
*************************/
  if($tlp_output == "table")
  {
    // if there's a title then show it.
    if($tlp_title){
      $data_output = '<h2 class="'.$tlp_type.'_header">'.$tlp_title.'</h2>';
    }
    $data_output .= '<table class="'.$tlp_class.'" id="'.$tlp_id.'">';
    // loop through the results
    $counter = 0; $counterlimit = ($tlp_limit -1); // because the array starts at 0
    foreach($items as $item){
      if( ($tlp_type != 'crew') && ($tlp_type != 'walkers') )
      {
        $data_output .= '<tr><td>'.$item['name'].'</td><td>'.$item['total'].'</td></tr>';
        if ($counter == $counterlimit){ break;}
        $counter++;
      }
      else{
        if($item['personalPagePrivate'] !== 'true'){
            $name = $item['name']['first'].' '.$item['name']['last'];
            setlocale(LC_MONETARY, 'en_US.UTF-8');
            $dirtymoney = substr_replace($item['amountRaised'], '.' . substr($item['amountRaised'], -2), -2);
            $money = money_format('%.2n',$dirtymoney);
            $data_output .= '<tr><td>'.$name.'</td><td>'.$money.'</td></tr>';
            if ($counter == $counterlimit){ break;}
            $counter++;
        }
      }
    }
    $data_output .= '</table>';
  }
  // if they want an unordered list
  else if($tlp_output == "list")
  {
    // if there's a title then show it.
    if($tlp_title){
      $data_output = '<h2 class="participant_header">'.$tlp_title.'</h2>';
    }
    $data_output .= '<ul class="'.$tlp_class.'" id="'.$tlp_id.'">';
    // loop through the results
    $counter = 0; $counterlimit = ($tlp_limit -1); // because the array starts at 0
    foreach($items as $item){
      if( ($tlp_type != 'crew') && ($tlp_type != 'walkers') )
      {
        $data_output .= '<li>'.$item['name'].'<span>'.$item['total'].'</span></li>';
        if ($counter == $counterlimit){ break;}
        $counter++;
      }
      else{
          
        $name = $item['name']['first'].' '.$item['name']['last'];
        setlocale(LC_MONETARY, 'en_US.UTF-8');
        $dirtymoney = substr_replace($item['amountRaised'], '.' . substr($item['amountRaised'], -2), -2);
        $money = money_format('%.2n',$dirtymoney);
        $data_output .= '<li>'.$name.'<span>'.$money.'</span></li>';
        if ($counter == $counterlimit){ break;}
        $counter++;
      }
    }
    $data_output .= '</ul>';
  }

  # RETURN THE DATA
	return $data_output;




	// add script in the footer on pages that include this Plugin
	//wp_enqueue_script( 'luminate-js' );

}



// add_action( 'wp_enqueue_scripts', 'luminate_api_js' );
// function luminate_api_js() {
// 	wp_register_script( 'luminate-js', plugins_url( '/js/luminate_extend_avon39.js' , __FILE__ ), array('jquery'), '1.0.0', true );
// }

add_action( 'admin_menu', 'ltp_add_admin_menu' );
add_action( 'admin_init', 'ltp_settings_init' );


function ltp_add_admin_menu(  ) {

	add_menu_page( 'Luminate Top Participants', 'Luminate Top Participants', 'manage_options', 'luminate_top_participants', 'ltp_options_page' );

}


function ltp_settings_init(  ) {

	register_setting( 'pluginPage', 'ltp_settings' );

	add_settings_section(
		'ltp_pluginPage_section',
		__( 'Luminate Top Participants', 'wordpress' ),
		'ltp_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'ltp_text_field_0',
		__( 'Luminate Host URL<br/><small>ex: (secure.yoursite.com)</small>', 'wordpress' ),
		'ltp_text_field_0_render',
		'pluginPage',
		'ltp_pluginPage_section'
	);

	add_settings_field(
		'ltp_text_field_1',
		__( 'Luminate API Key', 'wordpress' ),
		'ltp_text_field_1_render',
		'pluginPage',
		'ltp_pluginPage_section'
	);

	add_settings_field(
		'ltp_text_field_2',
		__( 'Luminate API Username', 'wordpress' ),
		'ltp_text_field_2_render',
		'pluginPage',
		'ltp_pluginPage_section'
	);

  add_settings_field(
		'ltp_text_field_3',
		__( 'Luminate API Password', 'wordpress' ),
		'ltp_text_field_3_render',
		'pluginPage',
		'ltp_pluginPage_section'
	);


}


function ltp_text_field_0_render(  ) {

	$options = get_option( 'ltp_settings' );
	?>
	<input type='text' name='ltp_settings[ltp_text_field_0]' value='<?php echo $options['ltp_text_field_0']; ?>'>
	<?php

}


function ltp_text_field_1_render(  ) {

	$options = get_option( 'ltp_settings' );
	?>
	<input type='text' name='ltp_settings[ltp_text_field_1]' value='<?php echo $options['ltp_text_field_1']; ?>'>
	<?php

}


function ltp_text_field_2_render(  ) {

	$options = get_option( 'ltp_settings' );
	?>
	<input type='text' name='ltp_settings[ltp_text_field_2]' value='<?php echo $options['ltp_text_field_2']; ?>'>
	<?php

}

function ltp_text_field_3_render(  ) {

	$options = get_option( 'ltp_settings' );
	?>
	<input type='text' name='ltp_settings[ltp_text_field_3]' value='<?php echo $options['ltp_text_field_3']; ?>'>
	<?php

}

function ltp_settings_section_callback(  ) {

	echo __( 'Please enter the following Luminate Information below', 'wordpress' );

}


function ltp_options_page(  ) {

	?>
	<form action='options.php' method='post'>

		<!-- <h2>Luminate Top Participants</h2> -->

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}

?>
