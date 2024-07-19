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

// add_action('wp_ajax_stripe_webhook', 'handle_stripe_webhook');
// add_action('wp_ajax_nopriv_stripe_webhook', 'handle_stripe_webhook');

// function handle_stripe_webhook() {
//     $payload = @file_get_contents('php://input');
//     $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
//     $endpoint_secret = 'whsec_Jg3aDEihzxCnk9tGcEnzen1IZu94Aaej';
    
//     try {
//         $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

//         if ($event->type === 'payment_intent.succeeded') {
//             $paymentIntent = $event->data->object;

//             // Extract relevant data
//             $paymentIntentId = $paymentIntent->id;
//             $customerId = $paymentIntent->customer;

//             // Handle successful payment
//             // Example: Store payment details in the database
//         }

//         http_response_code(200);
//     } catch(\UnexpectedValueException $e) {
//         // Invalid payload
//         http_response_code(400);
//     } catch(\Stripe\Exception\SignatureVerificationException $e) {
//         // Invalid signature
//         http_response_code(400);
//     }
//     exit;
// }


// Add action for authenticated AJAX requests
add_action('wp_ajax_stripe_webhook', 'handle_stripe_webhook');

// Add action for non-authenticated AJAX requests
add_action('wp_ajax_nopriv_stripe_webhook', 'handle_stripe_webhook');

