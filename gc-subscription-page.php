<?php
/*
Plugin Name: Gc Subscription Page
Plugin URI: http://subscription-page.com/
Description: A plugin to add a subscription page template.
Version: 1.0
Author: GC Plugins 
Author URI: http://graspcorn.com/
License: GPL2
*/

// Function to execute on plugin activation
function subscription_page_activate() {
    // Activation code here...
}
register_activation_hook(__FILE__, 'subscription_page_activate');

// Function to execute on plugin deactivation
function subscription_page_deactivate() {
    // Deactivation code here...
}
register_deactivation_hook(__FILE__, 'subscription_page_deactivate');


function enqueue_custom_js() {
	$random_version = mt_rand();
	// Enqueue the script with the random version number
	wp_enqueue_script('custommer-need-js', plugin_dir_url(__FILE__) . 'gc-customer-need.js', array('jquery'), $random_version, true);
	wp_enqueue_script('subsription-js', plugin_dir_url(__FILE__) . 'js/index.js', array('jquery'), $random_version, true);
	wp_enqueue_script('subscription-jquery', 'https://code.jquery.com/jquery-3.6.0.min.js',array(), $random_version,true );
    
	
	wp_localize_script( 'custommer-need-js', 'myAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'my-ajax-nonce' )
    ));
    // Enqueue the CSS file with the random version number
    wp_enqueue_style('customer-need-css', plugin_dir_url(__FILE__) . 'css/style.css', array(), $random_version);
}

add_action('wp_enqueue_scripts', 'enqueue_custom_js');


// Add page template to the list of templates
function subscription_page_template($templates) {
    $templates['template-subscription-page.php'] = 'Subscription Page';
    $templates['template-customer-need.php'] = 'Customer Need';
    $templates['template-text-subscription.php'] = ' Test Subscription Need';
    return $templates;
}
add_filter('theme_page_templates', 'subscription_page_template');


// Ensure WordPress recognizes the  template
function subscription_page_redirect_template($template) {
    if (is_page_template('template-subscription-page.php')) {
        $template = plugin_dir_path(__FILE__) . 'template-subscription-page.php';
    }
    elseif (is_page_template('template-customer-need.php')) {
        $template = plugin_dir_path(__FILE__) . 'template-customer-need.php';
    }
    elseif (is_page_template('template-text-subscription.php')) {
        $template = plugin_dir_path(__FILE__) . 'template-text-subscription.php';
    }
    return $template;
}
add_filter('template_include', 'subscription_page_redirect_template');



////

// add_action('wp_ajax_create_payment_intent', 'create_payment_intent');//
// add_action('wp_ajax_nopriv_create_payment_intent', 'create_payment_intent');//

// function create_payment_intent() {
//     check_ajax_referer('wp_rest', '_wpnonce');

//     \Stripe\Stripe::setApiKey('sk_test_51NSgyuSF4Znl2mSMztM9hVKDfnoHzfnd5JIty14ciaEpuK3pVp665ZFac95E7jEqnnWLnXo9Nd610PMHslbYKRev00oAJLKfFV');

//     $body = json_decode(file_get_contents('php://input'), true);
//     $paymentMethodId = sanitize_text_field($body['payment_method']);

//     try {
//         $paymentIntent = \Stripe\PaymentIntent::create([
//             'amount' => 5000, // Amount in cents (e.g., $50.00)
//             'currency' => 'usd',
//             'payment_method' => $paymentMethodId,
//             'confirmation_method' => 'manual',
//             'confirm' => true,
//         ]);

//         wp_send_json_success($paymentIntent);
//     } catch (\Stripe\Exception\ApiErrorException $e) {
//         wp_send_json_error($e->getMessage());
//     }

//     wp_die();
// }


