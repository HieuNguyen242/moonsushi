<?php
/**
 * Misc Functions
 *
 * @package     RPRESS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2018, Magnigenie
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



// API

function limited_use_callback( $response, $handler, WP_REST_Request $request ) {
    if ( ! is_wp_error( $response ) && ! empty( $handler['limited_use_callback'] ) ) {
		$limited_use_enabled = call_user_func( $handler['limited_use_callback'], $request );

		if ( is_wp_error( $limited_use_enabled ) ) {
			$response = $limited_use_enabled;
		} elseif ( true === $limited_use_enabled ) {
			$response = new WP_Error(
				'rest_forbidden',
				__( 'Ihre Lizenz ist abgelaufen. Bitte wenden Sie sich an Ihren Dienstanbieter, um eine neue Lizenz zu erhalten.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
	}
    return $response; 
}
add_filter( 'rest_request_before_callbacks', 'limited_use_callback', 10, 3 );


add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/orders', array(
	  'methods' => 'POST',
	  'callback' => 'get_orders',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/order-detail/(?P<id>\d+)', array(
	  'methods' => 'GET',
	  'callback' => 'get_order_detail',
	  'permission_callback' => 'api_permission',
	  'limited_use_callback' => 'api_limited_use'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/update-order-status', array(
	  'methods' => 'POST',
	  'callback' => 'update_order_status',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/settings', array(
	  'methods' => 'GET',
	  'callback' => 'get_server_settings',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/update-setting', array(
	  'methods' => 'POST',
	  'callback' => 'update_server_setting',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/kitchen-names', array(
	  'methods' => 'GET',
	  'callback' => 'get_kitchen_names',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/discounts', array(
	  'methods' => 'POST',
	  'callback' => 'get_discounts',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/discount/(?P<id>\d+)', array(
	  'methods' => 'GET',
	  'callback' => 'get_discount',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/save-discount', array(
	  'methods' => 'POST',
	  'callback' => 'save_discount',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/delete-discount', array(
	  'methods' => 'POST',
	  'callback' => 'delete_discount',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/customers', array(
	  'methods' => 'POST',
	  'callback' => 'get_customers',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/customer/(?P<id>\d+)', array(
	  'methods' => 'GET',
	  'callback' => 'get_customer',
	  'permission_callback' => 'api_permission'
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'fom-api', '/save-customer', array(
	  'methods' => 'POST',
	  'callback' => 'save_customer',
	  'permission_callback' => 'api_permission'
	) );
} );



function api_permission($request) {
	$srvAPIKey = defined( 'API_Key' ) ? API_Key : '';
	$clientAPIKey = $request->get_header('Authorization');
	if($clientAPIKey == $srvAPIKey){
		return true;
	}
	return false;
}

function api_limited_use($request) {
	$limitedUseEnabled = get_option('limited_use_enabled') == 1 ? 1 : 0;
	if ($limitedUseEnabled) {
		return true;
	}
	else return false;
}

function getDisplayString($key) {
	$APIStrings = defined( 'APIStrings' ) ? APIStrings : array();
	if (array_key_exists($key, $APIStrings)) {
		return $APIStrings[$key];
	}
	else {
		return $key;
	}
}

function get_orders($request) {
	$filterEnum = array(
		'DATE_FILTER_NEW' => 1,
		'DATE_FILTER_ALL_TODAY' => 2,
		'DATE_FILTER_ALL_YESTERDAY' => 3,
		'DATE_FILTER_ALL_THIS_WEEK' => 4,
		'DATE_FILTER_ALL_LAST_WEEK' => 5,
		'DATE_FILTER_ALL_THIS_MONTH' => 6,
		'DATE_FILTER_ALL_LAST_MONTH' => 7,
		'DATE_FILTER_ALL' => 8,
		'SERVICE_FILTER_ALL' => 9,
		'SERVICE_FILTER_PICKUP' => 10,
		'SERVICE_FILTER_DELIVERY' => 11
	);

	$params = $request->get_json_params();
	$dateFilter = $params['dateFilter'];
	$serviceTypeFilter = $params['serviceTypeFilter'];

	$args = array(
		'output'     => 'payments',
		'number'     => 9999999999,
		'page'       => 1,
		'orderby'    => 'ID',
		'order'      => 'DESC'
	);

	if ($serviceTypeFilter == $filterEnum['SERVICE_FILTER_PICKUP']) {
		$args['service_type'] = 'pickup';
	}
	else if ($serviceTypeFilter == $filterEnum['SERVICE_FILTER_DELIVERY']) {
		$args['service_type'] = 'delivery';
	}

	if ($dateFilter == $filterEnum['DATE_FILTER_NEW']) {
		$today = current_time( 'Y-m-d' );
		$args['start_date'] = $today;
		$args['end_date'] = $today;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_TODAY']) {
		$today = current_time( 'Y-m-d' );
		$args['start_date'] = $today;
		$args['end_date'] = $today;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_YESTERDAY']) {
		$yesterday = current_datetime()->modify( '-1 day' )->format( 'Y-m-d' );
		$args['start_date'] = $yesterday;
		$args['end_date'] = $yesterday;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_THIS_WEEK']) {
		$today = current_time( 'Y-m-d' );
		$firstDateOfWeek = date('Y-m-d', get_weekstartend($today)['start']);
		$args['start_date'] = $firstDateOfWeek;
		$args['end_date'] = $today;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_LAST_WEEK']) {
		$lastWeek = current_datetime()->modify( '-7 days' )->format( 'Y-m-d' );
		$start = date('Y-m-d', get_weekstartend($lastWeek)['start']);
		$end = date('Y-m-d', get_weekstartend($lastWeek)['end']);
		$args['start_date'] = $start;
		$args['end_date'] = $end;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_THIS_MONTH']) {
		$today = current_time( 'Y-m-d' );
		$firstDateOfMonth = current_datetime()->format( 'Y-m-01' );
		$args['start_date'] = $firstDateOfMonth;
		$args['end_date'] = $today;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL_LAST_MONTH']) {
		$start = current_datetime()->modify( '-1 month' )->format( 'Y-m-01' );
		$end = date('Y-m-d', strtotime(current_datetime()->format( 'Y-m-01') . ' -1 day'));
		$args['start_date'] = $start;
		$args['end_date'] = $end;
	}
	else if ($dateFilter == $filterEnum['DATE_FILTER_ALL']) {
		$args['number'] = 500;
	}

	$p_query  = new RPRESS_Payments_Query( $args );
	$payments = $p_query->get_payments();

	$orderCount = 0;
	$amountCount = 0;
	$items = array();
	if ( $payments ) {
		$i = 0;
		foreach ( $payments as $payment ) {
			$statusId = rpress_get_order_status( $payment->ID );
			if ($dateFilter == $filterEnum['DATE_FILTER_NEW'] && ($statusId == 'completed' || $statusId == 'cancelled')){
				continue;
			}

			$item = array();

			$total = rpress_get_payment_amount( $payment->ID );
			$total  = ! empty( $total ) ? $total : 0;

			$customer_id = rpress_get_payment_customer_id( $payment->ID );
			$customer_name = '';
			if( ! empty( $customer_id ) ) {
				$customer    = new RPRESS_Customer( $customer_id );
				$customer_name = $customer->name;
			}
			else {
				$user_info = rpress_get_payment_meta_user_info( $payment->ID );
				$customer_name = $user_info['first_name'] . ' '. $user_info['last_name'];
			}
			$customer_name = isset($customer_name) ? trim($customer_name) : '';

			$item['id']             = $payment->ID;
			$item['orderNumber']    = '#' . rpress_get_payment_number( $payment->ID );		
			$item['total']          = rpress_format_amount( $total );
			$item['customerName']         = $customer_name;
			$item['serviceType']          = rpress_get_service_type( $payment->ID );
			$item['serviceDate']          = get_post_meta( $payment->ID, '_rpress_delivery_date', true );
			$item['serviceTime']          = get_post_meta( $payment->ID, '_rpress_delivery_time', true );
			$item['statusId']       	  = $statusId;

			$items[ $i ] = $item;

			if ($statusId == 'completed') {
				$orderCount++;
				$amountCount += $total;
			}

			$i++;
		}
	}

	if ($dateFilter == $filterEnum['DATE_FILTER_NEW'] || $dateFilter == $filterEnum['DATE_FILTER_ALL']) {
		$orderCount = '';
		$amountCount = '';
	}
	else {
		$amountCount = rpress_format_amount ( $amountCount );
	}

	$foodOrderClosed = get_option('food_order_closed') == 1 ? 1 : 0;

	return array(
		'dateBetween' => $args['start_date'] . ' -> ' . $args['end_date'],
		'dateFilter' => $dateFilter,
		'serviceTypeFilter' => $serviceTypeFilter,
		'orderCount' => $orderCount,
		'amountCount' => $amountCount,		
		'orderList' => $items,
		'foodOrderClosed' => $foodOrderClosed
	);
}

function get_order_detail($params) {
	$id = $params['id'];
	$payment      = new RPRESS_Payment( $id );
	$customer_name = '';
	if( ! empty( $payment->customer_id ) ) {
		$customer    = new RPRESS_Customer( $payment->customer_id );
		$customer_name = $customer->name;
	}
	else {
		$user_info = $payment->user_info;
		$customer_name = $user_info['first_name'] . ' '. $user_info['last_name'];
	}
	$customer_name = isset($customer_name) ? trim($customer_name) : '';
	$payment_meta   = $payment->get_meta();
	$address_info		= get_post_meta( $id, '_rpress_delivery_address', true );
	$phone					= !empty( $payment_meta['phone'] ) ? $payment_meta['phone'] : ( !empty( $address_info['phone'] ) ? $address_info['phone'] : '' );
	$flat						= !empty( $address_info['flat'] ) ? $address_info['flat'] : '';
	$postcode				= !empty( $address_info['postcode'] ) ? $address_info['postcode'] : '';
	$street					= !empty( $address_info['address'] ) ? $address_info['address'] : '';
	$total = rpress_get_payment_amount( $payment->ID );
	$total  = !empty( $total ) ? $total : 0;

	$deliveryFee = 0;
	$fees = rpress_get_payment_fees( $payment->ID );
	foreach( $fees as $fee ) {			
		$deliveryFee += isset( $fee['amount'] ) ? floatval($fee['amount']) : 0;
	}

	$subtotal = 0;
	$discount = 0;

	$kitchenNoneText = getDisplayString('food_item_group_unknown');
	$kitchens = array();
	$products = array();
	$cart_details = $payment->cart_details;
    if ( is_array( $cart_details ) && !empty( $cart_details ) ) {
        for( $i = 0, $size = count($cart_details); $i < $size; $i++ ) {		
			$cart_content = $cart_details[$i];
			$itemSubTotal = isset( $cart_content['subtotal'] ) ? floatval($cart_content['subtotal']) : 0;
			$subtotal += $itemSubTotal;
			$discount += isset( $cart_content['discount'] ) ? floatval($cart_content['discount']) : 0;			
			$cart_content['subtotal'] = rpress_format_amount($itemSubTotal);
			
			$item_title = $cart_content['name'];
			if ( empty( $item_title ) ) {
				$item_title = $cart_content['id'];
			}
			if ( rpress_has_variable_prices( $cart_content['id'] ) && false !== rpress_get_cart_item_price_id( $cart_content ) ) {
				$item_title .= ' - ' . rpress_get_cart_item_price_name( $cart_content );
			}
			$cart_content['name'] = $item_title;
			
			$kitchen = get_the_terms( $cart_content['id'], 'fooditem_tag' );
			$cart_content['kitchen'] = array(
				'name' => $kitchen != false ? reset($kitchen)->name : $kitchenNoneText,
				'slug' => $kitchen != false ? reset($kitchen)->slug : ''
			);
			if (!kitchenExistsInArray($cart_content['kitchen'], $kitchens)) {
				$kitchens[$i] = $cart_content['kitchen'];
			}

			$addons = array();
			foreach( $cart_content['item_number']['options'] as $k => $v ) {
				if( !empty($v['addon_item_name']) ) {
					$addon = array(
						'name' => ('+ ' . $v['addon_item_name']),
						'longName' => ('+ ' . $v['addon_item_name'] . ($v['price'] == '' ? '' : (' (' . rpress_format_amount($v['price']) . ')')))
					);
					array_push($addons, $addon);
				}
			}
			$cart_content['addons'] = $addons;

			$products[$i] = $cart_content;
        }
    }
	$bySort = array_column($products, 'name');
	array_multisort($bySort, SORT_ASC, $products);

	$bySort = array_column($kitchens, 'slug');
	array_multisort($bySort, SORT_ASC, $kitchens);
	$return_products = array();
	$i = 0;
	foreach ($kitchens as $key => $kitchen) {
		if (!(count($kitchens) == 1 && $kitchen['name'] == $kitchenNoneText)) {
			$return_products[$i] = array(
				'name' => $kitchen['name'],
				'isKitchenItem' => true
			);
			$i++;
		}

		foreach ($products as $key => $product) {
			if ($product['kitchen']['slug'] == $kitchen['slug']) {
				$product['isKitchenItem'] = false;
				$return_products[$i] = $product;
				$i++;
			}
		}
	}

	$item = array();
	$item['id']             = $id;
	$item['orderNumber']    = '#' . $payment->number;
	$item['statusId']       = rpress_get_order_status( $payment->ID );
	$item['subtotal']       = rpress_format_amount( $subtotal );
	$item['discount']		= rpress_format_amount ( $discount );
	$item['deliveryFee']	= rpress_format_amount ( $deliveryFee );
	$item['total']          = rpress_format_amount( $total );
	$item['serviceType'] 	= $payment->get_meta( '_rpress_delivery_type' );
	$item['serviceDate'] 	= $payment->get_meta( '_rpress_delivery_date' );
	$item['serviceTime'] 	= $payment->get_meta( '_rpress_delivery_time' );
	$item['products']       = $return_products;
	$item['note']			= $payment->get_meta( '_rpress_order_note' );
	$item['customerName']   = $customer_name;
	$item['customerId'] 	= !empty($payment->customer_id) ? $payment->customer_id : 0;
	$item['emailAddress']	= isset($payment->email) ? $payment->email : '';
	$item['phoneNumber']	= $phone;
	if ($item['serviceType'] == 'delivery') {
		$item['deliveryAddress']  =  $street . ($flat != '' ? ', ' . $flat : '' ) . ', ' . $postcode;
	}
	else {
		$item['deliveryAddress'] = '';
	}
	
	return $item;
}

function kitchenExistsInArray($entry, $array) {
    foreach ($array as $compare) {
        if ($compare['slug'] == $entry['slug']) {
            return true;
        }
	}
    return false;
}

function update_order_status ($request) {
	$params = $request->get_json_params();
	$id = $params['id'];
	$statusId = $params['statusId'];
	$newServiceTimeParam = $params['newServiceTime'];

	if ($newServiceTimeParam != null && $newServiceTimeParam != '') {
		$arr = explode(";", $newServiceTimeParam);
		$time = strtotime($arr[0]);
		$newServiceTime = date("H:i", strtotime('+' . $arr[1] . ' minutes', $time));
		update_post_meta( $id , '_rpress_delivery_time', $newServiceTime );
	}

	rpress_update_order_status($id, $statusId);
	return true;
}

function get_server_settings() {
	$foodOrderClosed = get_option('food_order_closed') == 1 ? 1 : 0;

	return array(
		'foodOrderClosed' => $foodOrderClosed
	);
}

function update_server_setting ($request) {
	$params = $request->get_json_params();
	$settingName = $params['settingName'];
	$settingValue = $params['settingValue'];

	update_option($settingName, $settingValue, true);
	return true;
}

function get_kitchen_names() {
	$defaults = array( 'taxonomy' => 'fooditem_tag' );
    $args     = wp_parse_args( $args, $defaults );
    $tags = get_terms( $args );
	return implode(';', array_column($tags, 'name'));
}

function get_sortable_columns() {
	return array(
		'name'       => array( 'name', false ),
		'code'       => array( 'code', false ),
		'uses'       => array( 'uses', false ),
		'start' => array( 'start', false ),
		'expiration' => array( 'expiration', false ),
	);
}

function get_discounts( $request ) {
	$params = $request->get_json_params();

	$paged     = isset( $params['page'] ) ? $params['page'] : 1;
	$per_page  = $params['numItemsPerPage'];
	$discount_codes_data = array();

	$orderby  = isset( $params['orderBy'] )  ? $params['orderBy']   : 'ID';
	$order    = isset( $params['orderDirection'] )    ? $params['orderDirection']                    : 'DESC';
	$status   = isset( $params['status'] )   ? $params['status']     : array( 'active', 'inactive' );
	$meta_key = isset( $params['meta_key'] ) ? $params['meta_key']                 : null;
	$search   = isset( $params['filterStr'] )        ? sanitize_text_field( $params['filterStr'] ) : null;

	if ($orderby == 'none') {
		$orderby = 'ID';
		$order = 'DESC';
	}

	$args = array(
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => $orderby,
		'order'          => $order,
		'post_status'    => $status,
		'meta_key'       => $meta_key,
		's'              => $search
	);

	if( array_key_exists( $orderby, get_sortable_columns() ) && 'name' != $orderby ) {
		$args['orderby']  = 'meta_value';
		$args['meta_key'] = '_rpress_discount_' . $orderby;
	}

	$discounts = rpress_get_discounts( $args );

	if ( $discounts ) {
		foreach ( $discounts as $discount ) {
			if ( rpress_get_discount_max_uses( $discount->ID ) ) {
				$uses =  rpress_get_discount_uses( $discount->ID ) . '/' . rpress_get_discount_max_uses( $discount->ID );
			} else {
				$uses = rpress_get_discount_uses( $discount->ID );
			}

			$start_date = rpress_get_discount_start_date( $discount->ID );

			if ( ! empty( $start_date ) ) {
				$discount_start_date =  date_i18n( get_option( 'date_format' ), strtotime( $start_date ) );
			}

			if ( rpress_get_discount_expiration( $discount->ID ) ) {
				$expiration = date_i18n( get_option( 'date_format' ), strtotime( rpress_get_discount_expiration( $discount->ID ) ) );
			}

			$discount_codes_data[] = array(
				'discount-id'         => $discount->ID,
				'name'       => stripslashes( $discount->post_title ),
				'code'       => rpress_get_discount_code( $discount->ID ),
				'amount'     => html_entity_decode( rpress_format_discount_rate( rpress_get_discount_type( $discount->ID ), rpress_get_discount_amount( $discount->ID ) ), ENT_COMPAT, 'UTF-8' ),
				'uses'       => $uses,
				'start' => $discount_start_date,
				'expiration' => $expiration,
				'status'     => rpress_is_discount_expired( $discount->ID ) ? 'expired' : $discount->post_status,
			);
		}
	}

	$args['nopaging'] = true;
	$args['fields'] = 'ids';
	$discounts = rpress_get_discounts( $args );
	$totalCount    = count($discounts);

	return array (
		'page' => $paged,
		'numItemsPerPage' => $per_page,
		'totalCount' => $totalCount,
		'discounts' => $discount_codes_data
	);
}

function mapBooleanToNumber ($val) {
	if ($val == true) {
		return 1;
	}
	else if ($val == false) {
		return 0;
	}
	else {
		return $val;
	}
}

function get_discount( $params ) {
	$discountId = absint(isset($params['id']) && !empty($params['id']) ? $params['id'] : 0);

	$discount_data = array(
		'discount-id' => $discountId,
		'products' => [],
		'excluded-products' => []
	);

	if ($discountId > 0) {
		$discount          = rpress_get_discount( $discountId );
		$discount_data['name']                       = stripslashes( $discount->post_title );
		$discount_data['code']                       = rpress_get_discount_code( $discountId );
		$discount_data['amount']                     = rpress_get_discount_amount( $discountId );
		$discount_data['min_price']                  = rpress_get_discount_min_price( $discountId );
		$discount_data['type']                       = rpress_get_discount_type( $discountId );
		$discount_data['uses']                       = rpress_get_discount_uses( $discountId );
		$discount_data['max']                   = rpress_get_discount_max_uses( $discountId );
		$discount_data['start']                 = rpress_get_discount_start_date( $discountId );
		$discount_data['expiration']                   = rpress_get_discount_expiration( $discountId );
		$discount_data['status']                     = $discount->post_status;
		$discount_data['products']       = rpress_get_discount_product_reqs( $discountId );
		$discount_data['product_condition']      = rpress_get_discount_product_condition( $discountId );
		$discount_data['not_global']            = mapBooleanToNumber(rpress_is_discount_not_global( $discountId ));
		$discount_data['excluded-products']       = rpress_get_discount_excluded_products( $discountId );
		$discount_data['use_once']                 = mapBooleanToNumber(rpress_discount_is_single_use( $discountId ));
	}

	$products     = get_posts( array(
		'nopaging' => true,
		'post_type'      => 'fooditem',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_status'	 => 'publish',
		'fields'         => array('id', 'post_title'),
	) );
	$products_data = array();
	if ( $products ) {
		foreach ( $products as $product ) {
			$products_data[] = array(
				'id'         => $product->ID,
				'name'       => get_the_title( $product->ID )
			);
		}
	}
	$discount_data['lookupData'] = array(
		'products' => $products_data
	);

	return $discount_data;
}

function save_discount( $request ) {
	$data = $request->get_json_params();
	$discountId = $data['discount-id'];

	$result = array (
		'hasError' => false,
		'errorMessage' => ''
	);

	// Setup the discount code details
	$posted = array();

	if ( empty( $data['name'] ) || empty( $data['code'] ) || empty( $data['type'] ) || empty( $data['amount'] ) ) {
		$result['hasError'] = true;
		$result['errorMessage'] = getDisplayString('discount_validation_failed');
		return $result;
	}

	// Verify only accepted characters
	$sanitized = preg_replace('/[^a-zA-Z0-9-_]+/', '', $data['code'] );
	if ( strtoupper( $data['code'] ) !== strtoupper( $sanitized ) ) {
		$result['hasError'] = true;
		$result['errorMessage'] = getDisplayString('discount_invalid_code');
		return $result;
	}

	foreach ( $data as $key => $value ) {

		if ( $key === 'products' || $key === 'excluded-products' ) {

			foreach ( $value as $product_key => $product_value ) {
				$value[ $product_key ] = preg_replace("/[^0-9_]/", '', $product_value );
			}

			$posted[ $key ] = $value;

		} else if ( $key != 'rpress-discount-nonce' && $key != 'rpress-action' && $key != 'discount-id' && $key != 'rpress-redirect' ) {

			if ( is_string( $value ) || is_int( $value ) ) {

				$posted[ $key ] = strip_tags( addslashes( $value ) );

			} elseif ( is_array( $value ) ) {

				$posted[ $key ] = array_map( 'absint', $value );

			}
		}

	}

	if ($discountId == 0) {
		// Ensure this discount doesn't already exist
		if ( ! rpress_get_discount_by_code( $posted['code'] ) ) {

			if (!isset($posted['status']) || empty($posted['status'])) {
				$posted['status'] = 'active';
			}
			$posted['uses'] = 0;

			if ( !rpress_store_discount( $posted ) ) {
				$result['hasError'] = true;
				$result['errorMessage'] = getDisplayString('discount_add_failed');
				return $result;
			}

		} else {
			$result['hasError'] = true;
			$result['errorMessage'] = getDisplayString('discount_exists');
			return $result;
		}
	}
	else {
		$existing_discount = rpress_get_discount_by_code( $posted['code'] );
		if (!$existing_discount || $existing_discount->ID == $discountId) {
			$old_discount     = new RPRESS_Discount( (int)$discountId );
			$posted['uses'] = rpress_get_discount_uses( $old_discount->ID );
			if ( !rpress_store_discount( $posted, $discountId ) ) {
				$result['hasError'] = true;
				$result['errorMessage'] = getDisplayString('discount_update_failed');
				return $result;
			}
		} else {
			$result['hasError'] = true;
			$result['errorMessage'] = getDisplayString('discount_exists');
			return $result;
		}
	}

	return $result;
}

function delete_discount( $data ) {
	$discountId = $data['discount-id'];
	rpress_remove_discount( $discountId );
	return array (
		'hasError' => false,
		'errorMessage' => ''
	);
}

function get_customers( $request ) {
	$params = $request->get_json_params();

	$paged     = isset( $params['page'] ) ? $params['page'] : 1;
	$per_page  = $params['numItemsPerPage'];
	$offset  = $per_page * ( $paged - 1 );
	$data    = array();

	$orderby  = isset( $params['orderBy'] )  ? $params['orderBy']   : 'id';
	$order    = isset( $params['orderDirection'] )    ? $params['orderDirection']                    : 'DESC';
	$search   = isset( $params['filterStr'] )        ? sanitize_text_field( $params['filterStr'] ) : null;

	if ($orderby == 'none') {
		$orderby = 'id';
		$order = 'DESC';
	}

	$args    = array(
		'number'  => $per_page,
		'offset'  => $offset,
		'order'   => $order,
		'orderby' => $orderby
	);

	if( is_email( $search ) ) {
		$args['email'] = $search;
	} elseif( is_numeric( $search ) ) {
		$args['id']    = $search;
	} elseif( strpos( $search, 'user:' ) !== false ) {
		$args['user_id'] = trim( str_replace( 'user:', '', $search ) );
	} else {
		$args['name']  = $search;
	}

	$customers  = RPRESS()->customers->get_customers( $args );

	if ( $customers ) {

		foreach ( $customers as $customer ) {

			$user_id = ! empty( $customer->user_id ) ? intval( $customer->user_id ) : 0;

			$data[] = array(
				'id'            => $customer->id,
				'user_id'       => $user_id,
				'name'          => $customer->name,
				'email'         => $customer->email,
				'date_created'  => $customer->date_created,
			);
		}
	}

	$totalCount    = RPRESS()->customers->count( $args );

	return array (
		'page' => $paged,
		'numItemsPerPage' => $per_page,
		'totalCount' => $totalCount,
		'customers' => $data
	);
}

function get_customer( $params ) {
	$customerId = absint(isset($params['id']) && !empty($params['id']) ? $params['id'] : 0);
	
	$data = array(
		'id' => $customerId
	);

	if ($customerId > 0) {
		$customer = new RPRESS_Customer( $customerId );

		$user_id = ! empty( $customer->user_id ) ? intval( $customer->user_id ) : 0;
		$rank = $customer->get_meta('rank');
		$rankId = ! empty( $rank ) ? $rank['id'] : 1;
		$rankFromDate = ! empty( $rank ) ? $rank['rankFromDate'] : '';
		$rankSetting = array();
		foreach(Rank_Settings as $rs) {
			if ($rs['id'] == $rankId) {
				$rankSetting = $rs;
				break;
			}
		}
		$pointCount = $customer->get_meta('pointCount');
		$pointCount = ! empty( $pointCount ) ? intval( $pointCount ) : 0;

		$promotionItems = $customer->get_meta('promotionItems');
		if ($promotionItems === false || $promotionItems === '') {
			unset($promotionItems);
		}
		$promotionItems = isset($promotionItems) ? $promotionItems : [];

		$pointUsed = $customer->get_meta('pointUsed');
		$pointUsed = ! empty( $pointUsed ) ? intval( $pointUsed ) : 0;

		$data['user_id']     	 =    $user_id;
		$data['name']      		 =    $customer->name;
		$data['email']           =    $customer->email;
		$data['date_created']    =    $customer->date_created;
		if ($rankFromDate != '') {
			$data['rankTitle']   =    $rankSetting['title'] . ' (' . $rankFromDate . ')';
		}
		else {
			$data['rankTitle']   =    $rankSetting['title'];
		}
		$data['rankMessage']     =    $rankSetting['message'];
		$data['pointCount']      =    $pointCount;
		$data['promotionItems']  =    $promotionItems;
		$data['pointUsed']       =    $pointUsed;
		$data['email']           =    $customer->email;
		$data['date_created']    =    $customer->date_created;
	}

	return $data;
}

function updateCustomerRankAndPromotion($customer, $customerPointCount) {
	$rank = $customer->get_meta('rank');
	if ($rank === false || $rank === '') {
		unset($rank);
	}
	$updatedRankId = 1;	
	foreach(Rank_Settings as $rs) {
		if ($customerPointCount >= $rs['min'] && $customerPointCount <= $rs['max']) {
			$updatedRankId = $rs['id'];
			break;
		}
	}
	if ( ! isset( $rank ) ) {
		$newRank = array(
			'id' => $updatedRankId,
			'rankFromDate' => current_time( 'Y-m-d H:i:s' )
		);
		$customer->add_meta( 'rank', $newRank );
	}
	else if ($updatedRankId != $rank['id'])  {
		$rank['id'] = $updatedRankId;
		$rank['rankFromDate'] = current_time( 'Y-m-d H:i:s' );
		$customer->update_meta( 'rank', $rank );
	}

	$pointUsed = $customer->get_meta('pointUsed');
	if ($pointUsed === false || $pointUsed === '') {
		unset($pointUsed);
	}
	$updatedPointUsed = isset($pointUsed) ? intval($pointUsed) : 0;

	$promotionItems = $customer->get_meta('promotionItems');
	if ($promotionItems === false || $promotionItems === '') {
		unset($promotionItems);
	}
	$updatedPromotionItems = isset($promotionItems) ? $promotionItems : [];

	foreach(Promotion_Setting as $ps) {
		if ($ps['type'] == 'repeat' && $ps['status'] == 'active') {
			if ($updatedPointUsed > $customerPointCount) {
				$updatedPointUsed = floor($customerPointCount / $ps['each']) * $ps['each'];
			}
			$size = $customerPointCount - $updatedPointUsed;
			if ($size > 0) {
				$numItems = floor($size / $ps['each']);
				if ($numItems > 0) {
					$updatedPointUsed += $numItems * $ps['each'];
					for($i = 0; $i < $numItems; $i++) {
						$updatedPromotionItems[] = array(
							'id' => uniqid(),
							'type' => $ps['type'],
							'messageCode' => $ps['messageCode'],
							'title' => $ps['message'],
							'isDone' => false,
							'createdDate' => current_time( 'Y-m-d H:i:s' )
						);
					}
				}
			}
		}
	}

	if ( ! isset( $pointUsed ) ) {
		$customer->add_meta( 'pointUsed', $updatedPointUsed );
	}
	else {
		$customer->update_meta( 'pointUsed', $updatedPointUsed );
	}

	if ( ! isset( $promotionItems ) ) {
		$customer->add_meta( 'promotionItems', $updatedPromotionItems );
	}
	else {
		$customer->update_meta( 'promotionItems', $updatedPromotionItems );
	}
}

function save_customer( $request ) {
	$data = $request->get_json_params();
	$id = $data['id'];

	$result = array (
		'hasError' => false,
		'errorMessage' => ''
	);

	try
	{
		$customer = new RPRESS_Customer( $id );

		if ( empty( $customer->id ) ) {
			return $result;
		}

		$pointCount = $customer->get_meta('pointCount');
		if ($pointCount === false || $pointCount === '') {
			unset($pointCount);
		}
		$updatedPointCount = !empty($pointCount) ? intval($pointCount) : 0;
/*
		$updatedPointCount = !empty($data['pointCount']) ? intval($data['pointCount']) : 0;
		if ( ! isset( $pointCount ) ) {
			$customer->add_meta( 'pointCount', $updatedPointCount );
		}
		else {
			$customer->update_meta( 'pointCount', $updatedPointCount );
		}
*/
		updateCustomerRankAndPromotion($customer, $updatedPointCount);

		$promotionItems = $customer->get_meta('promotionItems');
		if ($promotionItems === false || $promotionItems === '') {
			unset($promotionItems);
		}
		$promotionItems = isset($promotionItems) ? $promotionItems : [];
		for($i=0; $i < count($promotionItems); $i++) {
			for($j=0; $i < count($data['promotionItems']); $j++) {
				if ($data['promotionItems'][$j]['id'] == $promotionItems[$i]['id']) {
					$isDone = $data['promotionItems'][$j]['isDone'];
					$promotionItems[$i]['isDone'] = $isDone;
					$promotionItems[$i]['completedDate'] = $isDone == true ? current_time( 'Y-m-d H:i:s' ) : '';
					break;
				}
			}
		}
		$customer->update_meta( 'promotionItems', $promotionItems );
	}
	catch(Exception $ex) {
		$result = array (
			'hasError' => true,
			'errorMessage' => $ex->getMessage()
		);
	}

	return $result;
}

