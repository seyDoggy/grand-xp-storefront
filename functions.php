<?php
add_action('get_header', 'remove_storefront_sidebar');

add_action('wp_enqueue_scripts', 'grand_xp_storefront_enqueue_styles');

add_action('woocommerce_checkout_process', 'anti_fraud_ip_checker', 10, 0);

add_action( 'init', 'remove_storefront_footer_credit' );

/**
 * Remove the Storefront sidebar on product and product category pages.
 */
function remove_storefront_sidebar()
{
    if ( is_product() || is_product_category()) {
        remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    }
}

/**
 * Enqueue the Grand XP Storefront styles.
 */
// This function will load the main stylesheet of the child theme.
// It assumes that the child theme's style.css file is located in the root directory of the
function grand_xp_storefront_enqueue_styles()
{
    wp_enqueue_style(
        'grand-xp-storefront-style',
        get_stylesheet_uri(),
        array(),
        null,
        'all'
    );
}

/**
 * Anti-fraud IP checker.
 * This function checks if the customer's IP address has made more than 10 orders in the last hour.
 * If so, it adds an error notice to the checkout process.
 */
// This function uses WooCommerce's geolocation and order retrieval functions to check the number of orders
// made from the customer's IP address in the last hour. If the count exceeds 10,
function anti_fraud_ip_checker() {
    $customer_ip = WC_Geolocation::get_ip_address();
    $last_1_hour_from_ip_results = wc_get_orders(array(
        'date_created'        => '>=' . (time() - 3600), // time in seconds
        'customer_ip_address' => $customer_ip,
        'paginate'            => true  // adds a total field to the results
    ));
    if(empty($customer_ip) || $last_1_hour_from_ip_results->total > 10) { 
        wc_add_notice('Too many attempts in the last hour. Please return later.', 'error');
    }
}

/**
 * Remove the Storefront footer credit.
 * This function removes the default footer credit from the Storefront theme.
 */
// This function uses the WordPress action hook to remove the footer credit from the Storefront theme
// by removing the 'storefront_credit' function from the 'storefront_footer' action hook
function remove_storefront_footer_credit() {
    remove_action( 'storefront_footer', 'storefront_credit', 20 );
}