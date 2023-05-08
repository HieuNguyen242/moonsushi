<div class="rpress-price-holder">
	<span class="price">
	
    <?php
  	
    global $post;
    global $rpress_options;

    $price = get_post_meta( $post->ID,'rpress_price', true ) ;
    $variable_pricing = rpress_has_variable_prices( $post->ID );

    if ( $variable_pricing ) {
      $price_range = rpress_price_range( $post->ID );
      echo $price_range;
    } else {
      echo rpress_currency_filter( rpress_format_amount( $price ) );
    }

    ?>

	</span>
	
  <div class="rpress_fooditem_buy_button">
		<?php echo rpress_get_purchase_link( array( 'fooditem_id' => get_the_ID() ) ); ?>
	</div>

</div>