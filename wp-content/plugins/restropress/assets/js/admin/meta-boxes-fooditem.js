/* Food Item metabox scripts */
jQuery( function( $ ) {

	//show the toast message
	function rpress_toast(heading, message, type){
		$.toast({
		  heading : heading,
		  text    : message,
		  showHideTransition: 'slide',
		  icon    : type,
		  position: { top: '36px', right: '0px' },
		  stack   : false
		});
	}

	$('.rp_add_category').click(function(){
		$('.rp-add-category').toggle();
	});

	$('#_variable_pricing').change(function(){
		if( $(this).is(':checked') ){
			$('.rp-variable-prices').slideDown();
			$('.rpress_price_field').slideUp();
		}
		else{
			$('.rp-variable-prices').slideUp();
			$('.rpress_price_field').slideDown();
		}
	});

  	//Remove Row
  	$( 'body' ).on( 'click', '.remove_row.delete', function(e) {
    	e.preventDefault();
    	if( window.confirm( fooditem_meta_boxes.delete_pricing ) ) {
      		$( this ).parents( '.rp-metabox' ).remove();
    	}
    });

  	$( 'body' ).on( 'click', '.remove.rp-addon-cat', function(e) {

    	e.preventDefault();
    	if( window.confirm( fooditem_meta_boxes.delete_new_category ) ) {
      		$( this ).parents( '.rp-addon.create-new-addon' ).remove();
    	}
  	});

  	//Addon Category Name
  	$( 'body' ).on( 'input keypress', '.rp-input.addon-category-name', function(event) {

    	var _self = $( this );
    	var category_name = _self.val();

    	if( event.currentTarget.value.length >= 1 ) {
      		if( category_name !== '' ) {
        		_self.parents( '.rp-metabox.create-new-addon' ).find( '.addon_category_name' ).text( category_name );
      		}
    	} else {
      		_self.parents( '.rp-metabox.create-new-addon' ).find( '.addon_category_name' ).text( 'Addon category Name' );
      	}
  	});

  	//Variable Price
  	$( '.rp-input-variable-name' ).on( 'input keypress', function(event) {

    	var _self = $( this );
    	var option_name = _self.val();

    	if( event.currentTarget.value.length >= 1 ) {
      		if( option_name !== '' ) {
        		_self.parents( '.rp-metabox.variable-price' ).find( '.price_name' ).text( option_name );
      		}
    	} else {
      		_self.parents( '.rp-metabox.variable-price' ).find( '.price_name' ).text( 'Option Name' );
    	}
  	});

  	// Addon multiple rows
  	$( 'body' ).on( 'click', '.add-new-addon.add-addon-multiple-item', function(e) {

    	e.preventDefault();
    	var SeletedRow = $(this).parents('.rp-metabox-content').find('tr.addon-items-row');
    	var ParentRow = SeletedRow.first().clone(true);

    	ParentRow.find( 'input' ).each( function(){
      		$(this).val('');
    	});
    	var LastRow = SeletedRow.last();
    	$( ParentRow ).insertAfter( LastRow );
  	});

	// Add rows.
	$( 'button.add-new-price' ).on( 'click', function() {
		var size     = $( '.rp-variable-prices .variable-price' ).length;
		var $wrapper = $( this ).closest( '.pricing' );
		var $prices  = $wrapper.find( '.rp-variable-prices' );
		var data     = {
			action   : 'rpress_add_price',
			i        : size,
			security : fooditem_meta_boxes.add_price_nonce
		};

		$wrapper.block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.post( fooditem_meta_boxes.ajax_url, data, function( response ) {
			$prices.find('.add-new-price').before( response );
			$wrapper.unblock();
			$( document.body ).trigger( 'rpress_added_price' );
		});
		return false;
	});

	// Add new category.
	$( 'button.add-category' ).on( 'click', function() {

        var $wrapper  = $( this ).closest( '.rp-category' );
		var name      = $wrapper.find('#rp-category-name').val();
		var parent    = $wrapper.find('#rp-parent-category').val();

		if( name == '' ){
		  $( this ).parent().find('#rp-category-name').focus();
			return;
		}
		var data = {
			action   : 'rpress_add_category',
			name     : name,
			parent   : parent,
			security : fooditem_meta_boxes.add_category_nonce
		};

		$wrapper.block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.post( fooditem_meta_boxes.ajax_url, data, function( response ) {

			if( undefined !== response.term_id ){
				// Create a DOM Option and pre-select by default
				var newOption = new Option( name, response.term_id, true, true );
				$('.rp-category-select,#rp-parent-category').append( newOption ).trigger('change');
				$wrapper.find('#rp-category-name,#rp-parent-category').val('').trigger('change');
			}

			$wrapper.unblock();

			$( document.body ).trigger( 'rpress_added_category' );
		});

		return false;
	});

	$( 'button.add-new-addon,button.create-addon' ).on( 'click', function(e) {

        var isCreate = $( e.target ).hasClass( 'create-addon' );
		var size     = Math.round( (new Date()).getTime() / 1000 );
		var $wrapper = $( this ).closest( '#addons_fooditem_data' );
		var $addons  = $wrapper.find( '.rp-addons' );
		var data     = {
			action   : 'rpress_add_addon',
			i        : size,
			iscreate : isCreate,
			security : fooditem_meta_boxes.add_addon_nonce
		};

		$wrapper.block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.post( fooditem_meta_boxes.ajax_url, data, function( response ) {
			$addons.append( response );
			$wrapper.unblock();
			$( document.body ).trigger( 'rpress_added_addon' );
		});
		return false;
	});

	$( '#addons_fooditem_data' ).on( 'click', 'button.load-addon', function(e) {

	    var _self    = $(this);
	  	var parent   = _self.parent( '.addon-category' ).find( 'select' ).val();
	    var selected_cat = _self.parent( '.addon-category' ).find( ':selected' ).data( 'name' );
	    var cats_arr = [];

	    if( parent == '' ) {
	    	rpress_toast('Error',fooditem_meta_boxes.select_addon_category, 'error');
	    	return false;
	    }
	    else if( $( '.addon-category select option:checked[value="' + parent +'"]' ).length > 1 ){
	    	rpress_toast('Error',fooditem_meta_boxes.addon_category_already_selected, 'error');
	    	return false;
	    }

	  	var size     = $(this).parents('.addon-category').find('select').attr('data-row-id');
	  	var $wrapper = _self.closest( '.rp-metabox-content' );
	  	var $addons  = $wrapper.find( '.addon-items' );
		var data   	 = {
			action  :  'rpress_load_addon_child',
			parent  :  parent,
			i       :  size,
			security:  fooditem_meta_boxes.load_addon_nonce
		};

		$wrapper.block({
			message: null,
			overlayCSS: {
			  background: '#fff',
			  opacity: 0.6
			}
		});

		$.post( fooditem_meta_boxes.ajax_url, data, function( response ) {
			$addons.html( response );
			$wrapper.unblock();
			$( document.body ).trigger( 'rpress_loaded_addon' );
		});

		return false;

	});
});