function handle_stripe_webhook() {
    // Your endpoint secret received from Stripe
    $endpoint_secret = 'whsec_Jg3aDEihzxCnk9tGcEnzen1IZu94Aaej';

    // Retrieve the request payload
    $payload = @file_get_contents('php://input');

    // Retrieve the Stripe signature from the header
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

    // Initialize Stripe
    require_once('/path/to/stripe-php/init.php'); // Adjust the path as per your setup
    \Stripe\Stripe::setApiKey('sk_test_51PbGpOLEsFu19GDa6UZs9MEzL1HvwIv7WKh4GXCdbCWf4uE2eD8PhjAxTHO0aGPXplb288WDmJ25jefteQ5NWpOy00aZL1pBAi');

    // Verify the Stripe signature
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $endpoint_secret
        );
    } catch (\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        exit();
    }

    // Handle the event based on type
    try {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;

                // Extract relevant data
                $paymentIntentId = $paymentIntent->id;
                $customerId = $paymentIntent->customer;

                // Handle successful payment
                // Example: Store payment details in the database
                // Example: Send a thank you email to the customer

                break;
            // Add more cases as needed for different event types

            default:
                // Handle other event types if necessary
                break;
        }

        // Return a 200 response to acknowledge receipt of the event
        http_response_code(200);
    } catch (Exception $e) {
        // Handle any other exceptions or errors
        http_response_code(500);
    }

    exit();
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
                    <label for="first_name">Full Name</label><br>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                    <!-- <div class="inputBox">
                    <label for="last_name">Last Name</label><br>
                    <input type="text" id="last_name" name="last_name" required>
                    </div> -->
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
                <div id="payment-element"></div>
                </div>
            </div>

            <!-- Stripe Payment Form
            <div id="payment-element"></div>
            <p>
                <button id="submit-button" class="stripe-payment-button">
                    <div class="spinner hidden" id="spinner"></div>
                    <span id="button-text">Subscribe</span>
                </button>
            </p>
            <div id="payment-message" class="hidden"></div> -->

            <div class="row row-App-Overview">
					<div class="App-Overview">
						<div class="OrderSummaryColumn">
							<div class="product-summary">
								<span class="ProductSummary-name">Subscribe to 6 - 14 Employees</span>
								<div class="ProductSummaryTotalAmount">
									<div>
										<span class="CurrencyAmount">£1,000.00</span>
									</div>
									<div class="BillingInterval">
										<span class="ProductSummaryTotalAmount-billingInterval">
											<div>per <br class="BillingIntervalBreak">year</div>
										</span>
									</div>
								</div>
							</div>
							<section class="order-details">
								<ul class="OrderDetails-items">
									<li class="OrderDetails-item">
										<div class="LineItem">
											<div class="LineItem-productName">
												<span>
													6 - 14 Employees
												</span>
											</div>
											<div>
												<span class="CurrencyAmount">
													£1,000.00
												</span>
											</div>
										</div>
										<div class="LineItem-description">
											<span>Billed yearly</span>
										</div>
									</li>
								</ul>
								<div class="OrderDetails-footer">
									<div class="OrderDetailsFooter-subtotal">
										<span>
											Subtotal
										</span>
										<div class="OrderDetailsFooter-subtotal-placeholder">
											<span>
												£1,000.00
											</span>
										</div>
									</div>
									<div class="OrderDetails-total">
										<div class="OrderDetailsFooter-total">
											<span>
												Total
											</span>
										</div>
										<div class="OrderDetailsFooter-total-placeholder">
											<span>
												£1,000.00
											</span>
										</div>
									</div>
								</div>
							</section>
						</div>
					</div>
					<!-- <div class="submit_btn_container">
						<input type="submit" value="Subscribe" class="submit_btn">
					</div> -->

                    <p>
                    <div class="submit_btn_container">
                        <input type="submit" id="submit-button" value="Subscribe" class="submit_btn">
                        <div class="spinner hidden" id="spinner"></div>
                        <span id="button-text"></span>
                    </div>
                    </p>
                    <div id="payment-message" class="hidden"></div>

					<footer class="App-Footer">
						<div class="Footer-PoweredBy"><a class="Link" href="https://stripe.com" target="_blank"
								rel="noopener">
								<div class="">
									<div>Powered by <svg
											class="" focusable="false" role="img" aria-labelledby="stripe-title">
											<title id="stripe-title">Stripe</title>
											<g fill-rule="evenodd">
												<path
													d="M32.956 7.925c0-2.313-1.12-4.138-3.261-4.138-2.15 0-3.451 1.825-3.451 4.12 0 2.719 1.535 4.092 3.74 4.092 1.075 0 1.888-.244 2.502-.587V9.605c-.614.307-1.319.497-2.213.497-.876 0-1.653-.307-1.753-1.373h4.418c0-.118.018-.588.018-.804zm-4.463-.859c0-1.02.624-1.445 1.193-1.445.55 0 1.138.424 1.138 1.445h-2.33zM22.756 3.787c-.885 0-1.454.415-1.77.704l-.118-.56H18.88v10.535l2.259-.48.009-2.556c.325.235.804.57 1.6.57 1.616 0 3.089-1.302 3.089-4.166-.01-2.62-1.5-4.047-3.08-4.047zm-.542 6.225c-.533 0-.85-.19-1.066-.425l-.009-3.352c.235-.262.56-.443 1.075-.443.822 0 1.391.922 1.391 2.105 0 1.211-.56 2.115-1.39 2.115zM18.04 2.766V.932l-2.268.479v1.843zM15.772 3.94h2.268v7.905h-2.268zM13.342 4.609l-.144-.669h-1.952v7.906h2.259V6.488c.533-.696 1.436-.57 1.716-.47V3.94c-.289-.108-1.346-.307-1.879.669zM8.825 1.98l-2.205.47-.009 7.236c0 1.337 1.003 2.322 2.34 2.322.741 0 1.283-.135 1.581-.298V9.876c-.289.117-1.716.533-1.716-.804V5.865h1.716V3.94H8.816l.009-1.96zM2.718 6.235c0-.352.289-.488.767-.488.687 0 1.554.208 2.241.578V4.202a5.958 5.958 0 0 0-2.24-.415c-1.835 0-3.054.957-3.054 2.557 0 2.493 3.433 2.096 3.433 3.17 0 .416-.361.552-.867.552-.75 0-1.708-.307-2.467-.723v2.15c.84.362 1.69.515 2.467.515 1.879 0 3.17-.93 3.17-2.548-.008-2.692-3.45-2.213-3.45-3.225z">
												</path>
											</g>
										</svg></div>
								</div>
							</a></div>
						<div class="Footer-Links"><a class="" href="https://stripe.com/legal/end-users"
								target="_blank" rel="noopener"><span
									class="">Terms</span></a><a
								class="" href="https://stripe.com/privacy" target="_blank" rel="noopener"><span
									class="">Privacy</span></a></div>
					</footer>
				</div>

            <!-- end -->
           
 
        

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
                        line1: form.find('input[name="subscription_address"]').val(),
                        city: form.find('input[name="city"]').val(),
                        state: form.find('input[name="state"]').val(),
                        country: form.find('select[name="billingCountry"]').val(),
                        postal_code: form.find('input[name="postal_code"]').val(),  // Added postal_code field
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