<?php
/**
 * Grand Experiences Storefront Child Theme Functions
 * Updated: Feb 2026
 * Author: Adam Merrifield
 */

/**
 * 1. ENQUEUE STYLES
 */
add_action( 'wp_enqueue_scripts', 'grand_xp_storefront_enqueue_styles' );
function grand_xp_storefront_enqueue_styles() {
    wp_enqueue_style( 
        'grand-xp-storefront-style', 
        get_stylesheet_uri(), 
        array(), 
        null, 
        'all' 
    );
}

/**
 * 2. STOREFRONT CLEANUP
 */
add_action( 'init', 'grand_xp_clean_up_storefront_actions' );
function grand_xp_clean_up_storefront_actions() {
    // Remove Search and Cart
    remove_action( 'storefront_header', 'storefront_product_search', 40 );
    remove_action( 'storefront_header', 'storefront_header_cart', 60 );
    
    // Remove Footer Credits
    remove_action( 'storefront_footer', 'storefront_credit', 20 );
    remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );

    // Remove Breadcrumbs
    remove_action( 'storefront_content_top', 'storefront_breadcrumb', 10 );
    remove_action( 'storefront_content_top', 'woocommerce_breadcrumb', 10 );

    // Remove Page Title & Header
    remove_action( 'storefront_page', 'storefront_page_header', 10 );

    // REMOVED: Date & Author
    remove_action( 'storefront_post_header_before', 'storefront_post_meta', 10 );

    // REMOVED: Categories & Tags
    remove_action( 'storefront_post_content_after', 'storefront_post_taxonomy', 10 );
    remove_action( 'storefront_loop_post', 'storefront_post_taxonomy', 40 );
    remove_action( 'storefront_single_post', 'storefront_post_taxonomy', 40 );
}

/**
 * 3. ADMIN UI TWEAKS
 */
add_action( 'wp_loaded', 'grand_xp_remove_admin_notices' );
function grand_xp_remove_admin_notices() {
    remove_action( 'admin_notices', 'storefront_is_woocommerce_activated_notice' );
    remove_action( 'admin_notices', 'storefront_welcome_notice' );
}

/**
 * 4. HEADER CALL-TO-ACTION (FAREHARBOR)
 * Logic: Checks specific Custom Fields (fh_item_id, fh_flow_id, fh_cta_text) to customize the button.
 */

// Helper: Get the current ID safely (works for Pages, Posts, and Categories)
function grand_xp_get_current_id() {
    $id = get_queried_object_id();
    if ( empty( $id ) ) {
        global $post;
        if ( isset( $post ) && isset( $post->ID ) ) {
            $id = $post->ID;
        }
    }
    return $id;
}

// Helper: Check if the CTA should be shown
function grand_xp_should_show_header_cta() {
    $current_id = grand_xp_get_current_id();
    
    // 1. CHECK OVERRIDES: If specific Custom Fields exist, ALWAYS show the CTA
    if ( $current_id ) {
        $item_id = get_post_meta( $current_id, 'fh_item_id', true );
        $flow_id = get_post_meta( $current_id, 'fh_flow_id', true );
        
        if ( ! empty( $item_id ) || ! empty( $flow_id ) ) {
            return true;
        }
    }
    
    // 2. CHECK EXCLUSIONS: Standard logic
    global $post; 
    
    $parent_slug = 'find-your-experience';
    $parent_page = get_page_by_path( $parent_slug );
    $parent_id   = $parent_page ? $parent_page->ID : 0;

    $is_excluded = is_page( $parent_slug ) 
                || ( $parent_id && is_page() && ! empty( $post ) && in_array( $parent_id, get_post_ancestors( $post ) ) )
                || is_page( 'contact-us' )
                || is_page( 'contest' );

    return ! $is_excluded;
}

// Add 'has-sticky-cta' class to body for CSS styling
add_filter( 'body_class', 'grand_xp_add_cta_body_class' );
function grand_xp_add_cta_body_class( $classes ) {
    if ( grand_xp_should_show_header_cta() ) {
        $classes[] = 'has-sticky-cta';
    }
    return $classes;
}

// Output the Button
add_action( 'storefront_header', 'grand_xp_add_cta_to_storefront_header', 40 );
function grand_xp_add_cta_to_storefront_header() {
    if ( ! grand_xp_should_show_header_cta() ) {
        return; 
    }

    // 1. Get Custom Fields
    $current_id  = grand_xp_get_current_id();
    $item_id     = $current_id ? get_post_meta( $current_id, 'fh_item_id', true ) : '';
    $flow_id     = $current_id ? get_post_meta( $current_id, 'fh_flow_id', true ) : '';
    $custom_text = $current_id ? get_post_meta( $current_id, 'fh_cta_text', true ) : '';

    // --- DEFAULTS ---
    $cta_text = 'Book Your Adventure';
    $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/?full-items=yes&flow=1495255'; 
    $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', flow: 1495255, view: 'items' }));";

    // --- SCENARIO A: Specific Item ID ---
    if ( ! empty( $item_id ) ) {
        $cta_text = 'Book This Trip';
        $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/items/' . esc_attr( $item_id ) . '/?full-items=yes';
        $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', view: { item: " . esc_js( $item_id ) . " } }));";
    
    // --- SCENARIO B: Specific Flow ID ---
    } elseif ( ! empty( $flow_id ) ) {
        $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/?full-items=yes&flow=' . esc_attr( $flow_id );
        $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', flow: " . esc_js( $flow_id ) . ", view: 'items' }));";
    }

    // --- TEXT OVERRIDE: Check if user defined a custom button label ---
    if ( ! empty( $custom_text ) ) {
        $cta_text = $custom_text;
    }

    ?>
    <div class="header-cta-wrapper">
            <a href="<?php echo $fh_url; ?>" onclick="<?php echo $fh_click; ?>" class="button header-book-now">
            <?php echo esc_html( $cta_text ); ?>
        </a>
    </div>
    <?php
}

