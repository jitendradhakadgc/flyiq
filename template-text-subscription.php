<?php
/**
 * Template Name:Test Subscription Page
 */

require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51PbGpOLEsFu19GDa6UZs9MEzL1HvwIv7WKh4GXCdbCWf4uE2eD8PhjAxTHO0aGPXplb288WDmJ25jefteQ5NWpOy00aZL1pBAi');

get_header(); 

// Check if the form has been submitted
if (isset($_POST['subscription_form']) && $_POST['subscription_form'] == '1') {
    // Check nonce for security
    if (!isset($_POST['subscription_form_nonce_field']) || !wp_verify_nonce($_POST['subscription_form_nonce_field'], 'subscription_form_nonce')) {
        echo '<p>Nonce verification failed</p>';
    } else {
        // Validate and sanitize the form data
        $first_name = sanitize_text_field($_POST['first_name']);
        // $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        // $company = sanitize_text_field($_POST['company']);
        // $job_title = sanitize_text_field($_POST['job_title']);
         $subscription_address = sanitize_text_field($_POST['subscription_address']);
         $city = sanitize_text_field($_POST['city']);
         $state = sanitize_text_field($_POST['state']);
         $billingCountry = sanitize_text_field($_POST['billingCountry']);
        $payment_method_id = sanitize_text_field($_POST['payment_method_id']);

        // Check if email already exists
        if (email_exists($email)) {
            echo '<h1>Email already exists</h1>';
        } else {
            // Create a new user
            $user_id = wp_create_user($email, wp_generate_password(), $email);

            if (is_wp_error($user_id)) {
                echo '<p>Error creating user: ' . $user_id->get_error_message() . '</p>';
            } else {
                // Update user meta data
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                ));

                update_user_meta($user_id, 'phone', $phone);
                 update_user_meta($user_id, 'subscription_address', $subscription_address);
                 update_user_meta($user_id, 'city', $city);
                 update_user_meta($user_id, 'state', $state);
                 update_user_meta($user_id, 'billingCountry', $billingCountry);

                // Create Payment Intent
                try {
                    $paymentIntent = \Stripe\PaymentIntent::create([
                        'amount' => 5000, // Amount in cents (e.g., $50.00)
                        'currency' => 'usd',
                        'payment_method_types' => ['card'],
                        'payment_method' => $payment_method_id,
                        'confirm' => true, // Confirm the payment immediately
                        'description' => 'Subscription Payment',
                        'statement_descriptor_suffix' => 'ExampleCorp', // Optional: Up to 22 characters
                    ]);

                 
                    if ($paymentIntent->status === 'succeeded') {
                        // Payment was successful
                        // Send a confirmation email to the user
                        $to = $email;
                        $subject = 'Subscription Confirmation';
                        $message = "Hello $first_name,\n\nThank you for subscribing!\n\nBest regards,\nYour Company";
                        $headers = array('Content-Type: text/plain; charset=UTF-8');
                        wp_mail($to, $subject, $message, $headers);

                        echo '<p>Thank you for subscribing! Check your email for a confirmation message.</p>';
                    } else {
                        // Payment failed or is in a different status
                        echo '<p>Payment was not successful. Please try again.</p>';
                    }
                                } catch (\Stripe\Exception\ApiErrorException $e) {
                    echo '<p>Error processing payment: ' . $e->getMessage() . '</p>';
                }
            }
        }
    }
}

add_action('wp_ajax_stripe_webhook', 'handle_stripe_webhook');
add_action('wp_ajax_nopriv_stripe_webhook', 'handle_stripe_webhook');

function handle_stripe_webhook() {
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $endpoint_secret = 'your_webhook_endpoint_secret';
    
    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            // Extract relevant data
            $paymentIntentId = $paymentIntent->id;
            $customerId = $paymentIntent->customer;

            // Handle successful payment
            // Example: Store payment details in the database
        }

        http_response_code(200);
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
    }
    exit;
}

