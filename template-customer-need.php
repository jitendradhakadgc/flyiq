<?php
/*
Template Name: Customer Need
*/

get_header(); 

// add shortcode for forminator customer need form
echo do_shortcode('[forminator_form id="748"]'); 

$baseURL = 'https://flyiqweb.azurewebsites.net/api/Token/GenerateToken';
function GenerateToken() {
    $baseURL = 'https://flyiqweb.azurewebsites.net/api';
    $url = $baseURL . '/Token/GenerateToken';
    
    // Adjust data if your API requires parameters
    $data = json_encode(array(
        // Add any required parameters here
        // 'username' => 'your_username',
        // 'password' => 'your_password'
    ));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);

    if (isset($data['token'])) {
        $tokenValue = $data['token'];
        return "Generated token: " . $tokenValue;
    } else {
        // Handle missing token
        return 'Token not found in response: ' . $response;
    }
}

// Call the function to generate the token and show the response
// echo GenerateToken();



add_action('wp_ajax_get_sector_background2', 'get_sector_background2');
add_action('wp_ajax_nopriv_get_sector_background2', 'get_sector_background2');

function get_sector_background2() {
    // Handle form data here
    $radio_value = sanitize_text_field( $_POST['radio-1'] );
    $from_value = sanitize_text_field( $_POST['text-1'] );
    $to_value = sanitize_text_field( $_POST['text-2'] );
    $departure_date = sanitize_text_field( $_POST['date-1'] );
    $flexible_days = sanitize_text_field( $_POST['number-1'] );
    $passengers = sanitize_text_field( $_POST['text-3'] );
    $aircraft_type = sanitize_text_field( $_POST['select-1'] );
    
    $firstname = isset($_POST['firstname']) ? sanitize_text_field($_POST['firstname']) : '';
    $surname = isset($_POST['surname']) ? sanitize_text_field($_POST['surname']) : '';
    $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
    $dob = isset($_POST['dob']) ? sanitize_text_field($_POST['dob']) : '';
    $experience = isset($_POST['experience']) ? sanitize_text_field($_POST['experience']) : '';
    $additional_countries = isset($_POST['additional_countries']) ? sanitize_text_field($_POST['additional_countries']) : '';
    $continent = isset($_POST['continent']) ? sanitize_text_field($_POST['continent']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $education = isset($_POST['education']) ? sanitize_text_field($_POST['education']) : '';
    $cover_letter = isset($_POST['cover_letter']) ? sanitize_textarea_field($_POST['cover_letter']) : '';

    $languages = isset($_POST['languages']) ? array_map('sanitize_text_field', $_POST['languages']) : array();
    $countries = isset($_POST['countries']) ? array_map('sanitize_text_field', $_POST['countries']) : array();

    // Implode arrays into comma-separated strings
    $languageString = implode(",", $languages);
    $countriesString = implode(",", $countries);

  

    // Handle CV file (not needed in this simplified example)

    // Prepare the post data array
    $post_data = array(
        'FirstName' => $firstname,
        'LastName' => $surname,
        'Gender' => $gender,
        'DOB' => $dob,
        'ExperienceDetail' => $experience,
        'OtherCountryName' => $additional_countries,
        'State' => '',
        'CountinentId' => $continent,
        'SectorName' => '',
        'Address1' => '',
        'Address2' => '',
        'PinCode' => '',
        'City' => '',
        'Phone' => '',
        'LanguageIds' => $languageString,
        'Email' => $email,
        'SkillIds' => '',
        'CountriesWorkedIn' => $countriesString,
        'HighestLevelOfEducIdName' => $education,
        'StatusId' => 1,
        'CoverLetter' => $cover_letter
    );

    // Print or use $post_data as needed
    print_r($post_data);

    // Example of API call (replace with your actual API URL and token logic)
    $token = GenerateToken(); // Assuming GenerateToken() function exists
    $api_url = 'https://flyiqweb.azurewebsites.net/api/CustomerNeedApi/Create';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query($post_data), // Use http_build_query to handle array data
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded', // Adjust as per API requirements
            "Authorization: Bearer $token",
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        // Handle error
        echo 'Curl error: ' . curl_error($curl);
    }
    curl_close($curl);

    // Output the API response
    echo $response;

    wp_die(); // Always include wp_die() after AJAX handling
}

get_footer(); 