function crp_update_clm_on_complete(  $payment_id, $new_status ) {
	if ( !empty( $payment_id ) && $new_status === Point_Settings['runWhenStatus'] ) 	{
		$customer = new RPRESS_Customer( rpress_get_payment_customer_id( $payment_id ) );
		if ( !empty( $customer->id ) ) {
			$payment      = new RPRESS_Payment( $payment_id );
			$cart_details = $payment->cart_details;
			if ( is_array( $cart_details ) && !empty( $cart_details ) ) {
				if ( Point_Settings['numOfItemsOrAmounts'] > 0) {
					$size = 0;
					if (Point_Settings['type'] == 'quantity') {
						foreach($cart_details as $cart_detail) {
							$size +=  isset($cart_detail['quantity']) ? intval($cart_detail['quantity']) : 1;
						}
					}
					else if (Point_Settings['type'] == 'amount') {
						$total = rpress_get_payment_amount( $payment->ID );
						$total  = ! empty( $total ) ? $total : 0;
						$size = $total;
					}
					$plus = round($size / Point_Settings['numOfItemsOrAmounts']) * Point_Settings['convertedPoints'];
					$pointCount = $customer->get_meta('pointCount');
					if ($pointCount === false || $pointCount === '') {
						unset($pointCount);
					}
					$updatedPointCount = !empty($pointCount) ? (intval($pointCount) + $plus) : $plus;
					if ( ! isset( $pointCount ) ) {
						$customer->add_meta( 'pointCount', $updatedPointCount );
					}
					else {
						$customer->update_meta( 'pointCount', $updatedPointCount );
					}

					updateCustomerRankAndPromotion($customer, $updatedPointCount);
				}
			}     
		}
	}
}
add_action( 'rpress_update_order_status', 'crp_update_clm_on_complete' , 1, 2 );

