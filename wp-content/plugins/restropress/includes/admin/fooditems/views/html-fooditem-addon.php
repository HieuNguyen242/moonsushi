<?php
/**
 * Food Item Addons data panel.
 *
 * @package RestroPress/Admin
 */

defined( 'ABSPATH' ) || exit;
$count 	  = !empty( $current ) ? $current : time();
$post_id  = get_the_ID();
$addons   = get_post_meta( $post_id, '_addon_items', true );

if ( is_array( $addons ) && !empty( $addons ) ) :
  foreach( $addons as $key => $addon_item ) :
    $addon_id = isset( $addon_item['category'] ) ? $addon_item['category'] : '';
    ?>
    <!-- Addon category form starts -->
    <div class="rp-addon rp-metabox">

      <h3>
        <div class="handlediv" title="<?php esc_html_e( 'Click to toggle', 'restropress' ); ?>"></div>
      	<a href="#" class="remove_row delete"><?php esc_html_e( 'Remove', 'restropress' ); ?></a>

        <div class="tips sort" data-tip="<?php esc_html_e( 'Drag Drop to reorder the addon categories.', 'restropress' );?>"></div>
      	 <strong class="addon_name">
      			<?php esc_html_e( 'Select Addon Category', 'restropress' ); ?>
      		</strong>
      	</h3>

      	<div class="rp-metabox-content">
      		<div class="rp-col-6 addon-category">
      			<select name="addons[<?php echo $key; ?>][category]" class=" rp-input rp-addon-lists " data-row-id="<?php echo $key; ?>">

              <?php if ( $addon_id == '' ) : ?>
                <option value="">
                  <?php esc_html_e( 'Select Addon Category', 'restropress' ); ?>
                </option>
              <?php endif; ?>

      				<?php
      					foreach ( $addon_categories as $category ){
      						echo '<option data-name="'.$category->name.'" '.selected( $addon_item['category'], $category->term_id, false ).' value="' . $category->term_id .'">' .$category->name .'</option>';
      					}
      				?>
      			</select>
      			<button type="button" class="button load-addon">
      				<?php esc_html_e( 'Add', 'restropress' ); ?>
      			</button>
      		</div>
      		<div class="rp-col-6 addon-items">
          <?php
            $get_addons = rpress_get_addons( $addon_id );
            if ( !empty( $addon_id ) && is_array( $get_addons ) && !empty( $get_addons ) ) :
          ?>
            <ul>
            <?php
              foreach( $get_addons as $get_addon ) :
                $addon_item_id = $get_addon->term_id;
                $addon_item_name = $get_addon->name;
                $addon_slug = $get_addon->slug;
                $addon_price = rpress_get_addon_data( $addon_item_id, 'price' );
                $addon_price = !empty( $addon_price ) ? rpress_currency_filter( rpress_format_amount( $addon_price ) ) : '0.00';

                $selected = false;

                if ( isset( $addon_item['items'] ) ) {
                  if ( in_array( $addon_item_id, $addon_item['items'] ) ) {
                    $selected = true;
                  }
                }

            ?>
              <li class="rp-child-addon">
                <?php if ( $selected ) : ?>
                <input type="checkbox" value="<?php echo $addon_item_id; ?>" id="<?php echo $addon_slug; ?>" name="addons[<?php echo $key; ?>][items][]" class="rp-checkbox" checked >
                <?php else : ?>
                <input type="checkbox" value="<?php echo $addon_item_id; ?>" id="<?php echo $addon_slug; ?>" name="addons[<?php echo $key; ?>][items][]" class="rp-checkbox rpress" >
                <?php endif; ?>

                <label for="<?php echo $addon_slug; ?>">
                  <?php echo $addon_item_name; ?>
                </label>
                <span class="rp-addon-price">(<?php echo $addon_price; ?>)</span>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php
              else :
            ?>
            <div class="rp-addon-msg">
              <?php esc_html_e( 'Please select a addon category first!', 'restropress' ); ?>
            </div>
            <?php endif; ?>
      		</div>
      	</div>
      </div>
      <!-- Addon category form ends -->
      <?php
      //endforeach;
    //endif;
  endforeach;
else :
?>
<!-- Addon category form starts -->
<div class="rp-addon rp-metabox">
  <h3>
    <div class="handlediv" title="<?php esc_html_e( 'Click to toggle', 'restropress' ); ?>"></div>
    <a href="#" class="remove_row delete"><?php esc_html_e( 'Remove', 'restropress' ); ?></a>
    <div class="tips sort" data-tip="<?php esc_html_e( 'Drag Drop to reorder the addon categories.', 'restropress' );?>"></div>
    <strong class="addon_name">
      <?php esc_html_e( 'Select Addon Category', 'restropress' ); ?>
    </strong>
  </h3>
  <div class="rp-metabox-content">
    <div class="rp-col-6 addon-category">
      <select name="addons[<?php echo $count; ?>][category]" class="rp-input rp-addon-items-list" data-row-id="<?php echo $count; ?>">
        <option value="">
          <?php esc_html_e( 'Select Addon Category', 'restropress' ); ?>
        </option>
        <?php
          foreach ( $addon_categories as $category ) :
            echo '<option value="' . $category->term_id .'">' . $category->name .'</option>';
          endforeach;
        ?>
      </select>
      <button type="button" class="button load-addon">
        <?php esc_html_e( 'Add', 'restropress' ); ?>
      </button>
    </div>
    <div class="rp-col-6 addon-items">
      <div class="rp-addon-msg">
        <?php esc_html_e( 'Please select a addon category first!', 'restropress' ); ?>
      </div>
    </div>
  </div>
</div>
<!-- Addon category form ends -->
<?php endif; ?>