<?php
/**
 * AJAX Functions
 *
 * Process the front-end AJAX actions.
 *
 * @package     RPRESS
 * @subpackage  Functions/AJAX
 * @copyright   Copyright (c) 2018, Magnigenie
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Checks whether AJAX is enabled.
 *
 * This will be deprecated soon in favor of rpress_is_ajax_disabled()
 *
 * @since 1.0
 * @return bool True when RPRESS AJAX is enabled (for the cart), false otherwise.
 */
function rpress_is_ajax_enabled() {
	$retval = ! rpress_is_ajax_disabled();
	return apply_filters( 'rpress_is_ajax_enabled', $retval );
}

/**
 * Checks whether AJAX is disabled.
 *
 * @since  1.0.0
 * @since 1.0 Setting to disable AJAX was removed
 * @return bool True when RPRESS AJAX is disabled (for the cart), false otherwise.
 */
function rpress_is_ajax_disabled() {
	return apply_filters( 'rpress_is_ajax_disabled', false );
}

/**
 * Check if AJAX works as expected
 *
 * @since  1.0.0
 * @return bool True if AJAX works, false otherwise
 */
function rpress_test_ajax_works() {

	// Check if the Airplane Mode plugin is installed
	if ( class_exists( 'Airplane_Mode_Core' ) ) {

		$airplane = Airplane_Mode_Core::getInstance();

		if ( method_exists( $airplane, 'enabled' ) ) {

			if ( $airplane->enabled() ) {
				return true;
			}

		} else {

			if ( $airplane->check_status() == 'on' ) {
				return true;
			}
		}
	}

	add_filter( 'block_local_requests', '__return_false' );

	if ( get_transient( '_rpress_ajax_works' ) ) {
		return true;
	}

	$params = array(
		'sslverify'  => false,
		'timeout'    => 30,
		'body'       => array(
			'action' => 'rpress_test_ajax'
		)
	);

	$ajax  = wp_remote_post( rpress_get_ajax_url(), $params );
	$works = true;

	if ( is_wp_error( $ajax ) ) {

		$works = false;

	} else {

		if( empty( $ajax['response'] ) ) {
			$works = false;
		}

		if( empty( $ajax['response']['code'] ) || 200 !== (int) $ajax['response']['code'] ) {
			$works = false;
		}

		if( empty( $ajax['response']['message'] ) || 'OK' !== $ajax['response']['message'] ) {
			$works = false;
		}

		if( ! isset( $ajax['body'] ) || 0 !== (int) $ajax['body'] ) {
			$works = false;
		}

	}

	if ( $works ) {
		set_transient( '_rpress_ajax_works', '1', DAY_IN_SECONDS );
	}

	return $works;
}

/**
 * Get AJAX URL
 *
 * @since 1.0
 * @return string URL to the AJAX file to call during AJAX requests.
*/
function rpress_get_ajax_url() {
	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$current_url = rpress_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'rpress_ajax_url', $ajax_url );
}

/**
 * Get food items list .
 *
 * @since 1.0
 * @return void
 */
