<?php
/**
 * RestroPress RP_AJAX. AJAX Event Handlers.
 *
 * @class   RP_AJAX
 * @package RestroPress/Classes
 * @since  3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * RP_Ajax class.
 */
class RP_AJAX {

  /**
   * Hook in ajax handlers.
   */
  public static function init() {

    add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
    add_action( 'template_redirect', array( __CLASS__, 'do_rp_ajax' ), 0 );
    self::add_ajax_events();
  }

  /**
   * Get RP Ajax Endpoint.
   *
   * @param string $request Optional.
   *
   * @return string
   */
  public static function get_endpoint( $request = '' ) {
    return esc_url_raw( apply_filters( 'rp_ajax_get_endpoint', add_query_arg( 'rp-ajax', $request, home_url( '/', 'relative' ) ), $request ) );
  }

  /**
   * Set RP AJAX constant and headers.
   */
  public static function define_ajax() {

    // phpcs:disable
    if ( ! empty( $_GET['rp-ajax'] ) ) {
      rp_maybe_define_constant( 'DOING_AJAX', true );
      rp_maybe_define_constant( 'RP_DOING_AJAX', true );
      if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
        @ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
      }
      $GLOBALS['wpdb']->hide_errors();
    }
  }

  /**
   * Send headers for RP Ajax Requests.
   *
   */
  private static function rp_ajax_headers() {

    if ( ! headers_sent() ) {
      send_origin_headers();
      send_nosniff_header();
      rp_nocache_headers();
      header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
      header( 'X-Robots-Tag: noindex' );
      status_header( 200 );
    } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
      headers_sent( $file, $line );
      trigger_error( "rp_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
    }
  }

  /**
   * Check for RP Ajax request and fire action.
   */
  public static function do_rp_ajax() {

    global $wp_query;

    if ( ! empty( $_GET['rp-ajax'] ) ) {
      $wp_query->set( 'rp-ajax', sanitize_text_field( wp_unslash( $_GET['rp-ajax'] ) ) );
    }

    $action = $wp_query->get( 'rp-ajax' );

    if ( $action ) {
      self::rp_ajax_headers();
      $action = sanitize_text_field( $action );
      do_action( 'rp_ajax_' . $action );
      wp_die();
    } // phpcs:enable
  }

  /**
   * Hook in methods - uses WordPress ajax handlers (admin-ajax).
   */
  public static function add_ajax_events() {

    $ajax_events_nopriv = array(
      'show_products',
      'add_to_cart',
      'show_delivery_options',
      'check_service_slot',
      'edit_cart_fooditem',
      'update_cart_items',
      'remove_from_cart',
      'clear_cart',
      'proceed_checkout',
      'get_subtotal',
      'apply_discount',
      'remove_discount',
      'checkout_login',
      'checkout_register',
      'recalculate_taxes',
      'get_states',
      'fooditem_search',
      'update_quantity',
      'apply_fee',
    );

    foreach ( $ajax_events_nopriv as $ajax_event ) {
      add_action( 'wp_ajax_rpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
      add_action( 'wp_ajax_nopriv_rpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );

      // RP AJAX can be used for frontend ajax requests.
      add_action( 'rp_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
    }

    $ajax_events = array(
      'add_addon',
      'load_addon_child',
      'add_price',
      'add_category',
	  'update_addon_status',
      'get_order_details',
      'update_order_status',
      'check_for_fooditem_price_variations',
      'admin_order_addon_items',
      'customer_search',
      'user_search',
      'search_users',
      'check_new_orders',
      'activate_addon_license',
      'deactivate_addon_license'
    );

    foreach ( $ajax_events as $ajax_event ) {
      add_action( 'wp_ajax_rpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
    }
  }

  /**
   * Add an variable price row.
   */
  public static function add_price() {

    ob_start();

    check_ajax_referer( 'add-price', 'security' );

    $current = $_POST['i'];

    include 'admin/fooditems/views/html-fooditem-variable-price.php';
    wp_die();
  }

  /**
   * Add an addon row.
   */
  public static function add_addon() {

    ob_start();

    check_ajax_referer( 'add-addon', 'security' );

    $current = $_POST['i'];

    if( $_POST['iscreate'] == 'true' ){
      $addon_types  = rpress_get_addon_types();
      include 'admin/fooditems/views/html-fooditem-new-addon-category.php';
    }
    else {
      $addon_categories = rpress_get_addons();
      include 'admin/fooditems/views/html-fooditem-addon.php';
    }

    wp_die();
  }


  /**
   * Add Category to fooditem
   */
  public static function add_category() {

    check_ajax_referer( 'add-category', 'security' );

    $parent = $_POST['parent'];
    $name   = $_POST['name'];
    $args   = apply_filters( 'rpress_add_category_args', array( 'parent' => $parent ) );

    $category = wp_insert_term( $name, 'food-category', $args );

    wp_send_json( $category );
  }

  public static function update_addon_status() {
    if ( isset( $_GET['status'] ) && isset( $_GET['term_id'] ) ) {

      $term_id = $_GET['term_id'];
      $new_status = $_GET['status'];

      $status = sanitize_text_field( wp_unslash( $new_status ) );
      $statuses = rpress_get_addon_statuses();

      if ( array_key_exists( $status, $statuses ) ) {
        $term = get_term( $term_id, 'addon_category' );
        $term_meta = get_option( "taxonomy_term_$term->term_id", array() );
        $term_meta['status'] = $new_status;
        update_option( "taxonomy_term_$term->term_id", $term_meta);
      }
    }

    echo "OK " . json_encode($term_meta);
    rpress_die();
  }
  
  /**
  *
  * Change order status from order history
  *
  * @since 3.0
  * @return mixed
  */
  public static function update_order_status() {

    if ( isset( $_GET['status'] ) && isset( $_GET['payment_id'] ) ) {

      $payment_id = $_GET['payment_id'];
      $new_status = $_GET['status'];

      $status = sanitize_text_field( wp_unslash( $new_status ) );
      $statuses = rpress_get_order_statuses();

      if ( array_key_exists( $status, $statuses ) ) {
        rpress_update_order_status( $payment_id, $status );
      }
    }

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=rpress-payment-history' ) );
    exit;
  }

  /**
   * Load addon child items when after selecting parent addon
   */
  public static function load_addon_child() {

    check_ajax_referer( 'load-addon', 'security' );

    $parent = $_POST['parent'];

    $addon_items = rpress_get_addons( $parent );

    $current = $_POST['i'];

    $output = '<ul class="rp-addon-items">';
    foreach( $addon_items as $addon_item ){

      $addon_price = rpress_get_addon_data( $addon_item->term_id, 'price' );
      $addon_price = !empty( $addon_price ) ? rpress_currency_filter( rpress_format_amount( $addon_price ) ) : '0.00';
      $parent_class = ( $addon_item->parent == 0 ) ? 'rp-parent-addon' : 'rp-child-addon';

      $output .= '<li class="  ' . $parent_class . ' ">';
      $output .= '<input type="checkbox" value="' . $addon_item->term_id . '" id="' . $addon_item->slug .'" name="addons[' . $current . '][items][]" class="rp-checkbox">';
      $output .= '<label for="' . $addon_item->slug .'">' . $addon_item->name .'</label>';
      $output .= '<span class="rp-addon-price">('.$addon_price .')</span>';
      $output .= '</li>';
    }
    $output .= '</ul>';

    echo $output;
    wp_die();
  }

  /**
   * Load Fooditems List in the popup
   */
  public static function show_products() {

    check_ajax_referer( 'show-products', 'security', false );

    if ( empty( $_POST['fooditem_id'] ) )
      return;

    $fooditem_id = $_POST['fooditem_id'];

    $price = '';

    if ( !empty( $fooditem_id ) ) {
      //Check item is variable or simple
      if ( rpress_has_variable_prices( $fooditem_id ) ) {
        $price = rpress_get_lowest_price_option( $fooditem_id );
      }
      else {
        $price = rpress_get_fooditem_price( $fooditem_id );
      }
    }

    if ( !empty( $price ) ) {
      $formatted_price = rpress_currency_filter( rpress_format_amount( $price ) );
    }

    $food_title     = get_the_title( $fooditem_id );
    $fooditem_desc  = get_post_field( 'post_content', $fooditem_id );
    $item_addons    = get_fooditem_lists( $fooditem_id, $cart_key = '' );

    ob_start();
    rpress_get_template_part( 'rpress', 'show-products' );
    $data = ob_get_clean();

    $data = str_replace( '{fooditemslist}', $item_addons, $data );
    $data = str_replace( '{itemdescription}', $fooditem_desc, $data );

    $response = array(
      'price'       => $formatted_price,
      'price_raw'   => $price,
      'html'        => $data,
      'html_title'  => apply_filters( 'rpress_modal_title' , $food_title ),
    );

    wp_send_json_success($response);
    rpress_die();
  }

  /**
   * Show Service Options in the popup
   */
  public static function show_delivery_options() {

    check_ajax_referer( 'service-type', 'security', false );

    $fooditem_id = isset( $_POST['fooditem_id'] ) ? $_POST['fooditem_id'] : '';

    $get_addons = rpress_get_delivery_steps( $fooditem_id );

    $response = array(
      'html'        => $get_addons,
      'html_title'  => apply_filters('rpress_delivery_options_title', __( 'Your Order Settings', 'restropress' ) ),
    );

    wp_send_json_success( $response );
    rpress_die();
  }

  /**
   * Check Service Options availibility
   */
  public static function check_service_slot() {
    $response = apply_filters( 'rpress_check_service_slot', $_POST );
    $response = apply_filters( 'rpress_validate_slot', $response );
    wp_send_json( $response );
    wp_die();
  }

  /**
   * Edit fooditem in the popup
   */
  public static function edit_cart_fooditem() {

    check_ajax_referer( 'edit-cart-fooditem', 'security', false );

    $cart_key = ! empty( $_POST['cartitem_id'] ) ? $_POST['cartitem_id'] : 0 ;
    $cart_key = absint( $cart_key );
    $fooditem_id = ! empty( $_POST['fooditem_id'] ) ? $_POST['fooditem_id'] : '' ;
    $food_title = ! empty( $_POST['fooditem_name'] ) ? $_POST['fooditem_name'] : get_the_title( $fooditem_id );
    $fooditem_desc  = get_post_field( 'post_content', $fooditem_id );

    if ( !empty( $fooditem_id)  ) {

      $price = '';

      if ( !empty( $fooditem_id ) ) {
        //Check item is variable or simple
        if ( rpress_has_variable_prices( $fooditem_id ) ) {
          $price = rpress_get_lowest_price_option( $fooditem_id );
        } else {
          $price = rpress_get_fooditem_price( $fooditem_id );
        }
      }

      if ( !empty( $price ) ) {
        $formatted_price = rpress_currency_filter( rpress_format_amount( $price ) );
      }

      $parent_addons = get_fooditem_lists( $fooditem_id, $cart_key );
      $special_instruction = rpress_get_instruction_by_key( $cart_key );

      ob_start();
      rpress_get_template_part( 'rpress', 'edit-product' );
      $data = ob_get_clean();

      $data = str_replace( '{itemdescription}', $fooditem_desc, $data );
      $data = str_replace( '{fooditemslist}', $parent_addons, $data );
      $data = str_replace( '{cartinstructions}', $special_instruction, $data );
    }

    $response = array(
      'price'       => $formatted_price,
      'price_raw'   => $price,
      'html'        => $data,
      'html_title'  => apply_filters( 'rpress_modal_title' , $food_title),
    );

    wp_send_json_success( $response );
    rpress_die();
  }

  /**
   * Add To Cart in the popup
   */
  public static function add_to_cart() {

    check_ajax_referer( 'add-to-cart', 'security', false );

    if ( empty( $_POST['fooditem_id'] ) ) {
      return;
    }

    $fooditem_id  = $_POST['fooditem_id'];
    $quantity     = ! empty( $_POST['fooditem_qty'] ) ? $_POST['fooditem_qty'] : 1;
    $instructions = ! empty( $_POST['special_instruction'] ) ? $_POST['special_instruction'] : '';
    $addon_items  = ! empty( $_POST['post_data'] ) ? $_POST['post_data'] : '';

    $items   = '';
    $options = array();

    //Check whether the fooditem has variable pricing
    if ( rpress_has_variable_prices( $fooditem_id ) ) {
      $price_id = !empty( $addon_items[0]['value'] ) ? $addon_items[0]['value'] : 0;
      $options['price_id'] = $price_id;
      $options['price']   = rpress_get_price_option_amount( $fooditem_id, $price_id );
    } else {
      $options['price'] = rpress_get_fooditem_price( $fooditem_id );
    }

    $options['id'] = $fooditem_id;
    $options['quantity'] = $quantity;
    $options['instruction'] = $instructions;

    if ( is_array( $addon_items ) && !empty( $addon_items ) ) {

      foreach( $addon_items as $key => $get_items ) {

        $addon_data = explode( '|', $get_items[ 'value' ] );

        if ( is_array( $addon_data ) && !empty( $addon_data ) ) {

          $addon_item_like = isset( $addon_data[3] ) ? $addon_data[3] : 'checkbox';

          $addon_id     = ! empty( $addon_data[0] ) ? $addon_data[0] : '';
          $addon_qty    = ! empty( $addon_data[1] ) ? $addon_data[1] : '';
          $addon_price  = ! empty( $addon_data[2] ) ? $addon_data[2] : '';

          $addon_details = get_term_by( 'id', $addon_id, 'addon_category' );

          if (  $addon_details ) {

            $addon_item_name = $addon_details->name;

            $options['addon_items'][$key]['addon_item_name'] = $addon_item_name;
            $options['addon_items'][$key]['addon_id'] = $addon_id;
            $options['addon_items'][$key]['price'] = $addon_price;
            $options['addon_items'][$key]['quantity'] = $addon_qty;
          }
        }
      }
    }

    $key = rpress_add_to_cart( $fooditem_id, $options );

    $item = array(
      'id'      => $fooditem_id,
      'options' => $options
    );

    $item   = apply_filters( 'rpress_ajax_pre_cart_item_template', $item );
    $items .= rpress_get_cart_item_template( $key, $item, true, $data_key = $key );

    $return = array(
     'subtotal'      => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
     'total'         => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_total() ) ), ENT_COMPAT, 'UTF-8' ),
     'cart_item'     => $items,
     'cart_key'      => $key,
     'cart_quantity' => html_entity_decode( rpress_get_cart_quantity() )
    );

    if ( rpress_use_taxes() ) {
      $cart_tax = (float) rpress_get_cart_tax();
      $return['taxes'] = html_entity_decode( rpress_currency_filter( rpress_format_amount( $cart_tax ) ), ENT_COMPAT, 'UTF-8' );
    }

    $return = apply_filters( 'rpress_cart_data', $return );

    wp_send_json( $return );
    rpress_die();
  }

  /**
   * Update Cart Items
   */
  public static function update_cart_items() {

    check_ajax_referer( 'update-cart-item', 'security', false );

    $cart_key     = isset( $_POST['fooditem_cartkey'] ) ? $_POST['fooditem_cartkey'] : '';
    $fooditem_id  = isset( $_POST['fooditem_id'] ) ? $_POST['fooditem_id'] : '';
    $item_qty     = isset( $_POST['fooditem_qty'] ) ? $_POST['fooditem_qty'] : 1;

    if ( empty( $cart_key ) && empty( $fooditem_id ) ) {
      return;
    }

    $special_instruction = isset( $_POST['special_instruction'] ) ? sanitize_text_field( $_POST['special_instruction'] ) : '';
    $addon_items = isset( $_POST['post_data'] ) ?  $_POST['post_data'] : '';

    $options = array();
    $options['id'] = $fooditem_id;
    $options['quantity'] = $item_qty;
    $options['instruction'] = $special_instruction;

    $price_id = '';
    $items    = '';

    if( rpress_has_variable_prices( $fooditem_id ) ) {
      if ( isset( $addon_items[0]['name'] ) && $addon_items[0]['name'] == 'price_options' ) {
        $price_id = $addon_items[0]['value'];
      }
    }

    $options['price_id'] = $price_id;

    if ( is_array( $addon_items ) && !empty( $addon_items ) ) {

      foreach( $addon_items as $key => $get_items ) {

        $addon_data = explode( '|', $get_items[ 'value' ] );

        if ( is_array( $addon_data ) && !empty( $addon_data ) ) {

          $addon_item_like = isset( $addon_data[3] ) ? $addon_data[3] : 'checkbox';

          $addon_id = !empty( $addon_data[0] ) ? $addon_data[0] : '';
          $addon_qty = !empty( $addon_data[1] ) ? $addon_data[1] : '';
          $addon_price = !empty( $addon_data[2] ) ? $addon_data[2] : '';

          $addon_details = get_term_by( 'id', $addon_id, 'addon_category' );

          if (  $addon_details ) {

            $addon_item_name = $addon_details->name;

            $options['addon_items'][$key]['addon_item_name'] = $addon_item_name;
            $options['addon_items'][$key]['addon_id'] = $addon_id;
            $options['addon_items'][$key]['price'] = $addon_price;
            $options['addon_items'][$key]['quantity'] = $addon_qty;
          }
        }
      }
    }

    RPRESS()->cart->set_item_quantity( $fooditem_id, $item_qty, $options );

    $item = array(
      'id'      => $fooditem_id,
      'options' => $options
    );

    $item   = apply_filters( 'rpress_ajax_pre_cart_item_template', $item );
    $items = rpress_get_cart_item_template( $cart_key, $item, true, $data_key = '' );

    $return = array(
     'subtotal'      => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
     'total'         => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_total() ) ), ENT_COMPAT, 'UTF-8' ),
     'cart_item'     => $items,
     'cart_key'      => $cart_key,
     'cart_quantity' => html_entity_decode( rpress_get_cart_quantity() )
    );

    if ( rpress_use_taxes() ) {
      $cart_tax = (float) rpress_get_cart_tax();
      $return['tax'] = html_entity_decode( rpress_currency_filter( rpress_format_amount( $cart_tax ) ), ENT_COMPAT, 'UTF-8' );
    }

    $return = apply_filters( 'rpress_cart_data', $return );
    echo json_encode( $return );
    rpress_die();
  }

  /**
   * Remove an item from Cart
   */
  public static function remove_from_cart() {

    if ( isset( $_POST['cart_item'] ) ) {

      rpress_remove_from_cart( $_POST['cart_item'] );

      $return = array(
        'removed'       => 1,
        'subtotal'      => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
        'total'         => html_entity_decode( rpress_currency_filter( rpress_format_amount( rpress_get_cart_total() ) ), ENT_COMPAT, 'UTF-8' ),
        'cart_quantity' => html_entity_decode( rpress_get_cart_quantity() ),
      );

      if ( rpress_use_taxes() ) {
        $cart_tax = (float) rpress_get_cart_tax();
        $return['tax'] = html_entity_decode( rpress_currency_filter( rpress_format_amount( $cart_tax ) ), ENT_COMPAT, 'UTF-8' );
      }
      $return = apply_filters( 'rpress_cart_data', $return );
      wp_send_json( $return );

    }
    rpress_die();
  }

  /**
  * Clear cart
  */
  public static function clear_cart() {

    //check_ajax_referer( 'clear-cart', 'security' );

    rpress_empty_cart();

    // Removing Service Time Cookie
    if ( isset( $_COOKIE['service_time'] ) ) {
      unset( $_COOKIE['service_time'] );
      setcookie( "service_time", "", time() - 300,"/" );
    }

    // Removing Service Type Cookie
    if ( isset( $_COOKIE['service_type'] ) ) {
      unset( $_COOKIE['service_type'] );
      setcookie( "service_type", "", time() - 300,"/" );
    }

    // Removing Delivery Date Cookie
    if ( isset( $_COOKIE['delivery_date'] ) ) :
      unset( $_COOKIE['delivery_date'] );
      setcookie( "delivery_date", "", time() - 300,"/" );
    endif;

    $return['status']   = 'success';
    $return['response'] = '<li class="cart_item empty"><span class="rpress_empty_cart">'.apply_filters( 'rpress_empty_cart_message', '<span class="rpress_empty_cart">' . __( 'CHOOSE AN ITEM FROM THE MENU TO GET STARTED.', 'restropress' ) . '</span>' ).'</span></li>';
    echo json_encode( $return );

    rpress_die();
  }

  /**
  * Proceed Checkout
  */
  public static function proceed_checkout() {
    //check_ajax_referer( 'proceed-checkout', 'security' );
    $response = rpress_pre_validate_order();
    $response = apply_filters( 'rpress_proceed_checkout', $response );
    wp_send_json( $response );
    rpress_die();
  }

  /**
   * Get Order Details
   */
  public static function get_order_details() {

    check_admin_referer( 'rpress-preview-order', 'security' );

    $order = rpress_get_payment( absint( $_GET['order_id'] ) );

    if ( $order ) {
      include_once 'admin/payments/class-payments-table.php';

      wp_send_json_success( RPRESS_Payment_History_Table::order_preview_get_order_details( $order ) );
    }
    rpress_die();
  }

  /**
  * Get Fooditem Variations
  */
  public static function check_for_fooditem_price_variations() {

    echo '2';

    if ( ! current_user_can( 'edit_products' ) ) {
      die( '-1' );
    }

    $fooditem_id = isset( $_POST['fooditem_id'] ) ? $_POST['fooditem_id'] : '';

    //Check fooditem has any variable pricing
    if ( empty( $fooditem_id ) )
      return;

    ob_start();

    if ( rpress_has_variable_prices( $fooditem_id ) ) :
      $get_lowest_price_id = rpress_get_lowest_price_id( $fooditem_id );
      $get_lowest_price = rpress_get_lowest_price_option( $fooditem_id );
      ?>
      <div class="rpress-get-variable-prices">
        <input type="hidden" class="rpress_selected_price" name="rpress_selected_price" value="<?php echo $get_lowest_price; ?>">
      <?php
      foreach ( rpress_get_variable_prices( $fooditem_id ) as $key => $options ) :
        $option_price = $options['amount'];
        $price = rpress_currency_filter( rpress_format_amount( $option_price ) );
        $option_name = $options['name'];
        $option_name_slug = sanitize_title( $option_name );
      ?>
        <label for="<?php echo $option_name_slug; ?>">
          <input id="<?php echo $option_name_slug; ?>" <?php checked( $get_lowest_price_id, $key, true ); ?> type="radio" name="rpres_price_name" value="<?php echo $option_price; ?>">
          <?php echo $option_name; ?>
          <?php echo sprintf( __( '( %1$s )', 'restropress' ), $price );  ?>
        </label>
      <?php
      endforeach;
    ?>
    </div>
    <?php
    else :
      $normal_price = rpress_get_fooditem_price( $fooditem_id );
      $price = rpress_currency_filter( rpress_format_amount( $normal_price  ) );
      ?>
      <span class="rpress-price-name"><?php echo $price; ?></span>
      <input type="hidden" class="rpress_selected_price" name="rpress_selected_price" value="<?php echo $normal_price; ?>">
      <?php
      //$output .= '<span class="rpress-price">'.$price.'</span>';
    endif;
    $output = ob_get_contents();
    ob_end_clean();
    echo $output;
    rpress_die();
  }

  /**
  * Get addon items in the admin order screen
  */
  public static function admin_order_addon_items() {

    check_ajax_referer( 'load-admin-addon', 'security' );

    $fooditem_id = isset( $_POST['fooditem_id' ] ) ? $_POST['fooditem_id'] : '';
    $get_addon_items = '';

    ob_start();

    if( !empty( $fooditem_id ) ) {
      $get_addon_items = get_addon_items_by_fooditem( $fooditem_id );
    }

    $output = ob_get_contents();
    ob_end_clean();

    echo $output;
    rpress_die();
  }

  /**
   * Gets the cart's subtotal via AJAX.
   *
   * @since 1.0
   * @return void
   */
  public static function get_subtotal() {

    echo rpress_currency_filter( rpress_get_cart_subtotal() );
    rpress_die();
  }

  // Custom code
  public static function apply_fee() {

    if ( isset( $_POST['postcode'] ) ) {

      $postcode = sanitize_text_field( $_POST['postcode'] );

			$arrPostCodes = RPRESS()->fees->getArrPostCodes();

      $args = array(
        'label'       => 'Lieferkosten',
        'amount'      => $arrPostCodes[$postcode],
        'type'        => 'fee'
      );
      RPRESS()->fees->add_fee($args);

      $total = rpress_get_cart_total();

      $return = array(
        'msg'         => 'valid',
        'postcode' => $postcode,
        'total_plain' => $total,
        'total'     => html_entity_decode( rpress_currency_filter( rpress_format_amount( $total ) ), ENT_COMPAT, 'UTF-8' ),
        'html' => '<th colspan="3" class="rpress_cart_fee_label">Lieferkosten <span style="float:right">' . html_entity_decode( rpress_currency_filter( rpress_format_amount( $arrPostCodes[$postcode] ) ), ENT_COMPAT, 'UTF-8' ) . '</span></th>'
      );

      echo json_encode($return);
    }
    rpress_die();
  }

  /**
   * Validates the supplied discount sent via AJAX.
   *
   * @since 1.0
   * @return void
   */
  public static function apply_discount() {

    if ( isset( $_POST['code'] ) ) {

      $discount_code = sanitize_text_field( $_POST['code'] );

      $return = array(
        'msg'  => '',
        'code' => $discount_code
      );

      $user = '';

      if ( is_user_logged_in() ) {
        $user = get_current_user_id();
      } else {
        parse_str( $_POST['form'], $form );
        if ( ! empty( $form['rpress_email'] ) ) {
          $user = urldecode( $form['rpress_email'] );
        }
      }

      if ( rpress_is_discount_valid( $discount_code, $user ) ) {

        $discount  = rpress_get_discount_by_code( $discount_code );
        $amount    = rpress_format_discount_rate( rpress_get_discount_type( $discount->ID ), rpress_get_discount_amount( $discount->ID ) );
        $discounts = rpress_set_cart_discount( $discount_code );
        $total     = rpress_get_cart_total( $discounts );
        $discount_value = rpress_get_discount_value( $discount_code );

        $return = array(
          'msg'         => 'valid',
          'discount_value' => $discount_value,
          'amount'      => $amount,
          'total_plain' => $total,
          'total'       => html_entity_decode( rpress_currency_filter( rpress_format_amount( $total ) ), ENT_COMPAT, 'UTF-8' ),
          'code'        => $discount_code,
          'html'        => rpress_get_cart_discounts_html( $discounts )
        );

      } else {

        $errors = rpress_get_errors();
        $return['msg']  = $errors['rpress-discount-error'];
        rpress_unset_error( 'rpress-discount-error' );
      }

      // Allow for custom discount code handling
      $return = apply_filters( 'rpress_ajax_discount_response', $return );

      echo json_encode($return);
    }
    rpress_die();
  }

  /**
   * Removes a discount code from the cart via ajax
   *
   * @since  1.0.0
   * @return void
   */
  public static function remove_discount() {

    if ( isset( $_POST['code'] ) ) {

      rpress_unset_cart_discount( urldecode( $_POST['code'] ) );

      $total = rpress_get_cart_total();

      $return = array(
        'total'     => html_entity_decode( rpress_currency_filter( rpress_format_amount( $total ) ), ENT_COMPAT, 'UTF-8' ),
        'code'      => $_POST['code'],
        'discounts' => rpress_get_cart_discounts(),
        'html'      => rpress_get_cart_discounts_html()
      );

      echo json_encode( $return );
    }
    rpress_die();
  }

  /**
   * Loads Checkout Login Fields the via AJAX
   *
   * @since 1.0
   * @return void
   */
  public static function checkout_login() {

    do_action( 'rpress_purchase_form_login_fields' );
    rpress_die();
  }
  /**
   * Load Checkout Register Fields via AJAX
   *
   * @since 1.0
   * @return void
  */
  public static function checkout_register() {

    do_action( 'rpress_purchase_form_register_fields' );
    rpress_die();
  }

  /**
   * Recalculate cart taxes
   *
   * @since  1.0.0
   * @return void
   */
  public static function recalculate_taxes() {

    if ( ! rpress_get_cart_contents() ) {
      return false;
    }

    if ( empty( $_POST['billing_country'] ) ) {
      $_POST['billing_country'] = rpress_get_shop_country();
    }

    ob_start();
    rpress_checkout_cart();
    $cart     = ob_get_clean();
    $response = array(
      'html'         => $cart,
      'tax_raw'      => rpress_get_cart_tax(),
      'tax'          => html_entity_decode( rpress_cart_tax( false ), ENT_COMPAT, 'UTF-8' ),
      'tax_rate_raw' => rpress_get_tax_rate(),
      'tax_rate'     => html_entity_decode( rpress_get_formatted_tax_rate(), ENT_COMPAT, 'UTF-8' ),
      'total'        => html_entity_decode( rpress_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
      'total_raw'    => rpress_get_cart_total(),
    );

    echo json_encode( $response );

    rpress_die();
  }

  /**
   * Retrieve a states drop down
   *
   * @since  1.0.0
   * @return void
   */
  public static function get_states() {

    if( empty( $_POST['country'] ) ) {
      $_POST['country'] = rpress_get_shop_country();
    }

    $states = rpress_get_states( $_POST['country'] );

    if( ! empty( $states ) ) {

      $args = array(
        'name'    => $_POST['field_name'],
        'id'      => $_POST['field_name'],
        'class'   => $_POST['field_name'] . '  rpress-select',
        'options' => $states,
        'show_option_all'  => false,
        'show_option_none' => false
      );

      $response = RPRESS()->html->select( $args );

    } else {

      $response = 'nostates';
    }

    echo $response;

    rpress_die();
  }

  /**
   * Search food items
   *
   * @since  1.0.0
   * @return void
   */
  public static function fooditem_search() {

    global $wpdb;

    $search   = esc_sql( sanitize_text_field( $_GET['s'] ) );
    $excludes = ( isset( $_GET['current_id'] ) ? (array) $_GET['current_id'] : array() );

    $no_bundles = isset( $_GET['no_bundles'] ) ? filter_var( $_GET['no_bundles'], FILTER_VALIDATE_BOOLEAN ) : false;
    if( true === $no_bundles ) {
      $bundles  = $wpdb->get_results( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_rpress_product_type' AND meta_value = 'bundle';", ARRAY_A );
      $bundles  = wp_list_pluck( $bundles, 'post_id' );
      $excludes = array_merge( $excludes, $bundles );
    }

    $variations = isset( $_GET['variations'] ) ? filter_var( $_GET['variations'], FILTER_VALIDATE_BOOLEAN ) : false;

    $excludes = array_unique( array_map( 'absint', $excludes ) );
    $exclude  = implode( ",", $excludes );

    $results = array();

    // Setup the SELECT statement
    $select = "SELECT ID,post_title FROM $wpdb->posts ";

    // Setup the WHERE clause
    $where = "WHERE `post_type` = 'fooditem' and `post_title` LIKE '%s' ";

    // If we have items to exclude, exclude them
    if( ! empty( $exclude ) ) {
      $where .= "AND `ID` NOT IN (" . $exclude . ") ";
    }

    if ( ! current_user_can( 'edit_products' ) ) {
      $status = apply_filters( 'rpress_product_dropdown_status_nopriv', array( 'publish' ) );
    } else {
      $status = apply_filters( 'rpress_product_dropdown_status', array( 'publish', 'draft', 'private', 'future' ) );
    }

    if ( is_array( $status ) && ! empty( $status ) ) {

      $status     = array_map( 'sanitize_text_field', $status );
      $status_in  = "'" . join( "', '", $status ) . "'";
      $where     .= "AND `post_status` IN ({$status_in}) ";

    } else {

      $where .= "AND `post_status` = `publish` ";

    }

    // Limit the result sets
    $limit = "LIMIT 50";

    $sql = $select . $where . $limit;

    $prepared_statement = $wpdb->prepare( $sql, '%' . $search . '%' );

    $items = $wpdb->get_results( $prepared_statement );

    if( $items ) {

      foreach( $items as $item ) {

        $results[] = array(
          'id'   => $item->ID,
          'name' => $item->post_title
        );

        if ( $variations && rpress_has_variable_prices( $item->ID ) ) {
          $prices = rpress_get_variable_prices( $item->ID );

          foreach ( $prices as $key => $value ) {
            $name   = ! empty( $value['name'] )   ? $value['name']   : '';
            $amount = ! empty( $value['amount'] ) ? $value['amount'] : '';
            $index  = ! empty( $value['index'] )  ? $value['index']  : $key;

            if ( $name && $index ) {
              $results[] = array(
                'id'   => $item->ID . '_' . $key,
                'name' => esc_html( $item->post_title . ': ' . $name ),
              );
            }
          }
        }
      }

    } else {

      $results[] = array(
        'id'   => 0,
        'name' => __( 'No results found', 'restropress' )
      );

    }

    echo json_encode( $results );

    rpress_die();
  }

  /**
   * Processes the updated quantity value on
   * Checkout page
   *
   * @since 1.0.0
   */
  public function update_quantity() {

    if ( empty( $_POST['quantity'] ) || empty( $_POST['fooditem_id'] ) )
      return;

    $fooditem_id = absint( $_POST['fooditem_id'] );
    $quantity    = absint( $_POST['quantity'] );
    $options     = json_decode( stripslashes( $_POST['options'] ), true );

    RPRESS()->cart->set_item_quantity( $fooditem_id, $quantity, $options );

    $return = array(
      'fooditem_id' => $fooditem_id,
      'quantity'    => RPRESS()->cart->get_item_quantity( $fooditem_id, $options, $quantity ),
      'subtotal'    => html_entity_decode( rpress_currency_filter( rpress_format_amount( RPRESS()->cart->get_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
      'taxes'       => html_entity_decode( rpress_currency_filter( rpress_format_amount( RPRESS()->cart->get_tax() ) ), ENT_COMPAT, 'UTF-8' ),
      'total'       => html_entity_decode( rpress_currency_filter( rpress_format_amount( RPRESS()->cart->get_total() ) ), ENT_COMPAT, 'UTF-8' )
    );

    // Allow for custom cart item quantity handling
    $return = apply_filters( 'rpress_ajax_cart_item_quantity_response', $return );
    echo json_encode($return);
    rpress_die();
  }

  /**
   * Search the customers database via AJAX
   *
   * @since  1.0.0
   * @return void
   */
  public static function customer_search() {

    global $wpdb;

    $search  = esc_sql( sanitize_text_field( $_GET['s'] ) );
    $results = array();
    $customer_view_role = apply_filters( 'rpress_view_customers_role', 'view_shop_reports' );
    if ( ! current_user_can( $customer_view_role ) ) {
      $customers = array();
    } else {
      $select = "SELECT id, name, email FROM {$wpdb->prefix}rpress_customers ";
      if ( is_numeric( $search ) ) {
        $where = "WHERE `id` LIKE '%$search%' OR `user_id` LIKE '%$search%' ";
      } else {
        $where = "WHERE `name` LIKE '%$search%' OR `email` LIKE '%$search%' ";
      }
      $limit = "LIMIT 50";

      $customers = $wpdb->get_results( $select . $where . $limit );
    }

    if( $customers ) {

      foreach( $customers as $customer ) {

        $results[] = array(
          'id'   => $customer->id,
          'name' => $customer->name . '(' .  $customer->email . ')'
        );
      }

    } else {

      $customers[] = array(
        'id'   => 0,
        'name' => __( 'No results found', 'restropress' )
      );

    }

    echo json_encode( $results );

    rpress_die();
  }

  /**
   * Search the users database via AJAX
   *
   * @since 1.0.0
   * @return void
   */
  public static function user_search() {

    global $wpdb;

    $search         = esc_sql( sanitize_text_field( $_GET['s'] ) );
    $results        = array();
    $user_view_role = apply_filters( 'rpress_view_users_role', 'view_shop_reports' );

    if ( ! current_user_can( $user_view_role ) ) {
      $results = array();
    } else {
      $user_args = array(
        'search' => '*' . esc_attr( $search ) . '*',
        'number' => 50,
      );

      $users = get_users( $user_args );
    }

    if ( $users ) {

      foreach( $users as $user ) {

        $results[] = array(
          'id'   => $user->ID,
          'name' => $user->display_name,
        );
      }

    } else {

      $results[] = array(
        'id'   => 0,
        'name' => __( 'No users found', 'restropress' )
      );

    }

    echo json_encode( $results );

    rpress_die();
  }

  /**
   * Searches for users via ajax and returns a list of results
   *
   * @since  1.0.0
   * @return void
   */
  public static function search_users() {

    if( current_user_can( 'manage_shop_settings' ) ) {

      $search_query = trim( $_POST['user_name'] );
      $exclude      = trim( $_POST['exclude'] );

      $get_users_args = array(
        'number' => 9999,
        'search' => $search_query . '*'
      );

      if ( ! empty( $exclude ) ) {
        $exclude_array = explode( ',', $exclude );
        $get_users_args['exclude'] = $exclude_array;
      }

      $get_users_args = apply_filters( 'rpress_search_users_args', $get_users_args );

      $found_users = apply_filters( 'rpress_ajax_found_users', get_users( $get_users_args ), $search_query );

      $user_list = '<ul>';
      if( $found_users ) {
        foreach( $found_users as $user ) {
          $user_list .= '<li><a href="#" data-userid="' . esc_attr( $user->ID ) . '" data-login="' . esc_attr( $user->user_login ) . '">' . esc_html( $user->user_login ) . '</a></li>';
        }
      } else {
        $user_list .= '<li>' . __( 'No users found', 'restropress' ) . '</li>';
      }
      $user_list .= '</ul>';

      echo json_encode( array( 'results' => $user_list ) );

    }
    die();
  }

  /**
   * Check for new orders and send notification
   *
   * @since       2.0.1
   * @param       void
   * @return      json | user notification json object
   */
  public static function check_new_orders() {
    $last_order = get_option( 'rp_last_order_id' );
    $order      = rpress_get_payments( array( 'number' => 1 ) );

    if( is_array( $order ) && $order[0]->ID != $last_order ) {

      $payment_id = $order[0]->ID;

      $payment  = new RPRESS_Payment( $payment_id );

      $placeholder = array( '{order_id}' => $payment_id );

      $service_type = get_post_meta( $payment_id, '_rpress_delivery_type', true );

      if ( !empty( $service_type ) ) {
        $service_type = ucfirst( $service_type );
      }

      $service_date = get_post_meta( $payment_id, '_rpress_delivery_date', true );

      if ( !empty( $service_date ) ) {
        $service_date = rpress_local_date( $service_date );
      }

      $payment_status = $payment->status;

      if ( $payment_status == 'publish' ) {
        $payment_status = 'Paid';
      }

      $payment_status = ucfirst( $payment_status );

      $search = array( '{order_id}', '{service_type}', '{payment_status}', '{service_date}' );

      $replace = array( $payment_id, $service_type, $payment_status, $service_date );

      $body = rpress_get_option( 'notification_body' );
      $body = str_replace( $search, $replace, $body );

      $notification = array(
        'title' => rpress_get_option( 'notification_title' ),
        'body'  => $body,
        'icon'  => rpress_get_option( 'notification_icon' ),
        'sound' => rpress_get_option( 'notification_sound' ),
        'url'   => admin_url( 'admin.php?page=rpress-payment-history&view=view-order-details&id=' . $payment_id )
      );
      update_option( 'rp_last_order_id', $payment_id  );
      wp_send_json( $notification );
    }
    wp_die();
  }

  /**
   * Activate addon license with ajax call
   *
   * @since 2.5
   * @author RestrPress
   */
  public function activate_addon_license() {

    // listen for our activate button to be clicked
    if( isset($_POST['license_key']) ) {

      // Get the license from the user
      // Item ID (Normally a 2 or 3 digit code)
      $item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : '';

      // The actual license code
      $license = isset( $_POST['license'] ) ? trim( $_POST['license'] ) : '';

      // Name of the addon (Print Receipts)
      $name = isset( $_POST['product_name'] ) ? $_POST['product_name'] : '';

      // Key to be saved in to DB
      $license_key = isset( $_POST['license_key'] ) ? $_POST['license_key'] : '';

      // data to send in our API request
      $api_params = array(
        'edd_action' => 'activate_license',
        'item_id'    => $item_id,
        'item_name'  => urlencode( $name ),
        'license'    => $license,
        'url'        => home_url()
      );

      // Call the custom API.
      $response = wp_remote_post( 'https://www.restropress.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      // make sure the response came back okay
      if ( is_wp_error( $response )
        || 200 !== wp_remote_retrieve_response_code( $response ) ) {

        if ( is_wp_error( $response ) ) {
          $message = $response->get_error_message();
        }
        else {
          $message = __( 'An error occurred, please try again.' );
        }

      } else {

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( false === $license_data->success ) {

          switch( $license_data->error ) {

              case 'expired' :

                $message = sprintf(
                  __( 'Your license key expired on %s.' ),
                  date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                );
                break;

              case 'revoked' :

                $message = __( 'Your license key has been disabled.' );
                break;

              case 'missing' :

                $message = __( 'Invalid license.' );
                break;

              case 'invalid' :
              case 'site_inactive' :

                $message = __( 'Your license is not active for this URL.' );
                break;

              case 'item_name_mismatch' :

                $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), $name );
                break;

              case 'no_activations_left':

                $message = __( 'Your license key has reached its activation limit.' );
                break;

              default :

                $message = __( 'An error occurred, please try again.' );
                break;
          }
        }
      }

      // Check if anything passed on a message constituting a failure
      if ( ! empty( $message ) )

        $return = array( 'status' => 'error', 'message' => $message );

      else {

        //Save the license key in database
        update_option( $license_key, $license );

        // $license_data->license will be either "valid" or "invalid"
        update_option( $license_key . '_status', $license_data->license );
        $return = array( 'status' => 'updated', 'message' => 'Your license is successfully activated.' );
      }

      echo json_encode( $return );
      wp_die();
    }
  }

  /**
   * Deactivate the license of plugin with AJAX call
   *
   * @since 2.5
   * @author RestroPress
   * @return void
   */
  public function deactivate_addon_license() {

    if( isset($_POST['license_key']) ) {

      $license_key = isset( $_POST['license_key'] ) ? $_POST['license_key'] : '';

      // retrieve the license from the database
      $license = trim( get_option( $license_key ) );

      $item_name = isset( $_POST['product_name'] ) ? $_POST['product_name'] : '';

      // data to send in our API request
      $api_params = array(
        'edd_action' => 'deactivate_license',
        'license'    => $license,
        'item_name'  => urlencode( $item_name ), // the name of our product in EDD
        'url'        => home_url()
      );

      // Call the custom API.
      $response = wp_remote_post( 'https://www.restropress.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      // make sure the response came back okay
      if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

        if ( is_wp_error( $response ) ) {
          $message = $response->get_error_message();
        } else {
          $message = __( 'An error occurred, please try again.' );
        }
        $return = array( 'status' => 'error', 'message' => $message );

      } else{

        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        // $license_data->license will be either "deactivated" or "failed"
        if( $license_data->license == 'deactivated' ) {
          delete_option( $license_key . '_status' );
          delete_option( $license_key );
        }
        $return = array( 'status' => 'updated', 'message' => 'License successfully deactivated.' );
      }
      echo json_encode( $return );
      wp_die();
    }
  }
}

RP_AJAX::init();