<?php
/*
Plugin Name: BP Groups RSS Feed Tab
Plugin URI: http://cosydale.com/
Description: Give ability for group admins to display any external RSS feed into a dedicated group tab "RSS".
Version: 1.0
Author: slaFFik, Valant
Author URI: http://cosydale.com/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

add_action( 'bp_init', 'bpgrft_load' );
function bpgrft_load(){
    if (!is_admin()){
        require ( dirname(__File__) . '/bpgrft-class.php');
        load_plugin_textdomain( 'bpgrft', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
    }
}

// load scripts on appropriate pages
add_action('wp_enqueue_scripts', 'bpgrft_css');
function bpgrft_css(){
    if (is_admin()) return;
    if(bp_is_active('groups'))
        wp_enqueue_style('BPGRFT_CSS', plugins_url('_inc/group-rss-feed-tab.css', __File__));
}

?>
