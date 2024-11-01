<?php 
/**
 * Newsletter class
 *
 * @author   Magazine3
 * @category Admin
 * @path     admin_section/newsletter
 * @Version 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Viatorwp_ads_newsletter {
        
        function __construct () {
                
                add_filter( 'viatorwp_localize_filter',array($this,'viatorwp_add_localize_footer_data'),10,2);
                add_action('wp_ajax_viatorwp_subscribe_to_news_letter', array($this, 'viatorwp_subscribe_to_news_letter'));
        }
        
        function viatorwp_subscribe_to_news_letter(){

                if ( ! isset( $_POST['viatorwp_newsletter_security_nonce'] ) ){
                    return; 
                }
                if ( !wp_verify_nonce( $_POST['viatorwp_newsletter_security_nonce'], 'vas_ajax_check_nonce' ) ){
                   return;  
                }
                                
                $name    = sanitize_text_field($_POST['name']);
                $email   = sanitize_text_field($_POST['email']);
                $website = sanitize_text_field($_POST['website']);
                
                if($email){
                        
                    $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';

                    $api_params = array(
                        'name'    => $name,
                        'email'   => $email,
                        'website' => $website,
                        'type'    => 'viatorwp'
                    );
                    
                    $response = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
                    $response = wp_remote_retrieve_body( $response );                    
                    echo $response;

                }else{
                        echo 'Email id required';                        
                }                        

                wp_die();
        }
                
        function viatorwp_add_localize_footer_data($object, $object_name){
            
        $dismissed = explode (',', get_user_meta (wp_get_current_user()->ID, 'dismissed_wp_pointers', true));
        $do_tour   = !in_array ('viatorwp_subscribe_pointer', $dismissed);
        if ($do_tour) {
                wp_enqueue_style ('wp-pointer');
                wp_enqueue_script ('wp-pointer');                                               
        }
                        
        if($object_name == 'viator_localize_admin_data'){
                        
                global $current_user;                
                $tour     = array ();
                $tab      = isset($_GET['tab']) ? esc_attr($_GET['tab']) : '';                   
                
                if (!array_key_exists($tab, $tour)) {                
                                                                                                        
                        $object['do_tour']            = $do_tour;        
                        $object['get_home_url']       = get_home_url();                
                        $object['current_user_email'] = $current_user->user_email;                
                        $object['current_user_name']  = $current_user->display_name;        
                        $object['displayID']          = '#menu-posts-viatorwp';                        
                        $object['button1']            = esc_html__('No Thanks', 'viator-integration-for-wp');
                        $object['button2']            = false;
                        $object['function_name']      = '';                        
                }
                                                                                                                                                                    
        }
        return $object;
         
    }
       
}
$viatorwp_ads_newsletter = new Viatorwp_ads_newsletter();
?>