// API



/**
 * Get Cart Items By Key
 *
 * @since       1.0
 * @param       int | key
 * @return      array | cart items array
 */
function rpress_get_cart_items_by_key( $key ) {
  $cart_items_arr = array();
  if( $key !== '' ) {
    $cart_items = rpress_get_cart_contents();
    if( is_array( $cart_items ) && !empty( $cart_items ) ) {
      $items_in_cart = $cart_items[$key];
      if( is_array( $items_in_cart ) ) {
        if( isset( $items_in_cart['addon_items'] ) ) {
          $cart_items_arr = $items_in_cart['addon_items'];
        }
      }
    }
  }
  return $cart_items_arr;
}

/**
 * Get Cart Items Price
 *
 * @since       1.0
 * @param       int | key
 * @return      int | total price for cart
 */
function rpress_get_cart_item_by_price( $key ) {
  $cart_items_price = array();

  if( $key !== '' ) {
    $cart_items = rpress_get_cart_contents();

    if( is_array( $cart_items ) && !empty( $cart_items ) ) {
      $items_in_cart = $cart_items[$key];
      if( is_array( $items_in_cart ) ) {
        $item_price = rpress_get_fooditem_price( $items_in_cart['id'] );

        if( $items_in_cart['quantity'] > 0 ) {
          $item_price = $item_price * $items_in_cart['quantity'];
        }
        array_push( $cart_items_price, $item_price );

        if( isset( $items_in_cart['addon_items'] ) && is_array( $items_in_cart['addon_items'] ) ) {
          foreach( $items_in_cart['addon_items'] as $item_list ) {
            array_push( $cart_items_price, $item_list['price'] );
          }
        }

      }
    }
  }

  $cart_item_total = array_sum($cart_items_price);
  return $cart_item_total;
}

