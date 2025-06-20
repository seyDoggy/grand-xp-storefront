<?php
add_action('get_header', 'remove_storefront_sidebar');

add_action('wp_enqueue_scripts', 'grand_xp_storefront_enqueue_styles');

add_action('woocommerce_checkout_process', 'anti_fraud_ip_checker', 10, 0);

function remove_storefront_sidebar()
{
    if ( is_product() || is_product_category()) {
        remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    }
}

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

function anti_fraud_ip_checker() {
    $customer_ip = WC_Geolocation::get_ip_address();
    $last_1_hour_from_ip_results = wc_get_orders(array(
        'date_created'        => '>=' . (time() - 3600), // time in seconds
        'customer_ip_address' => $customer_ip,
        'paginate'            => true  // adds a total field to the results
    ));
    if(empty($customer_ip) || $last_1_hour_from_ip_results->total > 4) { 
        wc_add_notice('Too many attempts in the last hour. Please return later.', 'error');
    }
}