function get_fooditem_lists( $fooditem_id, $cart_key = '' ) {

  // $addons = wp_get_post_terms( $fooditem_id, 'addon_category' );
  // if ( $addons ) {

  //   $addon_ids = $child_ids = array();

  //   foreach( $addons as $addon ) {
  //     if( $addon->parent != 0 ) {
  //       $child_ids[] = $addon->term_id;
  //       if ( !in_array( $addon->parent, $addon_ids ) ) {
  //         $addon_ids[] = $addon->parent;
  //       }
  //     }
  //   }
  // }

  $addons = get_post_meta( $fooditem_id, '_addon_items', true );
  $chosen_addons = array();
  $price_id = 0;
  $addon_ids = $child_ids = array();
  if( $addons ) {
    foreach ($addons as $addon ) {
      if( ! empty( $addon['category'] ) ) {
        array_push($addon_ids, $addon['category'] );
      }
      if( is_array( $addon['items'] ) ) {
        $child_ids = array_merge( $child_ids, $addon['items'] );
      }
    }
  }

  // if( $cart_key !== '' ) {         // Showed Ajax Error as per Nirmal
  // if( !empty( $cart_key ) ) {      // Did work but had issue somewhere else
  // if( !is_null( $cart_key ) ) {    // Did work but had issue somewhere else
  // if( is_int( $cart_key ) ) {      // Did work but had issue somewhere else

  if( $cart_key !== '' ) {

    $cart_contents = rpress_get_cart_contents();
    $cart_contents = $cart_contents[$cart_key];
    $price_id      = isset($cart_contents['price_id']) ? $cart_contents['price_id'] : 0;

    if( !empty( $cart_contents['addon_items'] ) ) {
      foreach( $cart_contents['addon_items'] as $key => $val ) {
        array_push( $chosen_addons, $val['addon_id'] );
      }
    }
  }

  ob_start();

  if ( !empty( $fooditem_id ) && rpress_has_variable_prices( $fooditem_id ) ) {

    $prices = rpress_get_variable_prices( $fooditem_id );

    if ( is_array( $prices ) && !empty( $prices ) ) {

      $variable_price_label = get_post_meta( $fooditem_id, 'rpress_variable_price_label', true );
      $variable_price_label = !empty( $variable_price_label ) ? $variable_price_label : esc_html( 'Price Options', 'restropress' );
      $variable_price_heading = apply_filters( 'rp_variable_price_heading', $variable_price_label ); ?>

      <h6 class="rpress-addon-category"><?php echo $variable_price_heading; ?></h6>

      <div class="rp-variable-price-wrapper">

      <?php
      foreach( $prices as $k => $price ) {

        $price_option = $price['name'];
        $is_first = ( $k == $price_id ) ? 'checked' : '';
        $price_option_slug = sanitize_title( $price['name'] );
        $price_option_amount = rpress_currency_filter( rpress_format_amount( $price['amount'] ) ); ?>

        <div class="food-item-list">
          <label for="<?php echo $price_option_slug; ?>" class="radio-container">
            <input type="radio" name="price_options" id="<?php echo $price_option_slug; ?>" data-value="<?php echo $price_option_slug . '|1|' . $price['amount'] . '|radio'; ?>" value="<?php echo $k; ?>" <?php echo $is_first; ?> class="rp-variable-price-option" ><?php echo $price_option; ?>
            <span class="control__indicator"></span>
          </label>

          <span class="cat_price"><?php echo $price_option_amount; ?></span>
        </div>
      <?php } ?>
      </div>
    <?php }
  }

  if( isset($addon_ids) && is_array( $addon_ids ) && !empty( $addon_ids ) ) {
    // Sort addons cats
    $addon_items_list = array();
    foreach( $addon_ids as $parent ) {
      $addon_items = get_term_by( 'id', $parent, 'addon_category' );
      array_push($addon_items_list, array(
        'parent' => $parent,
        'addon_items' => $addon_items
      ));
    }
    usort($addon_items_list, function($a, $b)
    {
      return strcmp($a['addon_items']->slug, $b['addon_items']->slug);
    });

    foreach( $addon_items_list as $addon_items_ele ) {
      $parent = $addon_items_ele['parent'];
      $addon_items = $addon_items_ele['addon_items'];
      $addon_name = $addon_items->name;
      $addon_slug = $addon_items->slug;
      $term_meta = get_option( "taxonomy_term_$parent" );
      $use_addon_like =  isset($term_meta['use_it_like']) ? $term_meta['use_it_like'] : 'checkbox';
      $addon_status = $term_meta['status'];
      $addon_display = $addon_status != 'inactive';

      $child_data_list = array();
      if ($addon_display) {
        $addon_category_args = array( 'taxonomy' => 'addon_category', 'parent' => $addon_items->term_id, 'include' => $child_ids );
        $child_addons = get_terms( apply_filters( 'rp_addon_category', $addon_category_args ) );
        if ( $child_addons ) {
          $child_addons = wp_list_pluck( $child_addons, 'term_id' );
        }
        if( is_array( $child_addons ) && !empty( $child_addons ) ) {
          // Sort addons          
          foreach($child_addons as $child_addon ) {
            $child_data = get_term_by( 'id', $child_addon, 'addon_category' );
            $child_addon_status = rpress_get_addon_data( $child_data->term_id, 'status' );
            if ($child_addon_status != 'inactive') {
              array_push($child_data_list, array(
                'child_addon' => $child_addon,
                'child_data' => $child_data
              ));
            }
          }
          usort($child_data_list, function($a, $b)
          {
            return strcmp($a['child_data']->slug, $b['child_data']->slug);
          });
          $addon_display = is_array( $child_data_list ) && !empty( $child_data_list );
        }
      }

      if ($addon_display) {
  ?>

  <h6 class="rpress-addon-category">
    <?php echo $addon_name; ?>
    <?php if ($use_addon_like == 'radio') { ?>
      <a href="javascript:clearRadioGroup('<?php echo str_replace("'", "\'", $addon_name); ?>')" class="rpress-clear-addon" style="float:right;color:#d4413c">löschen</a>
    <?php } ?>
  </h6>

  <script>
    function clearRadioGroup(GroupName)
    {
      var $form = null;
      var ele = document.getElementsByName(GroupName);
      for(var i=0;i<ele.length;i++) {
        if (ele[i].classList && ele[i].classList.contains('radio-input')) {
          ele[i].checked = false;
          $form = jQuery(ele[i]).closest('form');
        }
      }
      if ($form != null) {
        var formId = $form.attr('id');
        update_modal_live_price( formId );
      }
    }
  </script>

  <?php
    foreach( $child_data_list as $child_data_ele ) {
      $child_addon = $child_data_ele['child_addon'];
      $child_data = $child_data_ele['child_data'];
      $child_addon_slug = $child_data->slug;
      $child_addon_name = $child_data->name;
      $child_addon_id   = $child_data->term_id;
      $child_addon_price = rpress_get_addon_data( $child_data->term_id, 'price' );
      $child_addon_type_name = ( $use_addon_like == 'radio' ) ? $addon_name : $child_addon_name; ?>

      <?php if ( is_array( $chosen_addons ) ) : ?>
      <div class="food-item-list">
        <label for="<?php echo $child_addon_slug; ?>" class="<?php echo $use_addon_like; ?>-container">
          <?php $is_selected = in_array( $child_addon_id, $chosen_addons ) ?  'checked' : ''; ?>
          <input data-type="<?php echo $use_addon_like;?>" type="<?php echo $use_addon_like; ?>" name="<?php echo $child_addon_type_name; ?>" id="<?php echo $child_addon_slug; ?>" value="<?php echo $child_addon . '|1|' . $child_addon_price . '|' . $use_addon_like; ?>" <?php echo $is_selected; ?> class="<?php echo $use_addon_like; ?>-input">
          <span><?php echo $child_addon_name; ?></span>
          <span class="control__indicator"></span>
        </label>

        <?php if( $child_addon_price > 0 ) : ?>
          <span class="cat_price">&nbsp;+&nbsp;<?php echo rpress_currency_filter( rpress_format_amount( $child_addon_price ) ); ?>
          </span>
				<?php endif; ?>
      </div>

    <?php endif;
        }
      }
    }
  }
  return ob_get_clean();
}