function addon_category_taxonomy_custom_fields($tag) {
  $t_id = $tag->term_id;
  $term_meta = get_option( "taxonomy_term_$t_id" );
  $use_addon_like =  isset($term_meta['use_it_like']) ? $term_meta['use_it_like'] : 'checkbox';
?>
<?php if( $tag->parent != 0 ): ?>
<tr class="form-field">
  <th scope="row" valign="top">
    <label for="price_id"><?php _e('Price'); ?></label>
  </th>
  <td>
    <input type="number" step=".01" name="term_meta[price]" id="term_meta[price]" size="25" style="width:15%;" value="<?php echo $term_meta['price'] ? $term_meta['price'] : ''; ?>"><br />
    <span class="description"><?php _e('Price for this addon item'); ?></span>
  </td>
</tr>
<?php endif; ?>

<?php if( $tag->parent == 0 ): ?>
<tr class="form-field">
  <th scope="row" valign="top">
    <label for="use_it_as">
      <?php _e('Addon item selection type', 'restropress'); ?></label>
  </th>
  <td>
    <div class="use-it-like-wrap">
      <label for="use_like_radio">
        <input id="use_like_radio" type="radio" value="radio" name="term_meta[use_it_like]" <?php checked( $use_addon_like, 'radio'); ?> >
          <?php _e('Single item', 'restropress'); ?>
      </label>
      <br/><br/>
      <label for="use_like_checkbox">
        <input id="use_like_checkbox" type="radio" value="checkbox" name="term_meta[use_it_like]" <?php checked( $use_addon_like, 'checkbox'); ?> >
          <?php _e('Multiple Items', 'restropress'); ?>
      </label>
    </div>
  </td>
</tr>
<?php endif; ?>

<tr class="form-field">
  <th scope="row" valign="top">
    <label for="status_id"><?php _e('Status'); ?></label>
  </th>
  <td>
  <?php
  	$addon_statuses = rpress_get_addon_statuses();
	$current_addon_status = isset($term_meta['status']) ? $term_meta['status'] : 'active';
	$options = '<div class="rp-addon-status-wrapper"><select id="status_id" name="term_meta[status]">';
	foreach( $addon_statuses as $status_id => $status_label ) {
		$options .= '<option value="' . $status_id  . '" ' . rp_selected( $current_addon_status, $status_id, false ) . '>' . $status_label . '</option>';
	}
	$options .= '</select>';
	$options .= '</div>';
	echo $options;
  ?>
  </td>
</tr>

<?php
}

/**
 * Update taxonomy meta data
 *
 * @since       1.0
 * @param       int | term_id
 * @return      update meta data
 */
function save_addon_category_custom_fields( $term_id ) {
  if( isset( $_POST['term_meta'] ) ) {
    $t_id = $term_id;
    $term_meta = get_option( "taxonomy_term_$t_id" );
    $cat_keys = array_keys( $_POST['term_meta'] );

    if( is_array( $cat_keys ) && !empty( $cat_keys ) ) {
      foreach ( $cat_keys as $key ){
        if( isset( $_POST['term_meta'][$key] ) ){
          $term_meta[$key] = $_POST['term_meta'][$key];
        }
      }
    }

    //save the option array
    update_option( "taxonomy_term_$t_id", $term_meta );
  }
}

// Add the fields to the "addon_category" taxonomy, using our callback function
add_action( 'addon_category_edit_form_fields', 'addon_category_taxonomy_custom_fields', 10, 2 );

// Save the changes made on the "addon_category" taxonomy, using our callback function
add_action( 'edited_addon_category', 'save_addon_category_custom_fields', 10, 2 );

/**
 * Get food item quantity in the cart by key
 *
 * @since       1.0
 * @param       int | cart_key
 * @return      array | cart items array
 */
function rpress_get_item_qty_by_key( $cart_key ) {
  if( $cart_key !== '' ) {
    $cart_items = rpress_get_cart_contents();
    $cart_items = $cart_items[$cart_key];
    return $cart_items['quantity'];
  }
}

add_action( 'wp_footer', 'rpress_popup' );
if( !function_exists('rpress_popup') ) {
  function rpress_popup() {
    rpress_get_template_part( 'rpress', 'popup' );
  }
}


add_action( 'rp_get_categories', 'get_fooditems_categories' );

if ( ! function_exists( 'get_fooditems_categories' ) ) {
  function get_fooditems_categories( $params ){
    global $data;
    $data = $params;
    rpress_get_template_part('rpress', 'get-categories');
  }
}

if ( ! function_exists( 'rpress_search_form' ) ) {
  function rpress_search_form() {
    ?>
    <div class="rpress-search-wrap rpress-live-search">
      <input id="rpress-food-search" type="text" placeholder="<?php _e('Search Food Item', 'restropress') ?>">
    </div>
    <?php
  }
}

add_action( 'before_fooditems_list', 'rpress_search_form' );

if ( ! function_exists( 'rpress_product_menu_tab' ) ) {
  /**
   * Output the rpress menu tab content.
   */
  function rpress_product_menu_tab() {
    echo do_shortcode('[rpress_items]');
  }
}

/**
 * Get special instruction for food items
 *
 * @since       1.0
 * @param       array | food items
 * @return      string | Special instruction string
 */
function get_special_instruction( $items ) {
  $instruction = '';

  if( is_array($items) ) {
    if( isset($items['options']) ) {
      $instruction = $items['options']['instruction'];
    } else {
      if( isset($items['instruction']) ) {
        $instruction = $items['instruction'];
      }
    }
  }

  return apply_filters( 'rpress_sepcial_instruction', $instruction );
}

/**
 * Get instruction in the cart by key
 *
 * @since       1.0
 * @param       int | cart_key
 * @return      string | Special instruction string
 */
function rpress_get_instruction_by_key( $cart_key ) {
  $instruction = '';
  if( $cart_key !== '' ) {
    $cart_items = rpress_get_cart_contents();
    $cart_items = $cart_items[$cart_key];
    if( isset($cart_items['instruction']) ) {
      $instruction = !empty($cart_items['instruction']) ? $cart_items['instruction'] : '';
    }
  }
  return $instruction;
}

/**
 * Show delivery options in the cart
 *
 * @since       1.0.2
 * @param       void
 * @return      string | Outputs the html for the delivery options with texts
 */
function get_delivery_options( $changeble ) {
  $color = rpress_get_option( 'checkout_color', 'red' );
  $service_date = isset( $_COOKIE['delivery_date'] ) ? $_COOKIE['delivery_date'] : '';
  ob_start();
  ?>
  <div class="delivery-wrap">
    <div class="delivery-opts">
      <?php if ( !empty( $_COOKIE['service_type'] ) ) : ?>
      <span class="delMethod">
        <?php echo rpress_service_label( $_COOKIE['service_type'] ) . ', ' . $service_date; ?></span>
        <?php if( !empty( $_COOKIE['service_time'] ) ) : ?>
          <span class="delTime">
            <?php printf(__( 'at %s', 'restropress' ), sanitize_text_field( $_COOKIE['service_time'] )); ?>
          </span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php if( $changeble && !empty( $_COOKIE['service_type'] ) ) : ?>
      <a href="#" class="delivery-change <?php echo $color; ?>"><?php esc_html_e( 'Change?', 'restropress' ); ?></a>
    <?php endif; ?>
  </div>
  <?php
  $data = ob_get_contents();
  ob_get_clean();
  return $data;
}

/**
 * Stores delivery address meta
 *
 * @since       1.0.3
 * @param       array | Delivery address meta array
 * @return      array | Custom data with delivery address meta array
 */
function rpress_store_custom_fields( $delivery_address_meta ) {
  $delivery_address_meta['address']   = !empty( $_POST['rpress_street_address'] ) ? sanitize_text_field( $_POST['rpress_street_address'] ) : '';
  $delivery_address_meta['flat']      = !empty( $_POST['rpress_apt_suite'] ) ? sanitize_text_field( $_POST['rpress_apt_suite'] ) : '';
  $delivery_address_meta['city']      = !empty( $_POST['rpress_city'] ) ? sanitize_text_field( $_POST['rpress_city'] ) : '';
  $delivery_address_meta['postcode']  = !empty( $_POST['rpress_postcode'] ) ? sanitize_text_field( $_POST['rpress_postcode'] ) : '';
  return $delivery_address_meta;
}
add_filter( 'rpress_delivery_address_meta', 'rpress_store_custom_fields');


/**
* Add order note to the order
*/
add_filter( 'rpress_order_note_meta', 'rpress_order_note_fields' );
function rpress_order_note_fields( $order_note ) {
  $order_note = isset( $_POST['rpress_order_note'] ) ? sanitize_text_field( $_POST['rpress_order_note'] ) : '';
  return $order_note;
}

/**
* Add phone number to payment meta
*/
add_filter( 'rpress_payment_meta', 'rpress_add_phone' );
function rpress_add_phone( $payment_meta ) {
  if( !empty( $_POST['rpress_phone'] ) )
    $payment_meta['phone']  = $_POST['rpress_phone'];
  return $payment_meta;
}

/**
 * Get Service type
 *
 * @since       1.0.4
 * @param       Int | Payment_id
 * @return      string | Service type string
 */