?>

    <main id="main" class="subscription-payment-page" role="main">
    <div class="subscription-container">

        <form id="subscription-form" action="<?php echo esc_url(get_permalink()); ?>" method="POST">
            <input type="hidden" name="subscription_form" value="1">
            <?php wp_nonce_field('subscription_form_nonce', 'subscription_form_nonce_field'); ?>

            <div class="row">
                
					<div class="col">
                        <h3 class="title">
							Billing Address
						</h3>
                <div class="inputBox">
                    <label for="first_name">First Name</label><br>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                    <div class="inputBox">
                    <label for="last_name">Last Name</label><br>
                    <input type="text" id="last_name" name="last_name" required>
                    </div>
                <div class="inputBox">
                    <label for="email">Email</label><br>
                    <input type="email" id="email" name="email" required>
                    </div>
                <div class="inputBox">
                    <label for="phone">Phone Number</label><br>
                    <input type="tel" id="phone" name="phone" required>
                    </div>
                <div class="inputBox">
                    <label for="address">Address</label><br>
                    <input type="text" id="address" name="subscription_address" placeholder="Enter address" required>
                    </div>
                <div class="flex">
                    <div class="inputBox">
                        <label for="city">City</label><br>
                        <input type="text" id="city" name="city" placeholder="Enter city" required>
                    </div>
                    <div class="inputBox">
                        <label for="state">State</label><br>
                        <input type="text" id="state" name="state" placeholder="Enter state" required>
                    </div>
                    
                </div>
                
                <div class="inputBox">
								<label for="countryregion">
									Country or region:
								</label>
								<select id="billingCountry" name="billingCountry" autocomplete="billing country" aria-label="Country or region" class="Select-source"><option value="" disabled="" hidden=""></option><option value="AF">Afghanistan</option><option value="AX">Åland Islands</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua &amp; Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AC">Ascension Island</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia &amp; Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="VG">British Virgin Islands</option><option value="BN">Brunei</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="BQ">Caribbean Netherlands</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo - Brazzaville</option><option value="CD">Congo - Kinshasa</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="CI">Côte d’Ivoire</option><option value="HR">Croatia</option><option value="CW">Curaçao</option><option value="CY">Cyprus</option><option value="CZ">Czechia</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="SZ">Eswatini</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HN">Honduras</option><option value="HK">Hong Kong SAR China</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IM">Isle of Man</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="XK">Kosovo</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Laos</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao SAR China</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="MD">Moldova</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar (Burma)</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="MK">North Macedonia</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PS">Palestinian Territories</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PN">Pitcairn Islands</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RE">Réunion</option><option value="RO">Romania</option><option value="RU">Russia</option><option value="RW">Rwanda</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">São Tomé &amp; Príncipe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SX">Sint Maarten</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia &amp; South Sandwich Islands</option><option value="KR">South Korea</option><option value="SS">South Sudan</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="BL">St. Barthélemy</option><option value="SH">St. Helena</option><option value="KN">St. Kitts &amp; Nevis</option><option value="LC">St. Lucia</option><option value="MF">St. Martin</option><option value="PM">St. Pierre &amp; Miquelon</option><option value="VC">St. Vincent &amp; Grenadines</option><option value="SR">Suriname</option><option value="SJ">Svalbard &amp; Jan Mayen</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="TW">Taiwan</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad &amp; Tobago</option><option value="TA">Tristan da Cunha</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks &amp; Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VA">Vatican City</option><option value="VE">Venezuela</option><option value="VN">Vietnam</option><option value="WF">Wallis &amp; Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option></select>
				</div>
                </div>
            </div>

            <!-- Stripe Payment Form -->
            <div id="payment-element"></div>
            <p>
                <button id="submit-button" class="stripe-payment-button">
                    <div class="spinner hidden" id="spinner"></div>
                    <span id="button-text">Subscribe</span>
                </button>
            </p>
            <div id="payment-message" class="hidden"></div>
        </form>
        </div>
    </main><!-- .site-main -->



<?php get_footer(); ?>

<script src="https://js.stripe.com/v3/"></script>
<script>
    $(document).ready(function() {
        var stripe = Stripe('pk_test_51PbGpOLEsFu19GDaV9BJ6NbontkbBy9wZ2Ng1dgyFZ1lBcsD3OkUTJvbH4Aiyyg6taTxwLpAndDgk9ATga75GjKR00S9EULYNd');
        var elements = stripe.elements();
        var style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        var card = elements.create('card', { style: style });
        card.mount('#payment-element');

        $('#subscription-form').on('submit', function(event) {
            event.preventDefault();
            var form = $(this);
            var submitButton = $('#submit-button');

            stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    name: form.find('input[name="first_name"]').val() + ' ' + form.find('input[name="last_name"]').val(),
                    email: form.find('input[name="email"]').val(),
                    phone: form.find('input[name="phone"]').val(),
                    address: {
                        line1: "test 1",
                        line2: "test 2",
                        city: "Indore",
                        state: "MP",
                        postal_code: "452012",
                        country: "IN",
                    }
                }
            }).then(function(result) {
                if (result.error) {
                    $('#payment-message').text(result.error.message).removeClass('hidden');
                    submitButton.prop('disabled', false);
                    $('#button-text').show();
                    $('#spinner').hide();
                } else {
                    // Set the payment_method_id field value
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'payment_method_id',
                        value: result.paymentMethod.id
                    }).appendTo(form);

                    // Submit the form
                    console.log('Form submitting...'); // Log to check form submission
                    form.off('submit').submit(); // Submit the form
                }
            });

            // Disable the submit button to prevent multiple submissions
            submitButton.prop('disabled', true);
            $('#button-text').hide();
            $('#spinner').show();
        });
    });
</script>