/**
 * 5. WOOCOMMERCE UTILITIES
 */
if ( class_exists( 'WooCommerce' ) ) {

    add_action( 'get_header', 'grand_xp_remove_storefront_sidebar' );
    function grand_xp_remove_storefront_sidebar() {
        if ( is_product() || is_product_category()) {
            remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
        }
    }

    add_action( 'woocommerce_checkout_process', 'grand_xp_anti_fraud_ip_checker', 10 );
    function grand_xp_anti_fraud_ip_checker() {
        $customer_ip = WC_Geolocation::get_ip_address();
        
        $last_1_hour_from_ip_results = wc_get_orders( array(
            'date_created'        => '>=' . ( time() - 3600 ),
            'customer_ip_address' => $customer_ip,
            'paginate'            => true
        ) );
        
        if( empty( $customer_ip ) || ( isset( $last_1_hour_from_ip_results->total ) && $last_1_hour_from_ip_results->total > 10 ) ) { 
            wc_add_notice( 'Too many attempts in the last hour. Please return later.', 'error' );
        }
    }
}

/**
 * 6. CUSTOM EDITOR STYLES
 */
add_action( 'enqueue_block_editor_assets', 'grand_xp_custom_editor_colors' );
function grand_xp_custom_editor_colors() {
    global $post;

    if ( ! isset( $post ) ) return;

    if ( 'post' !== $post->post_type ) {
        $custom_editor_css = '
            .editor-styles-wrapper, .editor-styles-wrapper p, .editor-styles-wrapper li { color: #ffffff !important; }
            .editor-styles-wrapper h1, .editor-styles-wrapper h2, .editor-styles-wrapper h3, 
            .editor-styles-wrapper h4, .editor-styles-wrapper h5, .editor-styles-wrapper h6 { color: #ffffff !important; }
            .editor-styles-wrapper { background-color: #222222 !important; }
        ';
        wp_add_inline_style( 'wp-block-library', $custom_editor_css );
    }
}

/**
 * 7. LOCAL BUSINESS SCHEMA (JSON-LD)
 */
add_action( 'wp_head', 'grand_xp_output_local_schema', 20 );
function grand_xp_output_local_schema() {
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "SportsActivityLocation",
          "@id": "https://grand-experiences.com/#organization",
          "name": "Grand Experiences",
          "url": "https://grand-experiences.com/",
          "telephone": "+1-226-240-8315",
          "email": "info@grand-experiences.com",
          "address": {
            "@type": "PostalAddress",
            "streetAddress": "109 Grand River St N",
            "addressLocality": "Paris",
            "addressRegion": "ON",
            "postalCode": "N3L 2M4",
            "addressCountry": "CA"
          },
          "geo": {
            "@type": "GeoCoordinates",
            "latitude": 43.1942, 
            "longitude": -80.3844
          },
          "image": "https://grand-experiences.com/wp-content/uploads/2025/10/paris-to-brant-aerial-william-st-bridge-1-scaled.avif",
          "priceRange": "$$",
          "openingHoursSpecification": [
            {
              "@type": "OpeningHoursSpecification",
              "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
              "opens": "09:00",
              "closes": "17:00"
            }
          ],
          "sameAs": [
            "https://www.facebook.com/GrandXperiences/",
            "https://www.instagram.com/grand_experiences",
            "https://www.youtube.com/@grand_experiences",
            "https://www.linkedin.com/company/grand-experiences/",
            "https://x.com/GrandExpCo",
            "https://www.threads.net/@grand_experiences"
          ]
        }
      ]
    }
    </script>
    <?php
}

/**
 * 8. BLOG EXCERPTS
 * Swaps Storefront's full content for excerpts on archive pages
 */
add_action( 'init', 'grand_xp_enable_excerpts_on_archive' );
function grand_xp_enable_excerpts_on_archive() {
    remove_action( 'storefront_loop_post', 'storefront_post_content', 30 );
    add_action( 'storefront_loop_post', 'grand_xp_custom_loop_content', 30 );
}

function grand_xp_custom_loop_content() {
    ?>
    <div class="entry-content">
        <?php 
        the_excerpt(); 
        ?>
        <p><a class="button" href="<?php the_permalink(); ?>">Read More</a></p>
    </div>
    <?php
}

/**
 * 9. REMOVE ARCHIVE PREFIXES
 * Removes "Category:", "Tag:", etc. from archive titles
 */
add_filter( 'get_the_archive_title', 'grand_xp_remove_archive_prefix' );
function grand_xp_remove_archive_prefix( $title ) {
    if ( is_category() ) {
        $title = single_cat_title( '', false );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
    } elseif ( is_author() ) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif ( is_post_type_archive() ) {
        $title = post_type_archive_title( '', false );
    }
    return $title;
}