function rpress_get_service_type( $payment_id ) {
  if( $payment_id  ) {
    $service_type = get_post_meta( $payment_id, '_rpress_delivery_type', true );
    return strtolower( $service_type );
  }
}

/* Remove View Link From Food Items */
add_filter('post_row_actions','rpress_remove_view_link', 10, 2);

function rpress_remove_view_link($actions, $post){
  if ($post->post_type =="fooditem"){
    unset($actions['view']);
  }
  return $actions;
}

/* Remove View Link From Food Addon Category */
add_filter('addon_category_row_actions','rpress_remove_tax_view_link', 10, 2);

function rpress_remove_tax_view_link($actions, $taxonomy) {
    if( $taxonomy->taxonomy == 'addon_category' ) {
        unset($actions['view']);
    }
    return $actions;
}

/* Remove View Link From Food Category */
add_filter('food-category_row_actions','rpress_remove_food_cat_view_link', 10, 2);

function rpress_remove_food_cat_view_link($actions, $taxonomy) {
  if( $taxonomy->taxonomy == 'food-category' ) {
    unset($actions['view']);
  }
  return $actions;
}

function getDeliveryTime($service_type) {
	if ($service_type == 'delivery') {
		return (defined( 'Delivery_Time' ) ? Delivery_Time : 30);
	}
	else {
		return 0;
	}
}

/**
 * Get store timings for the store
 *
 * @since       1.0.0
 * @return      array | store timings
 */
function rp_get_store_timings( $hide_past_time = true, $service_type = '' ) {
  $current_time = current_time( 'timestamp' );
  $prep_time = !empty( rpress_get_option( 'prep_time' ) ) ? rpress_get_option( 'prep_time' ) : 0;
  $open_time = !empty( rpress_get_option( 'open_time' ) ) ? rpress_get_option( 'open_time' ) : '9:00am';
  $close_time = !empty( rpress_get_option( 'close_time' ) ) ? rpress_get_option( 'close_time' ) : '11:30pm';
  $food_order_closed = get_option('food_order_closed');

  if (defined( 'Specific_Food_Order_Opening_Time' ) && array_key_exists(date('D'), Specific_Food_Order_Opening_Time)) {
	$specific_time = Specific_Food_Order_Opening_Time[date('D')];
	$open_time = $specific_time['open_time'];
	$close_time = $specific_time['close_time'];
  }

  $time_interval = apply_filters( 'rp_store_time_interval', 15 );
  $time_interval = $time_interval * 60;

  $prep_time = $prep_time + getDeliveryTime($service_type);
  $prep_time  = $prep_time * 60;
  $open_time  = strtotime( date_i18n( 'Y-m-d' ) . ' ' . $open_time );
  $close_time = strtotime( date_i18n( 'Y-m-d' ) . ' ' . $close_time );
  $time_today = apply_filters( 'rpress_timing_for_today', true );

  if ($food_order_closed == 1) {
	$open_time = $current_time;
	$close_time = $current_time;
  }

  $store_times = range( $open_time, $close_time, $time_interval );

  //If not today then return normal time
  if( !$time_today ) return $store_times;

  //Add prep time to current time to determine the time to display for the dropdown
  if( $prep_time > 0 ) {
    $current_time = $current_time + $prep_time;
  }
  //Store timings for today.
  $store_timings = [];
  foreach( $store_times as $store_time ) {
    if( $hide_past_time ) {
      if( $store_time > $current_time ) {
        $store_timings[] = $store_time;
      }
    } else {
      $store_timings[] = $store_time;
    }
  }
  if ($service_type == 'delivery' && defined( 'Delivery_Close_Before_Num_Slot' )) {
	  $num_to_removes = Delivery_Close_Before_Num_Slot;
	  while($num_to_removes > 0) {
		  if (count($store_timings) > 0) {
			array_pop($store_timings);
		  }
		  $num_to_removes = $num_to_removes - 1;
	  }
  }
  return $store_timings;
}

/**
 * Get current time
 *
 * @since       1.0.0
 * @return      string | current time
 */
function rp_get_current_time() {
  $current_time = '';
  $timezone = get_option( 'timezone_string' );
  if( !empty( $timezone ) ) {
    $tz = new DateTimeZone( $timezone );
    $dt = new DateTime( "now", $tz );
    $current_time = $dt->format("H:i:s");
  }
  return $current_time;
}

/**
 * Get current date
 *
 * @since       1.0.0
 * @return      string | current date
 */
function rp_current_date( $format = '' ) {
  $date_format  = empty( $format ) ? get_option( 'date_format' ) : $format;
  $date_i18n = date_i18n( $date_format );
  return apply_filters( 'rpress_current_date', $date_i18n );
}

/**
 * Get local date from date string
 *
 * @since       1.0.0
 * @return      string | localized date based on date string
 */
function rpress_local_date( $date ) {
  $date_format = apply_filters( 'rpress_date_format', get_option( 'date_format', true ) );
  $timestamp  = strtotime( $date );
  $local_date = empty( get_option( 'timezone_string' ) ) ? date_i18n( $date_format, $timestamp ) : wp_date( $date_format, $timestamp );
  return apply_filters( 'rpress_local_date', $local_date, $date );
}

/**
 * Get list of categories
 *
 * @since 2.2.4
 * @return array of categories
 */
function rpress_get_categories( $params = array() ) {

  if( !empty( $params['ids'] ) ) {
    $params['include'] = $params['ids'];
    $params['orderby'] = 'include';
  }

  unset( $params['ids'] );
  unset( $params['orderby'] );

  $defaults = array(
    'taxonomy'    => 'food-category',
    'hide_empty'  => true,
    'orderby'     => 'slug',
    'order'       => 'ASC',
  );
  $term_args = wp_parse_args( $params, $defaults );
  $term_args = apply_filters( 'rpress_get_categories', $term_args );
  $get_all_items = get_terms( $term_args );

  return $get_all_items;
}

function rpress_get_service_types() {
  $service_types = array(
    'delivery'  => __( 'Delivery', 'restropress' ),
    'pickup'    => __( 'Pickup', 'restropress' )
  );
  return apply_filters( 'rpress_service_type', $service_types );
}

/**
* Get Store service hours
* @since 3.0
* @param string $service_type Select service type
* @param bool $current_time_aware if current_time_aware is set true then it would show the next time from now otherwise it would show the default store timings
* @return store time
*/
function rp_get_store_service_hours( $service_type, $current_time_aware = true, $selected_time  ) {

  if ( empty( $service_type ) ) {
    return;
  }

  $time_format = get_option( 'time_format', true );
  $time_format = apply_filters( 'rp_store_time_format', $time_format );

  $current_time = !empty( rp_get_current_time() ) ? rp_get_current_time() : date( $time_format );
  $store_times = rp_get_store_timings( false );

  if ( $service_type == 'delivery' ) {
    $store_timings = apply_filters( 'rpress_store_delivery_timings', $store_times );
  } else {
    $store_timings = apply_filters( 'rpress_store_pickup_timings', $store_times );
  }

  $store_timings_for_today = apply_filters( 'rpress_timing_for_today', true );

  if( is_array( $store_timings ) ) {

    foreach( $store_timings as $time ) {

      // Bring both curent time and Selected time to Admin Time Format
      echo $store_time = date( $time_format, $time );
      $selected_time = date( $time_format, strtotime( $selected_time ) );

      if ( $store_timings_for_today ) {

        // Remove any extra space in Current Time and Selected Time
        $timing_slug = str_replace( ' ', '', $store_time );
        $selected_time = str_replace( ' ', '', $selected_time );

        if( $current_time_aware ) {

          if ( strtotime( $store_time ) > strtotime( $current_time ) ) { ?>

            <option <?php selected( $selected_time, $timing_slug ); ?> value='<?php echo $store_time; ?>'>
              <?php echo $store_time; ?>
            </option>

          <?php }

        } else { ?>

          <option <?php selected( $selected_time, $timing_slug ); ?> value='<?php echo $store_time; ?>'>
            <?php echo $store_time; ?>
          </option>

        <?php }
      }
    }
  }
}

/**
 * Get list of categories/subcategories
 *
 * @since 2.3
 * @return array of Get list of categories/subcategories
 */
function rpress_get_child_cats( $category ) {
  $taxonomy_name = 'food-category';
  $parent_term = $category[0];
  $get_child_terms = get_terms( $taxonomy_name,
      ['child_of'=> $parent_term ] );

  if ( empty( $get_child_terms ) ) {
    $parent_terms = array(
      'taxonomy'    => $taxonomy_name,
      'hide_empty'  => true,
      'include'     => $category,
    );

    $get_child_terms = get_terms( $parent_terms );
  }
  return $get_child_terms;
}

add_filter( 'post_updated_messages', 'rpress_fooditem_update_messages' );
function rpress_fooditem_update_messages( $messages ) {
  global $post, $post_ID;

  $post_types = get_post_types( array( 'show_ui' => true, '_builtin' => false ), 'objects' );

  foreach( $post_types as $post_type => $post_object ) {
    if ( $post_type == 'fooditem' ) {
      $messages[$post_type] = array(
        0  => '', // Unused. Messages start at index 1.
        1  => sprintf( __( '%s updated.' ), $post_object->labels->singular_name ),
        2  => __( 'Custom field updated.' ),
        3  => __( 'Custom field deleted.' ),
        4  => sprintf( __( '%s updated.' ), $post_object->labels->singular_name ),
        5  => isset( $_GET['revision']) ? sprintf( __( '%s restored to revision from %s' ), $post_object->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
        6  => sprintf( __( '%s published.' ), $post_object->labels->singular_name ),
        7  => sprintf( __( '%s saved.' ), $post_object->labels->singular_name ),
        8  => sprintf( __( '%s submitted'), $post_object->labels->singular_name),
        9  => sprintf( __( '%s scheduled for: <strong>%1$s</strong>'), $post_object->labels->singular_name, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), $post_object->labels->singular_name ),
        10 => sprintf( __( '%s draft updated.'), $post_object->labels->singular_name ),
        );
    }
  }

  return $messages;

}

/**
 * Return the html selected attribute if stringified $value is found in array of stringified $options
 * or if stringified $value is the same as scalar stringified $options.
 *
 * @param string|int       $value   Value to find within options.
 * @param string|int|array $options Options to go through when looking for value.
 * @return string
 */
function rp_selected( $value, $options ) {
  if ( is_array( $options ) ) {
    $options = array_map( 'strval', $options );
    return selected( in_array( (string) $value, $options, true ), true, false );
  }
  return selected( $value, $options, false );
}


/**
 * Return the currently selected service type
 *
 * @since       2.5
 * @param       string | type
 * @return      string | Currently selected service type
 */
function rpress_selected_service( $type = '' ) {
  $service_type = isset( $_COOKIE['service_type'] ) ? $_COOKIE['service_type'] : '';
  //Return service type label when $type is label
  if( $type == 'label' )
    $service_type = rpress_service_label( $service_type );

  return $service_type;
}

/**
 * Return the service type label based on the service slug.
 *
 * @since       2.5
 * @param       string | service type
 * @return      string | Service type label
 */
function rpress_service_label( $service ) {
  $service_types = array(
    'delivery'  => __( 'Delivery', 'restropress' ),
    'pickup'    => __( 'Pickup', 'restropress' ),
  );
  //Allow to filter the service types.
  $service_types = apply_filters( 'rpress_service_types', $service_types );

  //Check for the service key in the service types and return the service type label
  if( array_key_exists( $service, $service_types ) )
    $service = $service_types[$service];

  return $service;
}

