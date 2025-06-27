<?php

/**
 * PH Customer booking followup email HTML.
 * 
 * You can choose to display the following details within the email 
 *  
 * $order_id         	- used to include Order ID
 * $order_number     	- used to include Order Number
 * $customer_first_name - used to include Customer First Name
 * $customer_last_name  - used to include Customer Last Name
 * $customer_full_name	- used to include Customer Full Name
 * $recipient_email     - used to include Recipient Email Id
 * $order     			- used to include Order Object
 * $item     			- used to include Order Item
 * $email_subject       - used to include Email Subject
 * $email_heading    	- used to include Email Header
 * $additional_content  - used to include Additional Content
 * $email_base_color	- used to include Email Base Color
 * $email_text_color	- used to include Text Color
 * $wp_date_format		- used to include Date Format
 * $email            	- used to include email object
 * $sent_to_admin 		- need to send email to admin
 * $plain_text    		- email type
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
 * @hooked WC_Emails::email_header() Output the email header
*/
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p>
    <?php
    echo __('Hi', 'bookings-and-appointments-for-woocommerce') . ' ' . $customer_full_name . ',<br><br>';
    printf(__('Thank you for booking with %s.<br><br>You can learn more about meeting places, and what to expect on the day of your trip <a href="https://grand-experiences.com/about-us/grand-experiences-trip-information/">here</a>.', 'bookings-and-appointments-for-woocommerce'), $email->blog_name);
    ?>
</p>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
