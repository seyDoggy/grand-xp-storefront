<?php
/**
 * Grand Experiences Storefront Child Theme Functions
 */

// 1. Enqueue Styles
add_action('wp_enqueue_scripts', 'grand_xp_storefront_enqueue_styles');
function grand_xp_storefront_enqueue_styles() {
    wp_enqueue_style( 
        'grand-xp-storefront-style', 
        get_stylesheet_uri(), 
        array(), 
        null, 
        'all' 
    );
}

// 2. Clean Up Storefront Elements (Search, Cart, Credits, Breadcrumbs)
add_action( 'init', 'clean_up_storefront_actions' );
function clean_up_storefront_actions() {
    // Remove Search and Cart
    remove_action( 'storefront_header', 'storefront_product_search', 40 );
    remove_action( 'storefront_header', 'storefront_header_cart', 60 );
    
    // Remove Footer Credits
    remove_action( 'storefront_footer', 'storefront_credit', 20 );
    remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );

    // Remove Breadcrumbs (Both Standard and Woo versions)
    remove_action( 'storefront_content_top', 'storefront_breadcrumb', 10 );
    remove_action( 'storefront_content_top', 'woocommerce_breadcrumb', 10 );

    // Remove Page Title & Header
    remove_action( 'storefront_page', 'storefront_page_header', 10 );
}

// 3. Disable "Install WooCommerce" Admin Notice
add_action( 'wp_loaded', function() {
    remove_action( 'admin_notices', 'storefront_is_woocommerce_activated_notice' );
    remove_action( 'admin_notices', 'storefront_welcome_notice' );
});

// 4. Add "Book Your Adventure" Button to Header (Exclude /find-your-experience/ + children, and /contact-us/)
// Helper: Check if the CTA should be shown
function ge_should_show_header_cta() {
    global $post;
    
    // Define the slug of the parent page to exclude along with its children
    $parent_slug = 'find-your-experience';
    $parent_page = get_page_by_path( $parent_slug );
    $parent_id   = $parent_page ? $parent_page->ID : 0;

    // Check exclusion conditions (Add any other excluded pages here)
    $is_excluded = is_page( $parent_slug ) 
                || ( $parent_id && is_page() && ! empty( $post ) && in_array( $parent_id, get_post_ancestors( $post ) ) )
                || is_page( 'contact-us' )
				|| is_page('contest');

    return ! $is_excluded;
}

// Add custom class 'has-sticky-cta' to body if conditions are met
add_filter( 'body_class', 'ge_add_cta_body_class' );
function ge_add_cta_body_class( $classes ) {
    if ( ge_should_show_header_cta() ) {
        $classes[] = 'has-sticky-cta';
    }
    return $classes;
}

// Add the HTML to the header
add_action( 'storefront_header', 'add_cta_to_storefront_header', 40 );
function add_cta_to_storefront_header() {
    if ( ge_should_show_header_cta() ) {
        ?>
        <div class="header-cta-wrapper">
             <a href="https://fareharbor.com/embeds/book/grand-experiences/?full-items=yes&flow=1495255" onclick="return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', flow: 1495255, view: 'items' }));" 
               class="button header-book-now">
               Book Your Adventure
            </a>
        </div>
        <?php
    }
}

/**
 * 5. WOOCOMMERCE DEPENDENCY WRAPPER
 * All code inside this block only runs if WooCommerce is active.
 * This prevents your site from crashing when you delete the plugin.
 */
if ( class_exists( 'WooCommerce' ) ) {

    add_action('get_header', 'remove_storefront_sidebar');
    add_action('woocommerce_checkout_process', 'anti_fraud_ip_checker', 10, 0);

    function remove_storefront_sidebar() {
        if ( is_product() || is_product_category()) {
            remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
        }
    }

    function anti_fraud_ip_checker() {
        $customer_ip = WC_Geolocation::get_ip_address();
        // Check if orders exist before accessing properties
        $last_1_hour_from_ip_results = wc_get_orders(array(
            'date_created'        => '>=' . (time() - 3600),
            'customer_ip_address' => $customer_ip,
            'paginate'            => true
        ));
        
        if(empty($customer_ip) || (isset($last_1_hour_from_ip_results->total) && $last_1_hour_from_ip_results->total > 10)) { 
            wc_add_notice('Too many attempts in the last hour. Please return later.', 'error');
        }
    }
}