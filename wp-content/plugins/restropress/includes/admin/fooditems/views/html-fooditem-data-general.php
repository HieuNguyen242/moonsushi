<?php
/**
 * Food Item general data panel.
 *
 * @package RestroPress/Admin
 */

defined( 'ABSPATH' ) || exit;
$has_variable_prices = $fooditem_object->has_variable_prices();
?>
<div id="general_fooditem_data" class="panel restropress_options_panel rp-metaboxes-wrapper">
	<div class="rp-metabox-container">
		<div class="toolbar toolbar-top">
			<span class="rp-toolbar-title">
				<?php esc_html_e( 'Food Item Pricing', 'restropress' ); ?>
			</span>
		</div>
		<div class="options_group pricing">
			<div class="rp-tab-content">
				
				<?php
				rpress_text_input(
					array(
						'id'        => 'rpress_price',
						'value'     => $fooditem_object->get_price(),
						'label'     => __( 'Price', 'restropress' ) . ' (' . rpress_currency_symbol() . ')',
						'wrapper_class'		=> $has_variable_prices ? 'hidden' : '',
						'data_type' => 'price',
					)
				);

				//Variable Pricing
				rpress_checkbox(
					array(
						'id'          => '_variable_pricing',
						'label'       => __( 'Variable pricing', 'restropress' ),
						'description' => __( 'Check this box if the food has multiple options and you want to specify price for different options.', 'restropress' ),
						'value'       => $has_variable_prices ? 'yes' : 'no',
					)
				);

				rpress_text_input(
					array(
						'id' => 'rpress_variable_price_label',
						'value' => get_post_meta( $fooditem_object->ID, 'rpress_variable_price_label', true),
						'label' => __( 'Price Label', 'restropress' ),
						'wrapper_class' => $has_variable_prices ? 'rp-variable-prices' : 'rp-variable-prices hidden',
					)
				);

				?>

				<div class="rp-metaboxes rp-variable-prices <?php echo !$has_variable_prices ? 'hidden' : ''; ?>">
					<?php  
					if( $has_variable_prices ) :
						$prices = $fooditem_object->get_prices();
						$current = 0;
						foreach ( $prices as $price ) :  ?>
							<?php include 'html-fooditem-variable-price.php'; ?>
						<?php $current++; endforeach; ?>
					<?php else: ?>
						<?php include 'html-fooditem-variable-price.php'; ?>
					<?php endif; ?>
					<button type="button" class="button button-primary add-new-price">
						<?php esc_html_e( '+ Add New', 'restropress' ); ?>
					</button>
				</div>

			</div>
			<?php do_action( 'rpress_fooditem_pricing' ); ?>
		</div>
	</div>

	<?php do_action( 'rpress_fooditem_options_general_data' ); ?>
</div>
