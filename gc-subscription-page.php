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

// Assuming you have loaded your JSON data into $data as shown in your example


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

function handle_charge_succeeded($charge) {
    update_option("Payment_got_charged", true);
}

// forminator
if (!defined('ABSPATH')) {
    exit;
}

if (defined('WP_CLI') && WP_CLI) {
    return;
}
if (!defined('ABSPATH')) {
    exit;
}

if (defined('WP_CLI') && WP_CLI) {
    return;
}

/* - - - - - - - - - - - -
    ! Remember to insert form ID in both filters
- - - - - - - - - - - - */
add_filter('forminator_cform_render_fields', function($wrappers, $model_id) {
    // $json_file_path = plugin_dir_path(__FILE__) . 'airports.json';

    // // Read JSON file contents
    // $json_data = file_get_contents($json_file_path);

    // // Decode JSON data into an associative array
    // $data = json_decode($json_data, true);
    $data =   $data = array(
        "00AK" => array(
            "icao" => "00AK",
            "iata" => "",
            "name" => "Lowell Field",
            "city" => "Anchor Point",
            "state" => "Alaska",
            "country" => "US",
            "elevation" => 450,
            "lat" => 59.94919968,
            "lon" => -151.695999146,
            "tz" => "America/Anchorage"
        ),
       );

    // Check if the JSON data is valid
    if ($data === null) {
        echo 'Error decoding JSON data.';
        return $wrappers; // Return original wrappers if JSON decoding fails
    }

    /* - - - - - - - - - - - -
        ! Change 748 to your form ID
    - - - - - - - - - - - - */
    if ($model_id != 748) {
        return $wrappers;
    }

    /* - - - - - - - - - - - -
        ! Update the field data
    - - - - - - - - - - - - */
    $select_fields_data = array(
        'select-2' => 'Label 1',
        'select-3' => 'Label 1',
        'select-4' => 'Label 1',
        'select-5' => 'Label 1',
    );

    foreach ($wrappers as $wrapper_key => $wrapper) {
        if (!isset($wrapper['fields'])) {
            continue;
        }

        foreach ($wrapper['fields'] as $field_key => $field) {
            if (
                isset($select_fields_data[$field['element_id']]) &&
                !empty($select_fields_data[$field['element_id']])
            ) {
                // Generate options from JSON data
                $new_options = [];
                foreach ($data as $key => $location) {
                    $new_options[] = array(
                        'label' => esc_html($location['name']) . ' (' . esc_html($key) . ')',
                        'value' => esc_attr($key),
                        'limit' => '-1', // Modify as needed
                        'key'   => forminator_unique_key(), // Assuming this generates a unique key
                    );
                }

                $opt_data = array(
                    'options' => $new_options,
                );

                $select_field = Forminator_API::get_form_field($model_id, $field['element_id'], true);
                if ($select_field) {
                    Forminator_API::update_form_field($model_id, $field['element_id'], $opt_data);
                    $wrappers[$wrapper_key]['fields'][$field_key]['options'] = $new_options;
                }
            }
        }
    }

    return $wrappers;
}, 10, 2);

add_filter('forminator_replace_form_data', function($content, $data, $fields) {

    /* - - - - - - - - - - - -
        ! Change 748 to your form ID
    - - - - - - - - - - - - */
    if ($data['form_id'] != 748) {
        return $content;
    }

    if (!empty($content)) {
        return $content;
    }

    $form_fields = Forminator_API::get_form_fields($data['form_id']);
    foreach ($data as $key => $value) {
        if (strpos($key, 'select') !== false) {
            $field_value = isset($data[$key]) ? $data[$key] : null;

            if (!is_null($field_value)) {
                $fields_slugs = wp_list_pluck($form_fields, 'slug');
                $field_key = array_search($key, $fields_slugs, true);
                $field_options = false !== $field_key && !empty($form_fields[$field_key]->raw['options'])
                    ? wp_list_pluck($form_fields[$field_key]->options, 'label', 'value')
                    : array();

                if (!isset($field_options[$field_value]) && isset($_POST[$key])) {
                    return sanitize_text_field($_POST[$key]);
                }
            }
        }
    }

    return $content;
}, 10, 3);

// end code forminator


// CustomerNeed
function GenerateToken() {
    $baseURL = 'https://flyiqweb.azurewebsites.net/api/Token/GenerateToken';
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $baseURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        return $data['token'];
    } else {
        // Handle missing token
        return null;
    }
}