/**
 * Get addon items for a specific Fooditem
 *
 * @since 1.0
 * @return void
 */
function get_addon_items_by_fooditem( $fooditem_id ) {

  if ( empty( $fooditem_id ) ) {
    return;
  }

  // $addons  = wp_get_post_terms( $fooditem_id, 'addon_category' );
  // $addon_ids = $child_ids = array();

  // foreach( $addons as $addon ) {
  //   if( $addon->parent == 0 ) {
  //     $addon_ids[] = $addon->term_id;
  //   }
  //   else {
  //     $child_ids[] = $addon->term_id;
  //   }
  // }

  $addons = get_post_meta( $fooditem_id, '_addon_items', true );
  $addon_ids = $child_ids = array();

  if( $addons ) {
    foreach ($addons as $addon ) {
      if( ! empty( $addon['category'] ) ) {
        array_push($addon_ids, $addon['category'] );
      }
      if( is_array( $addon['items'] ) ) {
        $child_ids = array_merge( $child_ids, $addon['items'] );
      }
    }
  }

  if( is_array( $addon_ids ) && !empty( $addon_ids ) ) {

    foreach( $addon_ids as $parent ) {

      $addon_items = get_term_by( 'id', $parent, 'addon_category' );
      $addon_category_args = array( 'taxonomy' => 'addon_category', 'parent' => $addon_items->term_id );
      $child_addons = get_terms( apply_filters( 'rp_addon_category', $addon_category_args ) );

      if ( $child_addons ) {
        $child_addons = wp_list_pluck( $child_addons, 'term_id' );
      }

      if( is_array( $child_addons ) && !empty( $child_addons ) ) {

        foreach( $child_addons as $child_addon ) {
          $child_data = get_term_by( 'id', $child_addon, 'addon_category' );
          $child_addon_slug = $child_data->slug;
          $child_addon_name = $child_data->name;
          $child_addon_price = rpress_get_addon_data( $child_data->term_id, 'price' );
          $addon_price = html_entity_decode( rpress_currency_filter( rpress_format_amount( $child_addon_price ) ) );
          ?>
          <option data-price="" data-id="" value=" <?php echo $child_addon_name .' | '. $child_addon_price; ?> "><?php echo $child_addon_name . ' (' . $addon_price . ') '; ?> </option>
          <?php
        }
      }
    }
  }
}