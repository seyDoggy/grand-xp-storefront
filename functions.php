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
 * 2. STOREFRONT GENERAL CLEANUP
 * Note: We removed the "Page Title" removal from here so we can do it smarter in Section 2b.
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

    // REMOVE: Date & Author from Blog Posts
    remove_action( 'storefront_post_header_before', 'storefront_post_meta', 10 );

    // REMOVE: Categories & Tags from Blog Posts
    remove_action( 'storefront_post_content_after', 'storefront_post_taxonomy', 10 );
    remove_action( 'storefront_loop_post', 'storefront_post_taxonomy', 40 );
    remove_action( 'storefront_single_post', 'storefront_post_taxonomy', 40 );
}

/**
 * 2b. SMART TITLE LOGIC (Universal Fix)
 * Hook: get_header
 * Logic: Hides titles on BOTH Pages and Single Posts by default.
 * Exception: Shows title only if Custom Field 'ge_show_title' is set to '1'.
 */
add_action( 'get_header', 'grand_xp_smart_remove_page_titles' );
function grand_xp_smart_remove_page_titles() {
    // Only run on singular content (Pages AND Blog Posts)
    if ( is_singular() ) {
        global $post;
        
        // Check for the override Custom Field
        $show_title = get_post_meta( $post->ID, 'ge_show_title', true );
        
        // If the Custom Field is EMPTY, remove the titles.
        if ( empty( $show_title ) ) {
            // 1. Remove Title from Standard Pages
            remove_action( 'storefront_page', 'storefront_page_header', 10 );
            
            // 2. Remove Title from Blog Posts (The missing piece!)
            remove_action( 'storefront_single_post', 'storefront_post_header', 10 );
        }
    }
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
 */
// Helper: Get the current ID safely
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
    
    // 1. CHECK OVERRIDES
    if ( $current_id ) {
        $item_id = get_post_meta( $current_id, 'fh_item_id', true );
        $flow_id = get_post_meta( $current_id, 'fh_flow_id', true );
        if ( ! empty( $item_id ) || ! empty( $flow_id ) ) {
            return true;
        }
    }
    
    // 2. CHECK EXCLUSIONS
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

// Add 'has-sticky-cta' class to body
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

    $current_id  = grand_xp_get_current_id();
    $item_id     = $current_id ? get_post_meta( $current_id, 'fh_item_id', true ) : '';
    $flow_id     = $current_id ? get_post_meta( $current_id, 'fh_flow_id', true ) : '';
    $custom_text = $current_id ? get_post_meta( $current_id, 'fh_cta_text', true ) : '';

    $cta_text = 'Book Your Adventure';
    $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/?full-items=yes&flow=1495255'; 
    $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', flow: 1495255, view: 'items' }));";

    if ( ! empty( $item_id ) ) {
        $cta_text = 'Book This Trip';
        $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/items/' . esc_attr( $item_id ) . '/?full-items=yes';
        $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', view: { item: " . esc_js( $item_id ) . " } }));";
    } elseif ( ! empty( $flow_id ) ) {
        $fh_url   = 'https://fareharbor.com/embeds/book/grand-experiences/?full-items=yes&flow=' . esc_attr( $flow_id );
        $fh_click = "return !(window.FH && FH.open({ shortname: 'grand-experiences', fallback: 'simple', fullItems: 'yes', flow: " . esc_js( $flow_id ) . ", view: 'items' }));";
    }

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
 */
add_action( 'init', 'grand_xp_enable_excerpts_on_archive' );
function grand_xp_enable_excerpts_on_archive() {
    remove_action( 'storefront_loop_post', 'storefront_post_content', 30 );
    add_action( 'storefront_loop_post', 'grand_xp_custom_loop_content', 30 );
}

function grand_xp_custom_loop_content() {
    ?>
    <div class="entry-content">
        <?php the_excerpt(); ?>
        <p><a class="button" href="<?php the_permalink(); ?>">Read More</a></p>
    </div>
    <?php
}

/**
 * 9. REMOVE ARCHIVE PREFIXES
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

/**
 * -------------------------------------------------------------------------
 * SCALABLE QUICK & BULK EDIT FOR CUSTOM FIELDS
 * -------------------------------------------------------------------------
 */

// 1. DEFINE YOUR FIELDS
function grand_xp_get_quick_edit_fields() {
    return array(
        'ge_show_title' => 'Show Title',
        'fh_item_id'    => 'FH Item ID',
        'fh_flow_id'    => 'FH Flow ID',
        'fh_cta_text'   => 'FH Button Text',
    );
}

// 2. ADD COLUMNS
add_filter( 'manage_page_posts_columns', 'grand_xp_add_columns' );
add_filter( 'manage_posts_columns', 'grand_xp_add_columns' );
function grand_xp_add_columns( $columns ) {
    $fields = grand_xp_get_quick_edit_fields();
    foreach ( $fields as $key => $label ) {
        $columns[ $key ] = $label;
    }
    return $columns;
}

// 3. POPULATE COLUMNS (Hidden data for Quick Edit JS)
add_action( 'manage_pages_custom_column', 'grand_xp_render_column_content', 10, 2 );
add_action( 'manage_posts_custom_column', 'grand_xp_render_column_content', 10, 2 );
function grand_xp_render_column_content( $column_name, $post_id ) {
    $fields = grand_xp_get_quick_edit_fields();
    if ( array_key_exists( $column_name, $fields ) ) {
        $value = get_post_meta( $post_id, $column_name, true );
        // Display value for admin + Hidden div for JS to grab
        echo '<span class="ge-admin-view">' . esc_html( $value ) . '</span>';
        echo '<div class="ge-hidden-value" data-field="' . esc_attr( $column_name ) . '" style="display:none;">' . esc_html( $value ) . '</div>';
    }
}

// 4. ADD INPUTS TO QUICK EDIT AND BULK EDIT
add_action( 'quick_edit_custom_box', 'grand_xp_add_quick_edit_inputs', 10, 2 );
add_action( 'bulk_edit_custom_box', 'grand_xp_add_quick_edit_inputs', 10, 2 ); // <--- ADDED THIS HOOK
function grand_xp_add_quick_edit_inputs( $column_name, $post_type ) {
    $fields = grand_xp_get_quick_edit_fields();
    if ( array_key_exists( $column_name, $fields ) ) {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php echo esc_html( $fields[$column_name] ); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="<?php echo esc_attr( $column_name ); ?>" class="ge-input-<?php echo esc_attr( $column_name ); ?>" value="">
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }
}

// 5. JAVASCRIPT (Populates Quick Edit only; Bulk Edit stays blank)
add_action( 'admin_footer', 'grand_xp_quick_edit_script' );
function grand_xp_quick_edit_script() {
    $screen = get_current_screen();
    if ( $screen->base != 'edit' ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var $wp_inline_edit = inlineEditPost.edit;
        
        inlineEditPost.edit = function( id ) {
            $wp_inline_edit.apply( this, arguments );

            var post_id = 0;
            if ( typeof( id ) == 'object' ) {
                post_id = parseInt( this.getId( id ) );
            }

            if ( post_id > 0 ) {
                var $row = $( '#post-' + post_id );
                var $edit_row = $( '#edit-' + post_id );
                
                // Populate Quick Edit inputs from the hidden column data
                $row.find( '.ge-hidden-value' ).each(function() {
                    var field_name = $(this).data('field');
                    var value = $(this).text();
                    $edit_row.find( 'input[name="' + field_name + '"]' ).val( value );
                });
            }
        };
    });
    </script>
    <?php
}

// 6. SMART SAVE (Handles both Quick & Bulk)
add_action( 'save_post', 'grand_xp_save_quick_edit' );
function grand_xp_save_quick_edit( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    $fields = grand_xp_get_quick_edit_fields();
    
    // Determine context: Quick Edit ('inline-save') or Bulk/Other
    $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

    foreach ( $fields as $key => $label ) {
        if ( isset( $_REQUEST[ $key ] ) ) {
            $value = sanitize_text_field( $_REQUEST[ $key ] );
            
            // LOGIC GATE:
            // 1. If Quick Edit ('inline-save') -> Save everything (allow wiping data).
            // 2. If Standard Edit ('editpost') -> Save everything.
            // 3. If Bulk Edit (anything else) -> Only save if User typed something (!empty).
            
            if ( $action === 'inline-save' || $action === 'editpost' ) {
                update_post_meta( $post_id, $key, $value );
            } elseif ( ! empty( $value ) ) {
                // Bulk Edit safety: Only update if the user actually typed a value
                update_post_meta( $post_id, $key, $value );
            }
        }
    }
}