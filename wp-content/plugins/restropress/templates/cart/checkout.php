<?php
	$color = rpress_get_option( 'checkout_color', 'red' );
	$cart_quantity = rpress_get_cart_quantity();
	$display       = $cart_quantity > 0 ? '' : ' style="display:none;"';
?>
<?php if ( rpress_use_taxes() ) : ?>
<li class="cart_item rpress-cart-meta rpress_subtotal"><?php echo __( 'Subtotal:', 'restropress' ). " <span class='subtotal'>" . rpress_currency_filter( rpress_format_amount( rpress_get_cart_subtotal() ) ); ?></span></li>
<li class="cart_item rpress-cart-meta rpress_cart_tax"><?php echo rpress_get_tax_name(); ?> <span class="cart-tax"><?php echo rpress_currency_filter( rpress_format_amount( rpress_get_cart_tax() ) ); ?></span></li>
<?php endif; ?>

<?php do_action( 'rpress_cart_line_item' ); ?>


<li class="cart_item rpress-cart-meta rpress_total"><?php _e( 'Total (', 'restropress' ); ?><span class="rpress-cart-quantity" <?php echo $display; ?> ><?php echo $cart_quantity; ?></span><?php _e( ' Items)', 'restropress' ); ?><span class="cart-total <?php echo $color; ?>"><?php echo rpress_currency_filter( rpress_format_amount( rpress_get_cart_total() ) ); ?></span></li>

<!-- Service Type and Service Time -->
<?php if ( ( isset( $_COOKIE['service_type'] ) && !empty( $_COOKIE['service_type'] ) ) || ( isset( $_COOKIE['service_time'] ) && !empty( $_COOKIE['service_time'] ) ) ) : ?>
  <li class="delivery-items-options">
    <?php echo get_delivery_options( true ); ?>
  </li>
<?php endif; ?>

<li class="cart_item rpress_checkout <?php echo $color; ?>">
  <a data-url="<?php echo rpress_get_checkout_uri(); ?>" href="#"> <?php
    $confirm_order_text = apply_filters( 'rp_confirm_order_text', _e( 'Checkout', 'restropress' ) );
    echo $confirm_order_text; ?></a>
</li>