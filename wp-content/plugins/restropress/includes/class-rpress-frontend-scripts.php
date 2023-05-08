<?php
/**
 * Handle frontend scripts
 *
 * @package RestroPress/Classes
 * @version 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Frontend scripts class.
 */
 class RP_Frontend_Scripts {

  /**
   * Contains an array of script handles registered by RP.
   *
   * @var array
   */
  private static $scripts = array();

  /**
   * Contains an array of script handles registered by RP.
   *
   * @var array
   */
  private static $styles = array();

  /**
   * Contains an array of script handles localized by RP.
   *
   * @var array
   */
  private static $wp_localize_scripts = array();

  /**
   * Hook in methods.
   */
  public static function init() {
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ) );
    add_action( 'wp_head', array( __CLASS__, 'rp_head_styles' ) );
  }

  /**
   * Return asset URL.
   *
   * @param string $path Assets path.
   * @return string
   */
  private static function get_asset_url( $path ) {
    return apply_filters( 'rpress_get_asset_url', plugins_url( $path, RP_PLUGIN_FILE ), $path );
  }

  /**
   * Register a script for use.
   *
   * @uses   wp_register_script()
   * @param  string   $handle    Name of the script. Should be unique.
   * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
   * @param  string[] $deps      An array of registered script handles this script depends on.
   * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
   * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
   */
  private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = RP_VERSION_CUSTOM, $in_footer = true ) {
    self::$scripts[] = $handle;
    wp_register_script( $handle, $path, $deps, $version, $in_footer );
  }

  /**
   * Register and enqueue a script for use.
   *
   * @uses   wp_enqueue_script()
   * @param  string   $handle    Name of the script. Should be unique.
   * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
   * @param  string[] $deps      An array of registered script handles this script depends on.
   * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
   * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
   */
  private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = RP_VERSION_CUSTOM, $in_footer = true ) {
    if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
      self::register_script( $handle, $path, $deps, $version, $in_footer );
    }
    wp_enqueue_script( $handle );
  }

  /**
   * Register a style for use.
   *
   * @uses   wp_register_style()
   * @param  string   $handle  Name of the stylesheet. Should be unique.
   * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
   * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
   * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
   * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
   * @param  boolean  $has_rtl If has RTL version to load too.
   */
  private static function register_style( $handle, $path, $deps = array(), $version = RP_VERSION_CUSTOM, $media = 'all', $has_rtl = false ) {
    self::$styles[] = $handle;
    wp_register_style( $handle, $path, $deps, $version, $media );

    if ( $has_rtl ) {
      wp_style_add_data( $handle, 'rtl', 'replace' );
    }
  }

  /**
   * Register and enqueue a styles for use.
   *
   * @uses   wp_enqueue_style()
   * @param  string   $handle  Name of the stylesheet. Should be unique.
   * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
   * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
   * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
   * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
   * @param  boolean  $has_rtl If has RTL version to load too.
   */
  private static function enqueue_style( $handle, $path = '', $deps = array(), $version = RP_VERSION_CUSTOM, $media = 'all', $has_rtl = false ) {
    if ( ! in_array( $handle, self::$styles, true ) && $path ) {
      self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
    }
    wp_enqueue_style( $handle );
  }

  /**
   * Register all RP scripts.
   */
  private static function register_scripts() {

    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

    $register_scripts = array(
      'jquery-cookies'     => array(
        'src'     => self::get_asset_url( 'assets/js/jquery.cookies.min.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'sticky-sidebar'     => array(
        'src'     => self::get_asset_url( 'assets/js/sticky-sidebar/rpress-sticky-sidebar.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '1.7.0',
      ),
      'timepicker'     => array(
        'src'     => self::get_asset_url( 'assets/js/timepicker/jquery.timepicker' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '1.11.14',
      ),
      'rp-fancybox'     => array(
        'src'     => self::get_asset_url( 'assets/js/jquery.fancybox.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'rp-checkout' => array(
        'src'     => self::get_asset_url( 'assets/js/frontend/rp-checkout' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'jquery-payment' => array(
        'src'     => self::get_asset_url( 'assets/js/jquery.payment' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '3.0.0',
      ),
      'jquery-creditcard-validator' => array(
        'src'     => self::get_asset_url( 'assets/js/jquery.creditCardValidator' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '1.3.3',
      ),
      'jquery-chosen' => array(
        'src'     => self::get_asset_url( 'assets/js/jquery-chosen/chosen.jquery' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '1.8.2',
      ),
      'jquery-flot' => array(
        'src'     => self::get_asset_url( 'assets/js/jquery-flot/jquery-flot' . $suffix . '.js' ),
        'deps'    => array( 'jquery' ),
        'version' => '0.7',
      ),
      'rp-frontend' => array(
        'src'     => self::get_asset_url( 'assets/js/frontend/rp-frontend.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'rp-ajax' => array(
        'src'     => self::get_asset_url( 'assets/js/frontend/rp-ajax.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'rp-bootstrap-script' => array(
        'src'     => self::get_asset_url( 'assets/js/frontend/rp-bootstrap.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
      'rp-modal' => array(
        'src'     => self::get_asset_url( 'assets/js/frontend/rp-modal.js' ),
        'deps'    => array( 'jquery' ),
        'version' => RP_VERSION_CUSTOM,
      ),
    );

    foreach ( $register_scripts as $name => $props ) {
      self::register_script( $name, $props['src'], $props['deps'], $props['version'] );
    }
  }

  /**
   * Register/queue frontend scripts.
   */
  public static function load_scripts() {

    global $post;

    self::register_scripts();
    self::enqueue_script( 'jquery-cookies' );

    if( is_restropress_page() ) {
      self::enqueue_script( 'sticky-sidebar' );
      self::enqueue_script( 'rp-fancybox' );
      self::enqueue_script( 'timepicker' );
      self::enqueue_script( 'jquery-chosen' );
      self::enqueue_script( 'rp-modal' );
      self::enqueue_script( 'rp-frontend' );
    }

    if ( rpress_is_checkout() ) {
      self::enqueue_script( 'rp-checkout' );
      if ( rpress_is_cc_verify_enabled() ) {
        self::enqueue_script( 'jquery-creditcard-validator' );
        self::enqueue_script( 'jquery-payment' );
      }
    }

    if ( !rpress_is_ajax_disabled() ) {
      self::enqueue_script( 'rp-ajax' );
    }

    if ( rpress_get_option( 'use_external_bootstrap_script') !== '1' ) {
      wp_enqueue_script( 'rp-bootstrap-script' );
    }

    $add_to_cart    = apply_filters( 'rp_add_to_cart', __( 'Add To Cart', 'restropress' ) );
    $update_cart    = apply_filters( 'rp_update_cart', __( 'Update Cart', 'restropress' ) );
    $added_to_cart  = apply_filters( 'rp_added_to_cart', __( 'Added To Cart', 'restropress' ) );
    $please_wait_text = __( 'Please Wait...', 'restropress' );

    $color = rpress_get_option( 'checkout_color', 'red' );
    $service_options = !empty( rpress_get_option( 'enable_service' ) ) ? rpress_get_option( 'enable_service' ) : 'delivery_and_pickup' ;
    $minimum_order_error_title = !empty( rpress_get_option( 'minimum_order_error_title' ) ) ? rpress_get_option( 'minimum_order_error_title' ) : 'Minimum Order Error' ;
    $service_change_text = apply_filters( 'rp_service_change_text', 'Change?' );
    $expire_cookie_time = !empty( rpress_get_option( 'expire_service_cookie' ) ) ? rpress_get_option( 'expire_service_cookie' ) : 30;

    $params = array(
      'estimated_tax'             => rpress_get_tax_name(),
      'total_text'                => __( 'Subtotal', 'restropress'),
      'ajaxurl'                   => rpress_get_ajax_url(),
      'show_products_nonce'       => wp_create_nonce( 'show-products' ),
      'add_to_cart'               => $add_to_cart,
      'update_cart'               => $update_cart,
      'added_to_cart'             => $added_to_cart,
      'please_wait'               => $please_wait_text,
      'at'                        => __( 'at', 'restropress' ),
      'color'                     => $color,
      'service_change_text'       => $service_change_text,
      'checkout_page'             => rpress_get_checkout_uri(),
      'add_to_cart_nonce'         => wp_create_nonce( 'add-to-cart' ),
      'service_type_nonce'        => wp_create_nonce( 'service-type' ),
      'service_options'           => $service_options,
      'minimum_order_title'       => $minimum_order_error_title,
      'edit_cart_fooditem_nonce'  => wp_create_nonce( 'edit-cart-fooditem' ),
      'update_cart_item_nonce'    => wp_create_nonce( 'update-cart-item' ),
      'clear_cart_nonce'          => wp_create_nonce( 'clear-cart' ),
      'update_service_nonce'      => wp_create_nonce( 'update-service' ),
      'proceed_checkout_nonce'    => wp_create_nonce( 'proceed-checkout' ),
      'error'                     => __( 'Error', 'restropress' ),
      'change_txt'                => __( 'Change?', 'restropress' ),
      'currency'                  => rpress_get_currency(),
      'currency_sign'             => rpress_currency_filter(),
      'expire_cookie_time'        => $expire_cookie_time,
    );
    wp_localize_script( 'rp-frontend', 'rp_scripts', $params );

    $co_params = array(
      'ajaxurl'             => rpress_get_ajax_url(),
      'checkout_nonce'      => wp_create_nonce('rpress_checkout_nonce'),
      'checkout_error_anchor' => '#rpress_purchase_submit',
      'currency_sign'         => rpress_currency_filter(''),
      'currency_pos'          => rpress_get_option('currency_position', 'before'),
      'decimal_separator'     => rpress_get_option('decimal_separator', '.'),
      'thousands_separator'   => rpress_get_option('thousands_separator', ','),
      'no_gateway'            => __('Please select a payment method', 'restropress'),
      'no_discount'           => __('Please enter a discount code', 'restropress'), // Blank discount code message
      'enter_discount'        => __('Enter coupon code', 'restropress'),
      'discount_applied'      => __('Discount Applied', 'restropress'), // Discount verified message
      'no_email'              => __('Please enter an email address before applying a discount code', 'restropress'),
      'no_username'           => __('Please enter a username before applying a discount code', 'restropress'),
      'purchase_loading'      => __('Please Wait...', 'restropress'),
      'complete_purchase'     => rpress_get_checkout_button_purchase_label(),
      'taxes_enabled'         => rpress_use_taxes() ? '1' : '0',
      'rpress_version'        => RP_VERSION
    );
    wp_localize_script( 'rp-checkout', 'rpress_global_vars', apply_filters('rpress_global_checkout_script_vars', $co_params ) );

    if ( isset( $post->ID ) )
      $position = rpress_get_item_position_in_cart( $post->ID );

    $has_purchase_links = false;
    if ((!empty($post->post_content) && (has_shortcode($post->post_content, 'purchase_link') || has_shortcode($post->post_content, 'fooditems'))) || is_post_type_archive('fooditem'))
      $has_purchase_links = true;

    $pickup_time_enabled = rpress_is_service_enabled( 'pickup' );
    $delivery_time_enabled = rpress_is_service_enabled( 'delivery' );

    $ajax_params = array(
      'ajaxurl'                 => rpress_get_ajax_url(),
      'position_in_cart'        => isset($position) ? $position : -1,
      'has_purchase_links'      => $has_purchase_links,
      'already_in_cart_message' => __('You have already added this item to your cart', 'restropress'), // Item already in the cart message
      'empty_cart_message'      => __('Your cart is empty', 'restropress'), // Item already in the cart message
      'loading'                 => __('Loading', 'restropress'), // General loading message
      'select_option'           => __('Please select an option', 'restropress'), // Variable pricing error with multi-purchase option enabled
      'is_checkout'             => rpress_is_checkout() ? '1' : '0',
      'default_gateway'         => rpress_get_default_gateway(),
      'redirect_to_checkout'    => (rpress_straight_to_checkout() || rpress_is_checkout()) ? '1' : '0',
      'checkout_page'           => rpress_get_checkout_uri(),
      'permalinks'              => get_option('permalink_structure') ? '1' : '0',
      'quantities_enabled'      => rpress_item_quantities_enabled(),
      'taxes_enabled'           => rpress_use_taxes() ? '1' : '0', // Adding here for widget, but leaving in checkout vars for backcompat
      'open_hours'              => rpress_get_option('open_time'),
      'close_hours'             => rpress_get_option('close_time'),
      'please_wait'             => __( 'Please Wait', 'restropress'),
      'add_to_cart'             => __( 'Add To Cart', 'restropress'),
      'update_cart'             => __( 'Update Cart', 'restropress'),
      'button_color'            => rpress_get_option('checkout_color', 'red'),
      'delivery_time_enabled'   => $delivery_time_enabled,
      'pickup_time_enabled'     => $pickup_time_enabled,
      'display_date'            => rp_current_date(),
      'current_date'            => current_time( 'Y-m-d' ),
      'current_time'            => current_time( 'H:i' ),
      'service_type'            => 'pickup',
      'service_label'           => __( ucfirst('pickup'), 'restropress' ),
      'update'                  => __( 'update', 'restropress' ),
      'subtotal'                => __( 'SubTotal', 'restropress' ),
      'change_txt'              => __( 'Change?', 'restropress' ),
      'fee'                     => __( 'Fee', 'restropress' ),
      'color'                   => rpress_get_option( 'checkout_color', 'red' ),
    );
    wp_localize_script( 'rp-ajax', 'rpress_scripts', apply_filters('rpress_ajax_script_vars', $ajax_params ) );

    // CSS Styles.
    $enqueue_styles = self::get_styles();
    if ( $enqueue_styles && is_restropress_page() ) {
      foreach ( $enqueue_styles as $handle => $args ) {
        if ( ! isset( $args['has_rtl'] ) ) {
          $args['has_rtl'] = false;
        }
        self::enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'], $args['has_rtl'] );
      }
    }
  }

  /**
   * Register Style
   * Code taken from scripts.php present in RP2.5
   *
   */
  public static function register_styles() {

    if ( rpress_get_option('disable_styles', false) ) {
      return;
    }

    if( !is_restropress_page() ) {
      return;
    }

    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

    $file = 'rpress' . $suffix . '.css';
    $templates_dir = rpress_get_theme_template_dir_name();

    $child_theme_style_sheet    = trailingslashit(get_stylesheet_directory()) . $templates_dir . $file;
    $child_theme_style_sheet_2  = trailingslashit(get_stylesheet_directory()) . $templates_dir . 'rpress.css';
    $parent_theme_style_sheet   = trailingslashit(get_template_directory()) . $templates_dir . $file;
    $parent_theme_style_sheet_2 = trailingslashit(get_template_directory()) . $templates_dir . 'rpress.css';
    $rpress_plugin_style_sheet  = trailingslashit(rpress_get_templates_dir()) . $file;

    // Look in the child theme directory first, followed by the parent theme, followed by the RPRESS core templates directory
    // Also look for the min version first, followed by non minified version, even if SCRIPT_DEBUG is not enabled.
    // This allows users to copy just rpress.css to their theme
    if (file_exists($child_theme_style_sheet) || (!empty($suffix) && ($nonmin = file_exists($child_theme_style_sheet_2)))) {
      if (!empty($nonmin)) {
        $url = trailingslashit(get_stylesheet_directory_uri()) . $templates_dir . 'rpress.css';
      } else {
        $url = trailingslashit(get_stylesheet_directory_uri()) . $templates_dir . $file;
      }
    } elseif (file_exists($parent_theme_style_sheet) || (!empty($suffix) && ($nonmin = file_exists($parent_theme_style_sheet_2)))) {
      if (!empty($nonmin)) {
        $url = trailingslashit(get_template_directory_uri()) . $templates_dir . 'rpress.css';
      } else {
        $url = trailingslashit(get_template_directory_uri()) . $templates_dir . $file;
      }
    } elseif (file_exists($rpress_plugin_style_sheet) || file_exists($rpress_plugin_style_sheet)) {
      $url = trailingslashit(rpress_get_templates_url()) . $file;
    }

    wp_register_style('rpress-styles', $url, array(), RP_VERSION_CUSTOM, 'all');
    wp_enqueue_style('rpress-styles');
  }

  /**
   * Load head styles
   *
   * Ensures fooditem styling is still shown correctly if a theme is using the CSS template file
   *
   * @since  1.0.0
   * @global $post
   * @return void
   */
  public static function rp_head_styles() {

    global $post;

    if (rpress_get_option('disable_styles', false) || !is_object($post)) {
        return;
    }

    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

    $file = 'rpress' . $suffix . '.css';
    $templates_dir = rpress_get_theme_template_dir_name();

    $child_theme_style_sheet    = trailingslashit(get_stylesheet_directory()) . $templates_dir . $file;
    $child_theme_style_sheet_2  = trailingslashit(get_stylesheet_directory()) . $templates_dir . 'rpress.css';
    $parent_theme_style_sheet   = trailingslashit(get_template_directory()) . $templates_dir . $file;
    $parent_theme_style_sheet_2 = trailingslashit(get_template_directory()) . $templates_dir . 'rpress.css';

    $has_css_template = false;

    if (has_shortcode($post->post_content, 'fooditems') && file_exists($child_theme_style_sheet) || file_exists($child_theme_style_sheet_2) || file_exists($parent_theme_style_sheet) || file_exists($parent_theme_style_sheet_2)) {
        $has_css_template = apply_filters('rpress_load_head_styles', true);
    }

    if (!$has_css_template) {
        return;
    } ?>

    <style>
    .rpress_fooditem {
      float:left;
    }
    .rpress_fooditem_columns_1 .rpress_fooditem {
      width: 100%;
    }
    .rpress_fooditem_columns_2 .rpress_fooditem {
      width:50%;
    }
    .rpress_fooditem_columns_0 .rpress_fooditem,.rpress_fooditem_columns_3 .rpress_fooditem {
      width:33%;
    }
    .rpress_fooditem_columns_4 .rpress_fooditem {
      width:25%;
    }
    .rpress_fooditem_columns_5 .rpress_fooditem {
      width:20%;
    }
    .rpress_fooditem_columns_6 .rpress_fooditem {
      width:16.6%;
    }
    </style>

    <?php
  }

  /**
   * Get styles for the frontend.
   *
   * @return array
   */
  public static function get_styles() {
    return apply_filters( 'rpress_enqueue_styles',
      array(
        'font-awesome'                => array(
          'src'     => 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
          'deps'    => '',
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),

        'rpress-frontend-icons'      => array(
          'src'     => self::get_asset_url( 'assets/css/frontend-icons.css' ),
          'deps'    => '',
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),

        'rp-bootstrap-styles'         => array(
          'src'     => self::get_asset_url( 'assets/css/rpress-bootstrap.css' ),
          'deps'    => array(),
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),

        'rp-fancybox'                 => array(
          'src'     => self::get_asset_url( 'assets/css/jquery.fancybox.css' ),
          'deps'    => array(),
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),

        'jquery-chosen'               => array(
          'src'     => self::get_asset_url( 'assets/css/chosen.css' ),
          'deps'    => array(),
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),

        'rp-frontend-styles'         => array(
          'src'     => self::get_asset_url( 'assets/css/rpress.css' ),
          'deps'    => array(),
          'version' => RP_VERSION_CUSTOM,
          'media'   => 'all',
          'has_rtl' => false,
        ),
      )
    );
  }
}

RP_Frontend_Scripts::init();