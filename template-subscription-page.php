<?php
/**
 * Template Name: Subscription Page
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
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $company = sanitize_text_field($_POST['company']);
        $job_title = sanitize_text_field($_POST['job_title']);
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
                update_user_meta($user_id, 'company', $company);
                update_user_meta($user_id, 'job_title', $job_title);

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

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <h1>Subscription Page</h1>
        <p>Please fill out the form below to subscribe:</p>

        <form id="subscription-form" action="<?php echo esc_url(get_permalink()); ?>" method="POST">
            <input type="hidden" name="subscription_form" value="1">
            <?php wp_nonce_field('subscription_form_nonce', 'subscription_form_nonce_field'); ?>

            <p>
                <label for="first_name">First Name</label><br>
                <input type="text" id="first_name" name="first_name" required>
            </p>
            <p>
                <label for="last_name">Last Name</label><br>
                <input type="text" id="last_name" name="last_name" required>
            </p>
            <p>
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" required>
            </p>
            <p>
                <label for="phone">Phone Number</label><br>
                <input type="tel" id="phone" name="phone" required>
            </p>
            <p>
                <label for="company">Company</label><br>
                <input type="text" id="company" name="company">
            </p>
            <p>
                <label for="job_title">Job Title</label><br>
                <input type="text" id="job_title" name="job_title">
            </p>

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

    </main><!-- .site-main -->
</div><!-- .content-area -->


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

