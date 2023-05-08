<?php
/**
 * This template is used to display the RestroPress cart widget.
 */
$cart_items    	= rpress_get_cart_contents();
$cart_quantity 	= rpress_get_cart_quantity();
$display       	= $cart_quantity > 0 ? '' : 'style="display:none;"';
$color 			= rpress_get_option( 'checkout_color', 'red' );
?>

<?php do_action( 'rpress_before_cart' ); ?>


<div class="rp-col-lg-4 rp-col-md-4 rp-col-sm-12 rp-col-xs-12 pull-right rpress-sidebar-cart item-cart sticky-sidebar">
	<div class="rpress-mobile-cart-icons rpress-bg-<?php echo $color; ?>">
	  <i class='fa fa-shopping-cart' aria-hidden='true'></i>
	  <span class='rpress-cart-badge rpress-cart-quantity'>
	    <?php echo rpress_get_cart_quantity(); ?>
	  </span>
	</div>
	<div class='rpress-sidebar-main-wrap'>
		<i class='fa fa-times close-cart-ic rpress-bg-<?php echo $color; ?>' aria-hidden='true'></i>
	    <div class="rpress-sidebar-cart-wrap">
	    	<div class="rpress item-order">
				<h6><?php echo apply_filters('rpress_cart_title', __( 'Your Order', 'restropress' ) ); ?></h6>
				<a class="rpress-clear-cart <?php echo $color; ?>" href="#" <?php echo $display ?> ><?php echo __('Clear Order', 'restropress') ?> </a>
			</div>
			<ul class="rpress-cart">

				<?php if( $cart_items ) : ?>
					<?php foreach( $cart_items as $key => $item ) : ?>
						<?php echo rpress_get_cart_item_template( $key, $item, false, $data_key = '' ); ?>
					<?php endforeach; ?>
					<?php rpress_get_template_part( 'cart/checkout' ); ?>
				<?php else : ?>
					<?php rpress_get_template_part( 'cart/empty' ); ?>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>
<?php do_action( 'rpress_after_cart' ); ?>
