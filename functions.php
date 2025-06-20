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

add_filter('manage_edit-shop_order_columns', function($columns) {
    $columns['ip_address'] = 'IP Address';
    return $columns;
});

add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
    if ($column == 'ip_address') {
        $order = wc_get_order($post_id);
        echo $order->get_customer_ip_address();
    }
}, 10, 2);