add_filter('forminator_custom_form_submit_field_data', 'custom_form_submit', 11, 2);
function custom_form_submit($field_data_array, $form_id) {
    if ($form_id == 748) {
        $all_fields = array();
        foreach ($field_data_array as $field) {
            if (isset($field['name']) && isset($field['value'])) {
                $clean_field_value = sanitize_text_field($field['value']);
                $all_fields[$field['name']] = $clean_field_value;
            }
        }

        $token = GenerateToken();

        if (!$token) {
            return $field_data_array;
        }

        //$data =  json_encode($all_fields);
        update_option('datasaveform11',$all_fields);
        update_option('datasaveform',$all_fields['select-4-2']);
       
        $depart_date = isset($all_fields['date-1']) ? date('Y-m-d', strtotime($all_fields['date-1'])) : '';
        $return_date = isset($all_fields['date-2']) ? date('Y-m-d', strtotime($all_fields['date-2'])) : '';

        // $api_endpoint = 'https://flyiqweb.azurewebsites.net/api/CustomerNeedApi/Create';
        // Check the value of radio-1
        if (isset($all_fields['radio-1']) && $all_fields['radio-1'] == 'RoundTrip') {
            $post_fields = array(
                'FirstName' => isset($all_fields['name-2']) ? $all_fields['name-2'] : '',
                'LastName' => isset($all_fields['name-4']) ? $all_fields['name-4'] : '',
                'LeavingFrom' => isset($all_fields['select-2']) ? $all_fields['select-2'] : '', // Example value
                'GoingOn' => isset($all_fields['select-3']) ? $all_fields['select-3'] : '', // Example value
                'Email' => isset($all_fields['email-1']) ? $all_fields['email-1'] : '', // Example value
                'DepartDate' => $depart_date,
                'ReturnDate' => $return_date, // Example value
                'MobileNumber' => isset($all_fields['phone-1']) ? $all_fields['phone-1'] : '', // Example value
                'Passengers' => isset($all_fields['text-3']) ? $all_fields['text-3'] : '', // Example value
                'AirCraftType' => isset($all_fields['select-1']) ? $all_fields['select-1'] : '', // Example value
                'Wifi' => isset($all_fields['radio-2']) ? $all_fields['radio-2'] : '', // Example value
                'Pet' => isset($all_fields['text-9']) ? $all_fields['text-9'] : '', // Example value
                'Body' => isset($all_fields['textarea-1']) ? $all_fields['textarea-1'] : '', // Example value
            );
        } else {
            $post_fields = array(
                'FirstName' => isset($all_fields['name-2']) ? $all_fields['name-2'] : '',
                'LastName' => isset($all_fields['name-4']) ? $all_fields['name-4'] : '',
                'LeavingFrom' => isset($all_fields['select-2']) ? $all_fields['select-2'] : '', // Example value
                'GoingOn' => isset($all_fields['select-3']) ? $all_fields['select-3'] : '', // Example value
                'Email' => isset($all_fields['email-1']) ? $all_fields['email-1'] : '', // Example value
                'DepartDate' => $depart_date,
                // 'ReturnDate' => $return_date, // Example value
                'MobileNumber' => isset($all_fields['phone-1']) ? $all_fields['phone-1'] : '', // Example value
                'Passengers' => isset($all_fields['text-3']) ? $all_fields['text-3'] : '', // Example value
                'AirCraftType' => isset($all_fields['select-1']) ? $all_fields['select-1'] : '', // Example value
                'Wifi' => isset($all_fields['radio-2']) ? $all_fields['radio-2'] : '', // Example value
                'Pet' => isset($all_fields['text-9']) ? $all_fields['text-9'] : '', // Example value
                'Body' => isset($all_fields['textarea-1']) ? $all_fields['textarea-1'] : '', // Example value
            );
        }

        update_option('datasaveform33111',$post_fields);
        // array('CustomerName' => 'harshit p','LeavingFrom' => '00AK','GoingOn' => '00AK','email' => 'apustake12345@gmail.com','DepartDate' => '02/09/2025','MobileNumber' => '789456123','Passengers' => '2','AirCraftType' => 'piston','Company' => 'htc','JobTitle' => 'software'),
        $json_data = json_encode($post_fields);
        update_option('datasavefor234m33111',$json_data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://flyiqweb.azurewebsites.net/api/CustomerNeedApi/Create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>  $json_data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $token",
                'Content-Type: application/json',
                'Cookie: ARRAffinity=92ca53ad8db4fbb93d4d3b7d8ab54dcf8ffecb2d731f25b0e91ad575d7534c3f; ARRAffinitySameSite=92ca53ad8db4fbb93d4d3b7d8ab54dcf8ffecb2d731f25b0e91ad575d7534c3f'
            ),
            ));

        $response = curl_exec($curl);
        // print_r( $response );
        if (curl_errno($curl)) {
            update_option('api_error', curl_error($curl));
        } else {
            update_option('api_response_success', serialize($response));
        }
        curl_close($curl);
    }
    return $field_data_array;
}














add_filter('forminator_custom_form_submit_field_data', 'custom_form_submit1', 11, 2);

function custom_form_submit1($field_data_array, $form_id) {
    // Check if the form ID matches the specific form you want to target
    //$target_form_id = 33; // Replace with your actual form ID
    if ($form_id == 1391) {
        // Initialize an array to hold all form field values
        $all_fields = array();

        // Loop through the form data to get all fields
        foreach ($field_data_array as $field) {
            if (isset($field['name']) && isset($field['value'])) {
                // Clean the field value
                $clean_field_value = sanitize_text_field($field['value']);
                // Add the field value to the array with the field name as the key
                $all_fields[$field['name']] = $clean_field_value;
            }
        }

        // Save the entire array of fields to the options table
        update_option('form_id1', $form_id);
        update_option('forminator_fields2', $all_fields);
    }
    return $field_data_array;
}














?>