/**
 * Save order type in session
 *
 * @since       1.0.4
 * @param       string | Delivery Type
 * @param           string | Delivery Time
 * @return      array  | Session array for delivery type and delivery time
 */
function rpress_checkout_delivery_type( $service_type, $service_time ) {

  $_COOKIE['service_type'] = $service_type;
  $_COOKIE['service_time'] = $service_time;
}

/**
 * Validates the cart before checkout
 *
 * @since       2.5
 * @param       void
 * @return      array | Respose as success/error
 */
function rpress_pre_validate_order(){

  $service_type 	= !empty( $_COOKIE['service_type'] ) ? $_COOKIE['service_type'] : '';
  $service_time 	= !empty( $_COOKIE['service_time'] ) ? $_COOKIE['service_time'] : '';
  $service_date 	= !empty( $_COOKIE['service_date'] ) ? $_COOKIE['service_date'] : current_time( 'Y-m-d' );
  $prep_time 			= rpress_get_option( 'prep_time', 0 );
  $prep_time = $prep_time + getDeliveryTime($service_type);
  $prep_time  		= $prep_time * 60;
  $current_time 	= current_time( 'timestamp' );
  $food_order_closed = get_option('food_order_closed');
  
  // Custom code
  $maxNumOrdersPerSlot = defined( 'Max_Num_Orders_Per_Slot' ) ? Max_Num_Orders_Per_Slot : 100000;
  $maxTotalAmountPerSlot = defined( 'Max_Total_Amount_Per_Slot' ) ? Max_Total_Amount_Per_Slot : 100000000;

  if( $prep_time > 0 ) {
    $current_time = $current_time + $prep_time;
  }

  $service_datetime = strtotime( $service_date . ' ' . $service_time );

  //Check minimum order
  $enable_minimum_order = rpress_get_option( 'allow_minimum_order' );
  $minimum_order_price_delivery = rpress_get_option('minimum_order_price');
  $minimum_order_price_delivery = floatval( $minimum_order_price_delivery );
  $minimum_order_price_pickup = rpress_get_option( 'minimum_order_price_pickup' );
  $minimum_order_price_pickup = floatval( $minimum_order_price_pickup );

  // Custom code
  $numOrdersPerSlot = 0;
  $totalAmountPerSlot = 0;
  if ( !empty( $_COOKIE['service_time'] ) ) {
	global $wpdb;
	$result = $wpdb->get_results('SELECT CAST(meta_value AS DECIMAL(19, 4)) AS meta_value FROM web_postmeta WHERE meta_key = "_rpress_payment_total" AND post_id IN (SELECT post_id FROM web_postmeta WHERE meta_key = "_rpress_delivery_time" AND meta_value = "' . $service_time . '" AND post_id IN (SELECT post_id FROM web_postmeta WHERE meta_key = "_rpress_delivery_date" AND meta_value = "' . $service_date . '"))');
	foreach($result as $row) {
	  $numOrdersPerSlot = $numOrdersPerSlot + 1;
	  $totalAmountPerSlot = $totalAmountPerSlot + $row->meta_value;
	}
  }
  
  if ($food_order_closed == 1) {
	$closed_message = rpress_get_option( 'store_closed_msg', __( 'Sorry, we are closed for ordering now.', 'restropress' ) );
    $response = array(
      'status' => 'error',
      'error_msg' =>  $closed_message
    );
  }
  else if ( $enable_minimum_order && $service_type == 'delivery' && rpress_get_cart_subtotal() < $minimum_order_price_delivery ) {
    $minimum_price_error = rpress_get_option('minimum_order_error');
    $minimum_order_formatted = rpress_currency_filter( rpress_format_amount( $minimum_order_price_delivery ) );
    $minimum_price_error = str_replace('{min_order_price}', $minimum_order_formatted, $minimum_price_error);
    $response = array( 'status' => 'error', 'minimum_price' => $minimum_order_price, 'error_msg' =>  $minimum_price_error  );
  }
  else if ( $enable_minimum_order && $service_type == 'pickup' && rpress_get_cart_subtotal() < $minimum_order_price_pickup ) {
    $minimum_price_error_pickup = rpress_get_option('minimum_order_error_pickup');
    $minimum_order_formatted = rpress_currency_filter( rpress_format_amount( $minimum_order_price_pickup ) );
    $minimum_price_error_pickup = str_replace('{min_order_price}', $minimum_order_formatted, $minimum_price_error_pickup);
    $response = array( 'status' => 'error', 'minimum_price' => $minimum_order_price_pickup, 'error_msg' =>  $minimum_price_error_pickup  );
  }
  else if( $current_time > $service_datetime && !empty( $_COOKIE['service_time'] ) ){
    $time_error = __( 'Please select a different time slot.', 'restropress' );
    $response = array(
      'status' => 'error',
      'error_msg' =>  $time_error
    );
  }
  else if( $numOrdersPerSlot >= $maxNumOrdersPerSlot || $totalAmountPerSlot >= $maxTotalAmountPerSlot ) { // Custom code
    $time_error = __( 'Please select a different time slot. This time is fully booked.', 'restropress' );
    $response = array(
      'status' => 'error',
      'error_msg' =>  $time_error
    );
  }
  else {
    $response = array( 'status' => 'success' );
  }
  return $response;
}

/**
 * Is Test Mode
 *
 * @since 1.0
 * @return bool $ret True if test mode is enabled, false otherwise
 */
function rpress_is_test_mode() {
	$ret = rpress_get_option( 'test_mode', false );
	return (bool) apply_filters( 'rpress_is_test_mode', $ret );
}

/**
 * Is Debug Mode
 *
 * @since 1.0
 * @return bool $ret True if debug mode is enabled, false otherwise
 */
function rpress_is_debug_mode() {
	$ret = rpress_get_option( 'debug_mode', false );
	if( defined( 'RPRESS_DEBUG_MODE' ) && RPRESS_DEBUG_MODE ) {
		$ret = true;
	}
	return (bool) apply_filters( 'rpress_is_debug_mode', $ret );
}

/**
 * Checks if Guest checkout is enabled
 *
 * @since 1.0
 * @return bool $ret True if guest checkout is enabled, false otherwise
 */
function rpress_no_guest_checkout() {
	$login_method = rpress_get_option( 'login_method', 'login_guest' );
	$ret = $login_method == 'login_only' ? true : false;
	return (bool) apply_filters( 'rpress_no_guest_checkout', $ret );
}

/**
 * Redirect to checkout immediately after adding items to the cart?
 *
 * @since 1.0.0
 * @return bool $ret True is redirect is enabled, false otherwise
 */
function rpress_straight_to_checkout() {
	$ret = rpress_get_option( 'redirect_on_add', false );
	return (bool) apply_filters( 'rpress_straight_to_checkout', $ret );
}

/**
 * Verify credit card numbers live?
 *
 * @since  1.0.0
 * @return bool $ret True is verify credit cards is live
 */
function rpress_is_cc_verify_enabled() {
	$ret = true;

	/*
	 * Enable if use a single gateway other than PayPal or Manual. We have to assume it accepts credit cards
	 * Enable if using more than one gateway if they aren't both PayPal and manual, again assuming credit card usage
	 */

	$gateways = rpress_get_enabled_payment_gateways();

	if ( count( $gateways ) == 1 && ! isset( $gateways['paypal'] ) && ! isset( $gateways['manual'] ) ) {
		$ret = true;
	} else if ( count( $gateways ) == 1 ) {
		$ret = false;
	} else if ( count( $gateways ) == 2 && isset( $gateways['paypal'] ) && isset( $gateways['manual'] ) ) {
		$ret = false;
	}

	return (bool) apply_filters( 'rpress_verify_credit_cards', $ret );
}

/**
 * Check if the current page is a RestroPress Page or not
 */
function is_restropress_page() {

	global $post;

	$rp_page = false;
  $menu_page = rpress_get_option( 'food_items_page', '' );

  if ( $post->ID == $menu_page ) {
    $rp_page = true;
  } else if ( has_shortcode($post->post_content, 'fooditems') ) {
		$rp_page = true;
	} else if ( has_shortcode($post->post_content, 'fooditem_checkout') ) {
		$rp_page = true;
	} else if ( has_shortcode($post->post_content, 'rpress_receipt') ) {
		$rp_page = true;
	}

	return apply_filters( 'is_a_restropress_page', $rp_page );
}

/**
 * Is Odd
 *
 * Checks whether an integer is odd.
 *
 * @since 1.0
 * @param int     $int The integer to check
 * @return bool Is the integer odd?
 */
function rpress_is_odd( $int ) {
	return (bool) ( $int & 1 );
}

/**
 * Get File Extension
 *
 * Returns the file extension of a filename.
 *
 * @since 1.0
 *
 * @param unknown $str File name
 *
 * @return mixed File extension
 */
function rpress_get_file_extension( $str ) {
	$parts = explode( '.', $str );
	return end( $parts );
}

/**
 * Checks if the string (filename) provided is an image URL
 *
 * @since 1.0
 * @param string  $str Filename
 * @return bool Whether or not the filename is an image
 */
function rpress_string_is_image_url( $str ) {
	$ext = rpress_get_file_extension( $str );

	switch ( strtolower( $ext ) ) {
		case 'jpg';
			$return = true;
			break;
		case 'png';
			$return = true;
			break;
		case 'gif';
			$return = true;
			break;
		default:
			$return = false;
			break;
	}

	return (bool) apply_filters( 'rpress_string_is_image', $return, $str );
}

/**
 * Get User IP
 *
 * Returns the IP address of the current visitor
 *
 * @since 1.0
 * @return string $ip User's IP address
 */
function rpress_get_ip() {

	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		// can include more than 1 ip, first is the public one
		$ip = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip = trim($ip[0]);
	} elseif( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	// Fix potential CSV returned from $_SERVER variables
	$ip_array = explode( ',', $ip );
	$ip_array = array_map( 'trim', $ip_array );

	return apply_filters( 'rpress_get_ip', $ip_array[0] );
}


/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since  1.0.0
 * @return mixed string $host if detected, false otherwise
 */
