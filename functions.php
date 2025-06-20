<?php
add_action('get_header', 'remove_storefront_sidebar');

add_action('wp_enqueue_scripts', 'grand_xp_storefront_enqueue_styles');

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

/**
* Inspired by https://stackoverflow.com/questions/53140009/add-search-by-customer-ip-address-to-woocommerce-order-search
* Code idea borrowed from https://www.skyverge.com/blog/filtering-woocommerce-orders/ && https://gist.github.com/bekarice/41bce677437cb8f312ed77e9f226a812
*/
add_filter( 'request', 'filter_orders_by_payment_method_query' );	
function filter_orders_by_payment_method_query( $vars ) {
    
    global $typenow;
    if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_ip'] ) ) {
        $vars['meta_key']   = '_customer_ip_address';
        $vars['meta_value'] = wc_clean( $_GET['_shop_order_ip'] );
    }
    return $vars;
}



// UPDATED: April 11, 2024
// This is for HPOS
/**
* Inspired by https://stackoverflow.com/questions/53140009/add-search-by-customer-ip-address-to-woocommerce-order-search
* Code idea borrowed from https://www.skyverge.com/blog/filtering-woocommerce-orders/ && https://gist.github.com/bekarice/41bce677437cb8f312ed77e9f226a812
*/
add_filter( 'woocommerce_order_query_args', 'managedwphosting_woocommerce_order_query_args' );
function managedwphosting_woocommerce_order_query_args( $args ) {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-orders' && isset( $_GET['_shop_order_ip'] ) ) {
        $args['ip_address'] = wc_clean( $_GET['_shop_order_ip'] );
    }
    
    return $args;
}