function rpress_get_host() {
	$host = false;

	if( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} elseif( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} elseif( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} elseif( DB_HOST == 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} elseif( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} elseif( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} elseif( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} elseif( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} elseif( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} elseif( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} elseif( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
		$host = 'Flywheel';
	} else {
		// Adding a general fallback for data gathering
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}


/**
 * Check site host
 *
 * @since  1.0.0
 * @param $host The host to check
 * @return bool true if host matches, false if not
 */
function rpress_is_host( $host = false ) {

	$return = false;

	if( $host ) {
		$host = str_replace( ' ', '', strtolower( $host ) );

		switch( $host ) {
			case 'wpengine':
				if( defined( 'WPE_APIKEY' ) )
					$return = true;
				break;
			case 'pagely':
				if( defined( 'PAGELYBIN' ) )
					$return = true;
				break;
			case 'icdsoft':
				if( DB_HOST == 'localhost:/tmp/mysql5.sock' )
					$return = true;
				break;
			case 'networksolutions':
				if( DB_HOST == 'mysqlv5' )
					$return = true;
				break;
			case 'ipage':
				if( strpos( DB_HOST, 'ipagemysql.com' ) !== false )
					$return = true;
				break;
			case 'ipower':
				if( strpos( DB_HOST, 'ipowermysql.com' ) !== false )
					$return = true;
				break;
			case 'mediatemplegrid':
				if( strpos( DB_HOST, '.gridserver.com' ) !== false )
					$return = true;
				break;
			case 'pairnetworks':
				if( strpos( DB_HOST, '.pair.com' ) !== false )
					$return = true;
				break;
			case 'rackspacecloud':
				if( strpos( DB_HOST, '.stabletransit.com' ) !== false )
					$return = true;
				break;
			case 'sysfix.eu':
			case 'sysfix.eupowerhosting':
				if( strpos( DB_HOST, '.sysfix.eu' ) !== false )
					$return = true;
				break;
			case 'flywheel':
				if( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false )
					$return = true;
				break;
			default:
				$return = false;
		}
	}

	return $return;
}


/**
 * Get Currencies
 *
 * @since 1.0
 * @return array $currencies A list of the available currencies
 */
function rpress_get_currencies() {
	$currencies = array(
		'USD'  => __( 'US Dollars (&#36;)', 'restropress' ),
		'EUR'  => __( 'Euros (&euro;)', 'restropress' ),
		'GBP'  => __( 'Pound Sterling (&pound;)', 'restropress' ),
		'AUD'  => __( 'Australian Dollars (&#36;)', 'restropress' ),
		'BRL'  => __( 'Brazilian Real (R&#36;)', 'restropress' ),
		'CAD'  => __( 'Canadian Dollars (&#36;)', 'restropress' ),
		'CZK'  => __( 'Czech Koruna', 'restropress' ),
		'DKK'  => __( 'Danish Krone', 'restropress' ),
		'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'restropress' ),
		'HUF'  => __( 'Hungarian Forint', 'restropress' ),
		'ILS'  => __( 'Israeli Shekel (&#8362;)', 'restropress' ),
		'JPY'  => __( 'Japanese Yen (&yen;)', 'restropress' ),
		'MYR'  => __( 'Malaysian Ringgits', 'restropress' ),
		'MXN'  => __( 'Mexican Peso (&#36;)', 'restropress' ),
		'NZD'  => __( 'New Zealand Dollar (&#36;)', 'restropress' ),
		'NOK'  => __( 'Norwegian Krone', 'restropress' ),
    'PKR'  => __( 'Pakistani Rupee', 'restropress' ),
		'PHP'  => __( 'Philippine Pesos', 'restropress' ),
		'PLN'  => __( 'Polish Zloty', 'restropress' ),
		'SGD'  => __( 'Singapore Dollar (&#36;)', 'restropress' ),
		'SEK'  => __( 'Swedish Krona', 'restropress' ),
		'CHF'  => __( 'Swiss Franc', 'restropress' ),
		'TWD'  => __( 'Taiwan New Dollars', 'restropress' ),
		'THB'  => __( 'Thai Baht (&#3647;)', 'restropress' ),
		'INR'  => __( 'Indian Rupee (&#8377;)', 'restropress' ),
		'TRY'  => __( 'Turkish Lira (&#8378;)', 'restropress' ),
		'RIAL' => __( 'Iranian Rial (&#65020;)', 'restropress' ),
		'RUB'  => __( 'Russian Rubles', 'restropress' ),
		'AOA'  => __( 'Angolan Kwanza', 'restropress' ),
    'NGN'  => __( 'Nigerian Naira (&#8358;)', 'restropress' ),
    'VND'  => __( 'Vietnamese dong', 'restropress' ),
	);

	return apply_filters( 'rpress_currencies', $currencies );
}

/**
 * Get the store's set currency
 *
 * @since 1.0
 * @return string The currency code
 */
function rpress_get_currency() {
	$currency = rpress_get_option( 'currency', 'USD' );
	return apply_filters( 'rpress_currency', $currency );
}

/**
 * Given a currency determine the symbol to use. If no currency given, site default is used.
 * If no symbol is determine, the currency string is returned.
 *
 * @since 1.0
 * @param  string $currency The currency string
 * @return string           The symbol to use for the currency
 */
function rpress_currency_symbol( $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = rpress_get_currency();
	}

	switch ( $currency ) :
		case "GBP" :
			$symbol = '&pound;';
			break;
		case "BRL" :
			$symbol = 'R&#36;';
			break;
		case "EUR" :
			$symbol = '&euro;';
			break;
    	case "INR" :
      		$symbol = '&#8377;';
      		break;
		case "USD" :
		case "AUD" :
		case "NZD" :
		case "CAD" :
		case "HKD" :
		case "MXN" :
		case "SGD" :
			$symbol = '&#36;';
			break;
		case "JPY" :
			$symbol = '&yen;';
			break;
		case "AOA" :
			$symbol = 'Kz';
			break;
    	case "NGN" :
      		$symbol = '&#8358;';
      		break;
		default :
			$symbol = $currency;
			break;
	endswitch;

	return apply_filters( 'rpress_currency_symbol', $symbol, $currency );
}

/**
 * Get the name of a currency
 *
 * @since  1.0.0
 * @param  string $code The currency code
 * @return string The currency's name
 */
function rpress_get_currency_name( $code = 'USD' ) {
	$currencies = rpress_get_currencies();
	$name       = isset( $currencies[ $code ] ) ? $currencies[ $code ] : $code;
	return apply_filters( 'rpress_currency_name', $name );
}

/**
 * Month Num To Name
 *
 * Takes a month number and returns the name three letter name of it.
 *
 * @since 1.0
 *
 * @param integer $n
 * @return string Short month name
 */
function rpress_month_num_to_name( $n ) {
	$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

	return date_i18n( "M", $timestamp );
}

/**
 * Get PHP Arg Separator Output
 *
 * @since 1.0
 * @return string Arg separator output
 */
function rpress_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
}

/**
 * Get the current page URL
 *
 * @since 1.0
 * @param  bool   $nocache  If we should bust cache on the returned URL
 * @return string $page_url Current page URL
 */
function rpress_get_current_page_url( $nocache = false ) {

	global $wp;

	if( get_option( 'permalink_structure' ) ) {

		$base = trailingslashit( home_url( $wp->request ) );

	} else {

		$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
		$base = remove_query_arg( array( 'post_type', 'name' ), $base );

	}

	$scheme = is_ssl() ? 'https' : 'http';
	$uri    = set_url_scheme( $base, $scheme );

	if ( is_front_page() ) {
		$uri = home_url( '/' );
	} elseif ( rpress_is_checkout() ) {
		$uri = rpress_get_checkout_uri();
	}

	$uri = apply_filters( 'rpress_get_current_page_url', $uri );

	if ( $nocache ) {
		$uri = rpress_add_cache_busting( $uri );
	}

	return $uri;
}

/**
 * Adds the 'nocache' parameter to the provided URL
 *
 * @since  1.0.0
 * @param  string $url The URL being requested
 * @return string      The URL with cache busting added or not
 */
function rpress_add_cache_busting( $url = '' ) {

	$no_cache_checkout = rpress_get_option( 'no_cache_checkout', false );

	if ( rpress_is_caching_plugin_active() || ( rpress_is_checkout() && $no_cache_checkout ) ) {
		$url = add_query_arg( 'nocache', 'true', $url );
	}

	return $url;
}

/**
 * Marks a function as deprecated and informs when it has been used.
 *
 * There is a hook rpress_deprecated_function_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @uses do_action() Calls 'rpress_deprecated_function_run' and passes the function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'rpress_deprecated_function_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string  $function    The function that was called
 * @param string  $version     The version of RestroPress that deprecated the function
 * @param string  $replacement Optional. The function that should have been called
 * @param array   $backtrace   Optional. Contains stack backtrace of deprecated function
 */
function _rpress_deprecated_function( $function, $version, $replacement = null, $backtrace = null ) {
	do_action( 'rpress_deprecated_function_run', $function, $replacement, $version );

	$show_errors = current_user_can( 'manage_options' );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'rpress_deprecated_function_trigger_error', $show_errors ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since RestroPress version %2$s! Use %3$s instead.', 'restropress' ), $function, $version, $replacement ) );
			trigger_error(  print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		} else {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since RestroPress version %2$s with no alternative available.', 'restropress' ), $function, $version ) );
			trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		}
	}
}

/**
 * Marks an argument in a function deprecated and informs when it's been used
 *
 * There is a hook rpress_deprecated_argument_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that has an argument being deprecated.
 *
 * @uses do_action() Calls 'rpress_deprecated_argument_run' and passes the argument, function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'rpress_deprecated_argument_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string  $argument    The arguemnt that is being deprecated
 * @param string  $function    The function that was called
 * @param string  $version     The version of WordPress that deprecated the function
 * @param string  $replacement Optional. The function that should have been called
 * @param array   $backtrace   Optional. Contains stack backtrace of deprecated function
 */
function _rpress_deprected_argument( $argument, $function, $version, $replacement = null, $backtrace = null ) {
	do_action( 'rpress_deprecated_argument_run', $argument, $function, $replacement, $version );

	$show_errors = current_user_can( 'manage_options' );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'rpress_deprecated_argument_trigger_error', $show_errors ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __( 'The %1$s argument of %2$s is <strong>deprecated</strong> since RestroPress version %3$s! Please use %4$s instead.', 'restropress' ), $argument, $function, $version, $replacement ) );
			trigger_error(  print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		} else {
			trigger_error( sprintf( __( 'The %1$s argument of %2$s is <strong>deprecated</strong> since RestroPress version %3$s with no alternative available.', 'restropress' ), $argument, $function, $version ) );
			trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		}
	}
}

/**
 * Checks whether function is disabled.
 *
 * @since 1.0.5
 *
 * @param string  $function Name of the function.
 * @return bool Whether or not function is disabled.
 */
function rpress_is_func_disabled( $function ) {
	$disabled = explode( ',',  ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

/**
 * RPRESS Let To Num
 *
 * Does Size Conversions
 *
 * @since  1.0.0
 * @usedby rpress_settings()
 * @author Chris Christoff
 *
 * @param unknown $v
 * @return int
 */
function rpress_let_to_num( $v ) {
	$l   = substr( $v, -1 );
	$ret = substr( $v, 0, -1 );

	switch ( strtoupper( $l ) ) {
		case 'P': // fall-through
		case 'T': // fall-through
		case 'G': // fall-through
		case 'M': // fall-through
		case 'K': // fall-through
			$ret *= 1024;
			break;
		default:
			break;
	}

	return (int) $ret;
}

/**
 * Retrieve the URL of the symlink directory
 *
 * @since 1.0
 * @return string $url URL of the symlink directory
 */
function rpress_get_symlink_url() {
	$wp_upload_dir = wp_upload_dir();
	wp_mkdir_p( $wp_upload_dir['basedir'] . '/rpress/symlinks' );
	$url = $wp_upload_dir['baseurl'] . '/rpress/symlinks';

	return apply_filters( 'rpress_get_symlink_url', $url );
}

/**
 * Retrieve the absolute path to the symlink directory
 *
 * @since 1.0
 * @return string $path Absolute path to the symlink directory
 */
function rpress_get_symlink_dir() {
	$wp_upload_dir = wp_upload_dir();
	wp_mkdir_p( $wp_upload_dir['basedir'] . '/rpress/symlinks' );
	$path = $wp_upload_dir['basedir'] . '/rpress/symlinks';

	return apply_filters( 'rpress_get_symlink_dir', $path );
}

/**
 * Retrieve the absolute path to the file upload directory without the trailing slash
 *
 * @since 1.0
 * @return string $path Absolute path to the RPRESS upload directory
 */
function rpress_get_upload_dir() {
	$wp_upload_dir = wp_upload_dir();
	wp_mkdir_p( $wp_upload_dir['basedir'] . '/rpress' );
	$path = $wp_upload_dir['basedir'] . '/rpress';

	return apply_filters( 'rpress_get_upload_dir', $path );
}

/**
 * Delete symbolic links after they have been used
 *
 * This function is only intended to be used by WordPress cron.
 *
 * @since 1.0
 * @return void
 */
function rpress_cleanup_file_symlinks() {

	// Bail if not in WordPress cron
	if ( ! rpress_doing_cron() ) {
		return;
	}

	$path = rpress_get_symlink_dir();
	$dir = opendir( $path );

	while ( ( $file = readdir( $dir ) ) !== false ) {
		if ( $file == '.' || $file == '..' )
			continue;

		$transient = get_transient( md5( $file ) );
		if ( $transient === false )
			@unlink( $path . '/' . $file );
	}
}
add_action( 'rpress_cleanup_file_symlinks', 'rpress_cleanup_file_symlinks' );

/**
 * Checks if SKUs are enabled
 *
 * @since  1.0.0
 * @author Daniel J Griffiths
 * @return bool $ret True if SKUs are enabled, false otherwise
 */
function rpress_use_skus() {
	$ret = rpress_get_option( 'enable_skus', false );
	return (bool) apply_filters( 'rpress_use_skus', $ret );
}

/**
 * Retrieve timezone
 *
 * @since  1.0.0
 * @return string $timezone The timezone ID
 */
function rpress_get_timezone_id() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) )
		return $timezone;

	// get UTC offset, if it isn't set return UTC
	if ( ! ( $utc_offset = 3600 * get_option( 'gmt_offset', 0 ) ) )
		return 'UTC';

	// attempt to guess the timezone string from the UTC offset
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	// last try, guess timezone string manually
	if ( $timezone === false ) {

		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst &&  $city['offset'] == $utc_offset )
					return $city['timezone_id'];
			}
		}
	}

	// fallback
	return 'UTC';
}

/**
 * Given an object or array of objects, convert them to arrays
 *
 * @since 1.0
 * @internal Updated in 2.6
 * @param    object|array $object An object or an array of objects
 * @return   array                An array or array of arrays, converted from the provided object(s)
 */
function rpress_object_to_array( $object = array() ) {

	if ( empty( $object ) || ( ! is_object( $object ) && ! is_array( $object ) ) ) {
		return $object;
	}

	if ( is_array( $object ) ) {
		$return = array();
		foreach ( $object as $item ) {
			if ( $object instanceof RPRESS_Payment ) {
				$return[] = $object->array_convert();
			} else {
				$return[] = rpress_object_to_array( $item );
			}

		}
	} else {
		if ( $object instanceof RPRESS_Payment ) {
			$return = $object->array_convert();
		} else {
			$return = get_object_vars( $object );

			// Now look at the items that came back and convert any nested objects to arrays
			foreach ( $return as $key => $value ) {
				$value = ( is_array( $value ) || is_object( $value ) ) ? rpress_object_to_array( $value ) : $value;
				$return[ $key ] = $value;
			}
		}
	}

	return $return;
}

/**
 * Set Upload Directory
 *
 * Sets the upload dir to rpress. This function is called from
 * rpress_change_fooditems_upload_dir()
 *
 * @since 1.0
 * @return array Upload directory information
 */
function rpress_set_upload_dir( $upload ) {

	// Override the year / month being based on the post publication date, if year/month organization is enabled
	if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
		// Generate the yearly and monthly dirs
		$time = current_time( 'mysql' );
		$y = substr( $time, 0, 4 );
		$m = substr( $time, 5, 2 );
		$upload['subdir'] = "/$y/$m";
	}

	$upload['subdir'] = '/rpress' . $upload['subdir'];
	$upload['path']   = $upload['basedir'] . $upload['subdir'];
	$upload['url']    = $upload['baseurl'] . $upload['subdir'];
	return $upload;
}

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since  1.0.0
 * @param  string $upgrade_action The upgrade action to check completion for
 * @return bool                   If the action has been added to the copmleted actions array
 */
function rpress_has_upgrade_completed( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades = rpress_get_completed_upgrades();

	return in_array( $upgrade_action, $completed_upgrades );

}

/**
 * Get's the array of completed upgrade actions
 *
 * @since  1.0.0
 * @return array The array of completed upgrades
 */
function rpress_get_completed_upgrades() {

	$completed_upgrades = get_option( 'rpress_completed_upgrades' );

	if ( false === $completed_upgrades ) {
		$completed_upgrades = array();
	}

	return $completed_upgrades;

}


if ( ! function_exists( 'cal_days_in_month' ) ) {
	// Fallback in case the calendar extension is not loaded in PHP
	// Only supports Gregorian calendar
	function cal_days_in_month( $calendar, $month, $year ) {
		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}


if ( ! function_exists( 'hash_equals' ) ) :
/**
 * Compare two strings in constant time.
 *
 * This function was added in PHP 5.6.
 * It can leak the length of a string.
 *
 * @since 1.0
 *
 * @param string $a Expected string.
 * @param string $b Actual string.
 * @return bool Whether strings are equal.
 */
function hash_equals( $a, $b ) {
	$a_length = strlen( $a );
	if ( $a_length !== strlen( $b ) ) {
		return false;
	}
	$result = 0;

	// Do not attempt to "optimize" this.
	for ( $i = 0; $i < $a_length; $i++ ) {
		$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
	}

	return $result === 0;
}
endif;

if ( ! function_exists( 'getallheaders' ) ) :

	/**
	 * Retrieve all headers
	 *
	 * Ensure getallheaders function exists in the case we're using nginx
	 *
	 * @since 1.0
	 * @return array
	 */
	function getallheaders() {
		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}
		return $headers;
	}

endif;

/**
 * Determines the receipt visibility status
 *
 * @return bool Whether the receipt is visible or not.
 */
function rpress_can_view_receipt( $payment_key = '' ) {

	$return = false;

	if ( empty( $payment_key ) ) {
		return $return;
	}

	global $rpress_receipt_args;

	$rpress_receipt_args['id'] = rpress_get_purchase_id_by_key( $payment_key );

	$user_id = (int) rpress_get_payment_user_id( $rpress_receipt_args['id'] );

	$payment_meta = rpress_get_payment_meta( $rpress_receipt_args['id'] );

	if ( is_user_logged_in() ) {
		if ( $user_id === (int) get_current_user_id() ) {
			$return = true;
		} elseif ( wp_get_current_user()->user_email === rpress_get_payment_user_email( $rpress_receipt_args['id'] ) ) {
			$return = true;
		} elseif ( current_user_can( 'view_shop_sensitive_data' ) ) {
			$return = true;
		}
	}

	$session = rpress_get_purchase_session();
	if ( ! empty( $session ) && ! is_user_logged_in() ) {
		if ( $session['purchase_key'] === $payment_meta['key'] ) {
			$return = true;
		}
	}

	return (bool) apply_filters( 'rpress_can_view_receipt', $return, $payment_key );
}

/**
 * Given a Payment ID, generate a link to IP address provider (ipinfo.io)
 *
 * @since 1.0
 * @param  int		$payment_id The Payment ID
 * @return string	A link to the IP details provider
 */
function rpress_payment_get_ip_address_url( $payment_id ) {

	$payment = new RPRESS_Payment( $payment_id );

	$base_url = 'https://ipinfo.io/';
	$provider_url = '<a href="' . esc_url( $base_url ) . esc_attr( $payment->ip ) . '" target="_blank">' . esc_attr( $payment->ip ) . '</a>';

	return apply_filters( 'rpress_payment_get_ip_address_url', $provider_url, $payment->ip, $payment_id );

}

/**
 * Abstraction for WordPress cron checking, to avoid code duplication.
 *
 * In future versions of RPRESS, this function will be changed to only refer to
 * RPRESS specific cron related jobs. You probably won't want to use it until then.
 *
 * @since 1.0
 *
 * @return boolean
 */
function rpress_doing_cron() {

	// Bail if not doing WordPress cron (>4.8.0)
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return true;

	// Bail if not doing WordPress cron (<4.8.0)
	} elseif ( defined( 'DOING_CRON' ) && ( true === DOING_CRON ) ) {
		return true;
	}

	// Default to false
	return false;
}

/**
 * Display a RestroPress help tip.
 *
 * @since  3.0
 *
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
 * @return string
 */
function rp_help_tip( $tip, $allow_html = false ) {
  if ( $allow_html ) {
    $tip = rpress_sanitize_tooltip( $tip );
  } else {
    $tip = esc_attr( $tip );
  }

  return '<span class="restropress-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Is pickup/delivery time enabled
 *
 * @since 1.0
 * @return bool $ret True if test mode is enabled, false otherwise
 */
function rpress_is_service_enabled( $service ) {
	return (bool) apply_filters( 'rpress_is_service_enabled', true, $service );
}

function rpress_fooditem_available( $fooditem_id ) {
	return (bool) apply_filters( 'rpress_is_orderable', true, $fooditem_id );
}

/** Get Singular Label
 *  @since 2.0.7
 *
 *  @param bool $lowercase
 *  @return string $defaults['singular'] Singular label
 */
function rp_get_label_singular( $lowercase = false ) {
  $defaults = rp_get_default_labels();
  return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Label
 *
 * @since 1.0
 * @return string $defaults['plural'] Plural label
 */
function rp_get_label_plural( $lowercase = false ) {
  $defaults = rp_get_default_labels();
  return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

/**
 * Get Default Labels
 *
 * @since 1.0
 * @return array $defaults Default labels
 */
function rp_get_default_labels() {
  $defaults = array(
     'singular' => __( 'Food Item', 'restropress' ),
     'plural'   => __( 'Food Items','restropress' )
  );
  return apply_filters( 'rp_default_fooditems_name', $defaults );
}

/**
 * Display notices to admins
 *
 * @since 2.6
 */
function rp_addon_activation_notice() {

  $items = get_transient( 'restropress_add_ons_feed' );
  if( ! $items ) {
    $items = rpress_fetch_items();
  }

  $statuses = array();

  if( is_array($items) && !empty($items) ) {

    foreach( $items as $key => $item ) {

      $class_name = trim($item->class_name);

      if( class_exists($class_name) ) {

        if( !get_option($item->license_string.'_status') ) {
          array_push( $statuses, 'empty' );
        } else {
          $status = get_option($item->license_string.'_status');
          array_push( $statuses, $status );
        }
      }
    }
  }

  if( !empty( $statuses ) && ( in_array( 'empty', $statuses) || in_array( 'invalid', $statuses) ) ) {

    $class = 'notice notice-error';
    $message = __( 'You have invalid or expired license keys for one or more addons of RestroPress. Please go to the <a href="%2$s">Extensions</a> page to update your licenses.', 'restropress' );
    $url = admin_url( 'admin.php?page=rpress-extensions' );

    printf( '<div class="%1$s"><p>' . $message . '</p></div>', esc_attr( $class ), $url );
  }
}
add_action( 'admin_notices', 'rp_addon_activation_notice' );
