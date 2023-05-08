/* Get RestroPress Cookie */
function rp_getCookie( cname ) {
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for( var i=0; i<ca.length; i++ ) {
    var c = ca[i];
    while ( c.charAt(0)==' ' ) c = c.substring(1);
    if ( c.indexOf(name) != -1 ) return c.substring( name.length,c.length );
  }
  return "";
}

/* Set RestroPress Cookie */
function rp_setCookie( cname, cvalue, ex_time ) {
  var d = new Date();
  d.setTime( d.getTime() + ( ex_time*60*1000 ) );
  var expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + "; " + expires + ";path=/";
}

/* Get RestroPress Storage Data */
function rp_get_storage_data() {

  var serviceType = rp_getCookie( 'service_type' );
  var serviceTime = rp_getCookie( 'service_time' );

  if( typeof serviceType == undefined || serviceType == '' ) {
    return false;
  } else {
    return true;
  }
}

/* Calculate Live Price On Click */
function update_modal_live_price( fooditem_container ) {

  var single_price = parseFloat(jQuery( '#rpressModal .cart-item-price').attr('data-price'));
  var quantity    = parseInt( jQuery('input[name=quantity]').val() );

  /* Act on the variations */
  jQuery('#' + fooditem_container + ' .rp-variable-price-wrapper .food-item-list').each(function () {

    var element = jQuery(this).find('input');

    if ( element.is(':checked') ) {

      var attrs = element.attr('data-value');
      var attrs_arr = attrs.split('|');
      var price = attrs_arr[2];

      single_price = parseFloat(price);
    }
  });

  /* Act on the addons */
  jQuery('#' + fooditem_container + ' > .food-item-list').each(function () {

    var element = jQuery(this).find('input');

    // element.prop("type") == 'radio'
    if ( element.is(':checked') ) {

      var attrs = element.val();
      var attrs_arr = attrs.split('|');
      var price = attrs_arr[2];

      if( price != '' ) {
        single_price = parseFloat(single_price) + parseFloat(price);
      }
    }
  });

  /* Updating as per current quantity */
  total_price = single_price * quantity;

  /* Update the price in Submit Button */
  jQuery( '#rpressModal .cart-item-price').html( rp_scripts.currency_sign + total_price.toFixed(2) );
  jQuery( '#rpressModal .cart-item-price').attr('data-current', single_price.toFixed(2));
}

/* RestroPress Frontend Functions */
jQuery( function($) {

  //Remove loading from modal
  $( '#rpressModal' ).removeClass('loading');

  //Remove service options from modal
  $( '#rpressModal' ).removeClass( 'show-service-options' );
  $( '#rpressModal' ).removeClass( 'minimum-order-notice' );

  $( '#rpressModal' ).on('hidden.bs.modal', function () {
    $( '#rpressModal' ).removeClass( 'show-service-options' );
    $( '#rpressModal' ).removeClass( 'minimum-order-notice' );
  });

  var ServiceType = rp_scripts.service_options;

  if ( ServiceType == 'delivery_and_pickup' ) {
    ServiceType = 'delivery';
  }

  // Add to Cart
  $('.rpress-add-to-cart').click(function(e) {

    e.preventDefault();

    var rp_get_delivery_data = rp_get_storage_data();

    $( '#rpressModal' ).addClass('loading');
    $( '#rpressModal .modal-body' ).html('<span class="rp-loader">' + rp_scripts.please_wait + '</span>');

    $( '#rpressModal' ).removeClass( 'rpress-delivery-options rpress-food-options checkout-error' );
    $( '#rpressModal .qty' ).val('1');
    $( '#rpressModal' ).find( '.cart-action-text' ).html( rp_scripts.add_to_cart  );

    if ( ! rp_get_delivery_data ) {
      var action   = 'rpress_show_delivery_options';
      var security = rp_scripts.service_type_nonce;
      $( '#rpressModal' ).addClass( 'show-service-options' );
    } else {
      var action   = 'rpress_show_products';
      var security = rp_scripts.show_products_nonce;
    }

    var _self          = $(this);
    var fooditem_id    = _self.attr( 'data-fooditem-id' );
    var price          = _self.attr( 'data-price' );
    var variable_price = _self.attr( 'data-variable-price');

    var data = {
      action        : action,
      fooditem_id   : fooditem_id,
      security      : security,
    };

    MicroModal.show('rpressModal');

    $.ajax({
      type      : "POST",
      data      : data,
      dataType  : "json",
      url       : rp_scripts.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success : function( response ) {

        $( '#rpressModal' ).removeClass('loading');
        $( '#rpressModal .modal-title' ).html( response.data.html_title );
        $( '#rpressModal .modal-body' ).html( response.data.html );
        $( '#rpressModal .cart-item-price').html( response.data.price );
        $( '#rpressModal .cart-item-price').attr('data-price', response.data.price_raw);

        if ( $( '.rpress-tabs-wrapper' ).length ) {
          $( '#rpressdeliveryTab > li:first-child > a' )[0].click();
        }

        // Trigger event so themes can refresh other areas.
        $( document.body ).trigger( 'opened_service_options', [ response.data ] );

        $( '#rpressModal' ).find( '.submit-fooditem-button' ).attr( 'data-cart-action', 'add-cart' );
        $( '#rpressModal' ).find( '.cart-action-text' ).html( rp_scripts.add_to_cart  );

        if ( fooditem_id !== '' && price !== '' ) {
          $('#rpressModal').find('.submit-fooditem-button').attr('data-item-id', fooditem_id); //setter
          $('#rpressModal').find('.submit-fooditem-button').attr('data-item-price', price);
          $('#rpressModal').find('.submit-fooditem-button').attr('data-item-qty', 1);
        }

        update_modal_live_price( 'fooditem-details' );
      }

    });
  });

  // Update Cart
  $( '.rpress-sidebar-cart' ).on( 'click', 'a.rpress-edit-from-cart', function(e) {
    e.preventDefault();

    var _self = $(this);
    _self.parents( '.rpress-cart-item' ).addClass( 'edited' );

    var CartItemId = _self.attr( 'data-remove-item' );
    var FoodItemId = _self.attr( 'data-item-id' );
    var FoodItemName = _self.attr( 'data-item-name' );
    var FoodQuantity = _self.parents( '.rpress-cart-item' ).find( '.cart-item-quantity-wrap' ).children( '.rpress-cart-item-qty' ).text();
    var action = 'rpress_edit_cart_fooditem';
    var security = rp_scripts.edit_cart_fooditem_nonce;

    MicroModal.show('rpressModal');

    $( '#rpressModal' ).addClass('loading');
    $( '#rpressModal .modal-body' ).html('<span class="rp-loader">' + rp_scripts.please_wait + '</span>');

    var data   = {
      action         : action,
      cartitem_id    : CartItemId,
      fooditem_id    : FoodItemId,
      fooditem_name  : FoodItemName,
      security       : security,
    };

    if( CartItemId !== '' ) {

      $.ajax({
        type: "POST",
        data: data,
        dataType: "json",
        url: rp_scripts.ajaxurl,
        xhrFields: {
          withCredentials: true
      },
      success: function(response) {

        $( '#rpressModal' ).removeClass('checkout-error');
        $( '#rpressModal' ).removeClass('show-service-options');
        $( '#rpressModal' ).removeClass('loading');
        $( '#rpressModal .modal-title' ).html( response.data.html_title );

        $( '#rpressModal' ).find( ".qty" ).val(FoodQuantity);
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).attr( 'data-item-id', FoodItemId );
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).attr( 'data-cart-key', CartItemId );
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).attr( 'data-cart-action', 'update-cart' );
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).find( '.cart-action-text' ).html( rp_scripts.update_cart );
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).find( '.cart-item-price' ).html( response.data.price );
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).find( '.cart-item-price' ).attr('data-price', response.data.price_raw);
        $( '#rpressModal' ).find( '.submit-fooditem-button' ).attr('data-item-qty', FoodQuantity);
        $( '#rpressModal .modal-body' ).html( response.data.html );

        update_modal_live_price( 'fooditem-update-details' );
      }
    });
    }
  });

  // Add to Cart / Update Cart Button From Popup
  $( document ).on( 'click', '.submit-fooditem-button', function(e) {

    e.preventDefault();

    var self = $(this);
    var cartAction = self.attr( 'data-cart-action' );
    var text  = self.text();

    self.find( '.cart-action-text' ).text( rp_scripts.please_wait );

    if( cartAction == 'add-cart' ) {

      self.addClass('disable_click');

      var Form = $(this).parents('.modal').find('form#fooditem-details');
      var itemId = $(this).attr('data-item-id');
      var action = 'rpress_add_to_cart';
      var itemQty = $(this).attr('data-item-qty');
      var FormData = Form.serializeArray();
      var SpecialInstruction = $(this).parents('.modal').find('textarea.special-instructions').val();

      var data = {
        action        : action,
        fooditem_id   : itemId,
        fooditem_qty  : itemQty,
        special_instruction: SpecialInstruction,
        post_data     : Form.serializeArray(),
        security      : rp_scripts.add_to_cart_nonce
      };

      if( itemId !== '' ) {
        $.ajax({
          type      : "POST",
          data      : data,
          dataType  : "json",
          url       : rp_scripts.ajaxurl,
          xhrFields : {
            withCredentials: true
          },
          success: function(response) {
            if( response ) {

              self.removeClass('disable_click');
              self.find( '.cart-action-text' ).text(text);

              var serviceType = rp_getCookie('service_type');
              var serviceTime = rp_getCookie('service_time');
              var serviceDate = rp_getCookie('delivery_date');

              $('ul.rpress-cart').find('li.cart_item.empty').remove();
              $('ul.rpress-cart').find('li.cart_item.rpress_subtotal').remove();
              $('ul.rpress-cart').find('li.cart_item.cart-sub-total').remove();
              $('ul.rpress-cart').find('li.cart_item.rpress_cart_tax').remove();
              $('ul.rpress-cart').find('li.cart_item.rpress-cart-meta.rpress-delivery-fee').remove();
              $('ul.rpress-cart').find('li.cart_item.rpress-cart-meta.rpress_subtotal').remove();

              $(response.cart_item).insertBefore('ul.rpress-cart li.cart_item.rpress_total');

              if( $('.rpress-cart').find('.rpress-cart-meta.rpress_subtotal').is(':first-child') ) {
                $(this).hide();
              }

              $('.rpress-cart-quantity').show().text(response.cart_quantity);
              $('.cart_item.rpress-cart-meta.rpress_total').find('.cart-total').text(response.total);
              $('.cart_item.rpress-cart-meta.rpress_subtotal').find('.subtotal').text(response.total);
              $('.cart_item.rpress-cart-meta.rpress_total').css('display', 'block');
              $('.cart_item.rpress-cart-meta.rpress_subtotal').css('display', 'block');
              $('.cart_item.rpress_checkout').addClass(rp_scripts.button_color);
              $('.cart_item.rpress_checkout').css('display', 'block');

              if( serviceType !== undefined ) {
                serviceLabel = window.localStorage.getItem('serviceLabel');
                var orderInfo = '<span class="delMethod">'+ serviceLabel + ', ' + serviceDate + '</span>';

                if( serviceTime !== undefined ) {
                  orderInfo += '<span class="delTime">, '+ serviceTime + '</span>';
                }

                $('.delivery-items-options').find('.delivery-opts').html( orderInfo );

                if( $('.delivery-wrap .delivery-change').length == 0 ) {
                  $( "<a href='#' class='delivery-change'>"+ rp_scripts.change_txt +"</a>" ).insertAfter( ".delivery-opts" );
                }
              }

              $('.delivery-items-options').css('display', 'block');

              var subTotal = '<li class="cart_item rpress-cart-meta rpress_subtotal">'+rp_scripts.total_text+'<span class="cart-subtotal">'+response.subtotal+'</span></li>';
              if( response.taxes ) {
                var taxHtml = '<li class="cart_item rpress-cart-meta rpress_cart_tax">'+rp_scripts.estimated_tax+'<span class="cart-tax">'+response.taxes+'</span></li>';
                $(taxHtml).insertBefore('ul.rpress-cart li.cart_item.rpress_total');
                $(subTotal).insertBefore('ul.rpress-cart li.cart_item.rpress_cart_tax');
              }

              if( response.taxes === undefined ) {
                $('ul.rpress-cart').find('.cart_item.rpress-cart-meta.rpress_subtotal').remove();
                var cartLastChild = $('ul.rpress-cart>li.rpress-cart-item:last');
                $(subTotal).insertAfter(cartLastChild);
              }

              $(document.body).trigger('rpress_added_to_cart', [ response ]);
              $('ul.rpress-cart').find('.cart-total').html(response.total);
              $('ul.rpress-cart').find('.cart-subtotal').html(response.subtotal);

              if ( $( 'li.rpress-cart-item' ).length > 0 ){
                $( 'a.rpress-clear-cart' ).show();
              }else {
                $( 'a.rpress-clear-cart' ).hide();
              }

              MicroModal.close('rpressModal');

              self.find( '.cart-action-text' ).text( rp_scripts.add_to_cart );

              $(document.body).trigger('rpress_added_to_cart', [ response ]);
            }
          }
        })
      }
    }

    if ( cartAction == 'update-cart' ) {

      var itemId    = self.attr( 'data-item-id' );
      var itemPrice = self.attr( 'data-item-price' );
      var cartKey   = self.attr( 'data-cart-key' );
      var itemQty   = self.attr( 'data-item-qty' );
      var FormData  = self.parents('.modal').find( '#fooditem-update-details' ).serializeArray();
      var SpecialInstruction = self.parents('.modal').find( 'textarea.special-instructions' ).val();
      var action = 'rpress_update_cart_items';

      var data = {
        action              : action,
        fooditem_id         : itemId,
        fooditem_qty        : itemQty,
        fooditem_cartkey    : cartKey,
        special_instruction : SpecialInstruction,
        post_data           : FormData,
        security            : rp_scripts.update_cart_item_nonce
      };

      if ( itemId !== '' ) {
        $.ajax({
          type     : "POST",
          data     : data,
          dataType : "json",
          url      : rp_scripts.ajaxurl,
          xhrFields : {
            withCredentials: true
          },
          success: function( response ) {

            self.find( '.cart-action-text' ).text(text);

            if ( response ) {

              html = response.cart_item;

              $('ul.rpress-cart').find('li.cart_item.empty').remove();

              $('.rpress-cart >li.rpress-cart-item').each( function( index, item ) {
                $(this).find("[data-cart-item]").attr('data-cart-item', index);
                $(this).attr('data-cart-key', index);
                $(this).attr('data-remove-item', index);
              });

              $( 'ul.rpress-cart' ).find( 'li.edited' ).replaceWith( function() {

                let obj = $(html);
                obj.attr('data-cart-key', response.cart_key);

                obj.find("a.rpress-edit-from-cart").attr("data-cart-item", response.cart_key);
                obj.find("a.rpress-edit-from-cart").attr("data-remove-item", response.cart_key);

                obj.find("a.rpress_remove_from_cart").attr("data-cart-item", response.cart_key);
                obj.find("a.rpress_remove_from_cart").attr("data-remove-item", response.cart_key);

                return obj;
              } );

              $('ul.rpress-cart').find('.cart-total').html(response.total);
              $('ul.rpress-cart').find('.cart-subtotal').html(response.subtotal);
              $('ul.rpress-cart').find('.cart-tax').html(response.tax);

              $(document.body).trigger('rpress_items_updated', [ response ]);

              MicroModal.close('rpressModal');
            }
          }
        });
      }
    }
  });

  // Add Service Date and Time
  $( 'body' ).on( 'click', '.rpress-delivery-opt-update', function( e ) {
    e.preventDefault();

    var _self = $(this);
    var foodItemId = _self.attr('data-food-id');

    if ( $('.rpress-tabs-wrapper').find('.nav-item.active a').length > 0 ) {
      var serviceType   = $('.rpress-tabs-wrapper').find('.nav-item.active a').attr('data-service-type');
      var serviceLabel  = $('.rpress-tabs-wrapper').find('.nav-item.active a').text().trim();
      //Store the service label for later use
      window.localStorage.setItem( 'serviceLabel', serviceLabel );
    }

    var serviceTime = _self.parents('.rpress-tabs-wrapper').find('.delivery-settings-wrapper.active .rpress-hrs').val();
    var serviceDate = _self.parents('.rpress-tabs-wrapper').find('.delivery-settings-wrapper.active .rpress_get_delivery_dates').val();

    if ( serviceTime === undefined && ( rpress_scripts.pickup_time_enabled == 1 && serviceType == 'pickup'  || rpress_scripts.delivery_time_enabled == 1 && serviceType == 'delivery' )) {
      _self.parents('.rpress-delivery-wrap').find('.rpress-errors-wrap').text('Please select time for ' + serviceLabel);
      _self.parents('.rpress-delivery-wrap').find('.rpress-errors-wrap').removeClass('disabled').addClass('enable');
      return false;
    }

    var sDate = serviceDate === undefined ? rpress_scripts.current_date : serviceDate;

    _self.parents('.rpress-delivery-wrap').find('.rpress-errors-wrap').removeClass('enable').addClass('disabled');
    _self.text(rpress_scripts.please_wait);

    var action = 'rpress_check_service_slot';
    var data = {
        action: action,
        serviceType: serviceType,
        serviceTime: serviceTime,
        service_date: sDate
    };

    $.ajax({
      type: "POST",
      data: data,
      dataType: "json",
      url: rpress_scripts.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success: function( response ) {

        if ( response.status == 'error' ) {

          _self.text(rpress_scripts.update);
          _self.parents('#rpressModal').find('.rpress-errors-wrap').html(response.msg).removeClass('disabled');
          return false;

        } else {

          rp_setCookie( 'service_type', serviceType, rp_scripts.expire_cookie_time );

          if ( serviceDate === undefined ) {

            rp_setCookie( 'service_date', rpress_scripts.current_date, rp_scripts.expire_cookie_time );
            rp_setCookie( 'delivery_date', rpress_scripts.display_date, rp_scripts.expire_cookie_time );

          } else {

            var delivery_date = $('.delivery-settings-wrapper.active .rpress_get_delivery_dates option:selected').text();
            rp_setCookie( 'service_date', serviceDate, rp_scripts.expire_cookie_time );
            rp_setCookie( 'delivery_date', delivery_date, rp_scripts.expire_cookie_time );
          }

          if( serviceTime === undefined ) {
            rp_setCookie( 'service_time', '', rp_scripts.expire_cookie_time );
          } else {
            rp_setCookie( 'service_time', serviceTime, rp_scripts.expire_cookie_time );
          }

          $('#rpressModal').removeClass( 'show-service-options' );

          if ( foodItemId ) {

            $( '#rpressModal' ).addClass('loading');
            $('#rpress_fooditem_'+foodItemId).find('.rpress-add-to-cart').trigger('click');

          } else {

            MicroModal.close('rpressModal');

            if ( typeof serviceType !== 'undefined' && typeof serviceTime !== 'undefined' ) {

              $('.delivery-wrap .delivery-opts').html('<span class="delMethod">' + serviceLabel + ',</span> <span class="delTime"> ' + Cookies.get( 'delivery_date' ) + ', ' + serviceTime + '</span>');

            } else if( typeof serviceTime == 'undefined' ) {

              $('.delivery-items-options').find('.delivery-opts').html('<span class="delMethod">' + serviceLabel + ',</span> <span class="delTime"> ' + Cookies.get( 'delivery_date' ) + '</span>' );
            }
          }

          //Trigger checked slot event so that it can be used by theme/plugins
          $( document.body ).trigger( 'rpress_checked_slots', [response] );

          //If it's checkout page then refresh the page to reflect the updated changes.
          if( rpress_scripts.is_checkout == '1' )
            window.location.reload();
        }
      }
    });
  });

  // Update Service Date and Time
  $( document ).on( 'click', '.delivery-change', function(e) {
    e.preventDefault();

    var self = $( this );
    var action = 'rpress_show_delivery_options';
    var ServiceType  = rp_getCookie( 'service_type' );
    var ServiceTime  = rp_getCookie( 'service_time' );
    var text = self.text();
    self.text(rp_scripts.please_wait);

    var data = {
      action    : action,
      security  : rp_scripts.service_type_nonce
    }

    $( '#rpressModal' ).addClass( 'show-service-options' );

    $.ajax({
      type     : "POST",
      data     : data,
      dataType : "json",
      url      : rp_scripts.ajaxurl,
      success: function( response ) {

        self.text(text);
        $('#rpressModal .modal-title').html(response.data.html_title);
        $('#rpressModal .modal-body').html(response.data.html);
        MicroModal.show('rpressModal');

        if ( $( '.rpress-tabs-wrapper' ).length ) {

          if( ServiceTime !== '' ) {
            $('.rpress-delivery-wrap').find('select#rpress-'+ServiceType+'-hours').val(ServiceTime);
          }

          $('.rpress-delivery-wrap').find('a#nav-'+ ServiceType + '-tab').trigger('click');
        }

        // Trigger event so themes can refresh other areas.
        $( document.body ).trigger( 'opened_service_options', [ response.data ] );
      }
    })
  });

  // Remove Item from Cart
  $('.rpress-cart').on('click', '.rpress-remove-from-cart', function(event) {

    var $this = $(this),
      item = $this.data( 'cart-item' ),
      action = $this.data( 'action' ),
      id = $this.data( 'fooditem-id' ),
      data = {
        action: action,
        cart_item: item
      };

    $.ajax({

      type: "POST",
      data: data,
      dataType: "json",
      url: rpress_scripts.ajaxurl,
      xhrFields: {
        withCredentials: true
      },
      success: function(response) {

        if ( response.removed ) {

          // Remove the $this cart item
          $('.rpress-cart .rpress-cart-item').each(function() {
            $(this).find("[data-cart-item='" + item + "']").parents('.rpress-cart-item').remove();
          });

          // Check to see if the purchase form(s) for this fooditem is present on this page
          if ($('[id^=rpress_purchase_' + id + ']').length) {
            $('[id^=rpress_purchase_' + id + '] .rpress_go_to_checkout').hide();
            $('[id^=rpress_purchase_' + id + '] a.rpress-add-to-cart').show().removeAttr('data-rpress-loading');

            if (rpress_scripts.quantities_enabled == '1') {
              $('[id^=rpress_purchase_' + id + '] .rpress_fooditem_quantity_wrapper').show();
            }
          }

          $('span.rpress-cart-quantity').text(response.cart_quantity);

          $(document.body).trigger('rpress_quantity_updated', [response.cart_quantity]);

            if ( rpress_scripts.taxes_enabled ) {
              $('.cart_item.rpress_subtotal span').html(response.subtotal);
              $('.cart_item.rpress_cart_tax span').html(response.tax);
            }

            $('.cart_item.rpress_total span.rpress-cart-quantity').html(response.cart_quantity);
            $('.cart_item.rpress_total span.cart-total').html(response.total);

            if ( response.cart_quantity == 0 ) {

              $('.cart_item.rpress_subtotal,.rpress-cart-number-of-items,.cart_item.rpress_checkout,.cart_item.rpress_cart_tax,.cart_item.rpress_total').hide();
              $('.rpress-cart').each(function() {

                var cart_wrapper = $(this).parent();

                if ( cart_wrapper ) {
                  cart_wrapper.addClass('cart-empty')
                  cart_wrapper.removeClass('cart-not-empty');
                }

                $(this).append('<li class="cart_item empty">' + rpress_scripts.empty_cart_message + '</li>');
              });
            }

            $(document.body).trigger('rpress_cart_item_removed', [response]);

            $('ul.rpress-cart > li.rpress-cart-item').each( function( index, item ) {
              $(this).find("[data-cart-item]").attr('data-cart-item', index);
              $(this).find("[data-remove-item]").attr('data-remove-item', index);
              $(this).attr('data-cart-key', index);
            });

            // check if no item in cart left
            if ($('li.rpress-cart-item').length == 0) {
              $('a.rpress-clear-cart').trigger('click');
              $('li.delivery-items-options').hide();
              $('a.rpress-clear-cart').hide();
            }
          }
        }
    });

    return false;
  });

  // Clear All Fooditems from Cart
  $( document ).on('click', 'a.rpress-clear-cart', function(e) {
    e.preventDefault();
    var self = $( this );
    var OldText = self.html();
    var action = 'rpress_clear_cart';
    var data = {
      security : rp_scripts.clear_cart_nonce,
      action   : action
    }

    self.text( rp_scripts.please_wait );

    $.ajax({
      type      : "POST",
      data      : data,
      dataType  : "json",
      url       : rp_scripts.ajaxurl,
      xhrFields : {
        withCredentials: true
      },
      success : function(response) {
        if( response.status == 'success' ) {
          $('ul.rpress-cart').find('li.cart_item.rpress_total').css('display','none');
          $('ul.rpress-cart').find('li.cart_item.rpress_checkout').css('display','none');
          $('ul.rpress-cart').find('li.rpress-cart-item').remove();
          $('ul.rpress-cart').find('li.cart_item.empty').remove();
          $('ul.rpress-cart').find('li.rpress_subtotal').remove();
          $('ul.rpress-cart').find('li.rpress_cart_tax').remove();
          $('ul.rpress-cart').find('li.rpress-delivery-fee').remove();
          $('ul.rpress-cart').append(response.response);
          $('.rpress-cart-number-of-items').css('display','none');
          $('.delivery-items-options').css('display', 'none');
          self.html( OldText );
          self.hide();
        }
      }
    });
  });

  // Proceed to Checkout
  $( document ).on( 'click', '.cart_item.rpress_checkout a', function(e) {
    e.preventDefault();

    var CheckoutUrl = rp_scripts.checkout_page;
    var _self = $(this);
    var OrderText = _self.text();

    var action = 'rpress_proceed_checkout';
    var data = {
      action        : action,
      security      : rp_scripts.proceed_checkout_nonce,
    }

    $.ajax({
      type      : "POST",
      data      : data,
      dataType  : "json",
      url       : rp_scripts.ajaxurl,
      beforeSend : function(){
       _self.text( rp_scripts.please_wait );
      },
      success : function( response ) {
        if ( response.status == 'error' ) {
          if( response.error_msg ) {
            errorString = response.error_msg;
          }
          $( '#rpressModal' ).addClass( 'checkout-error' );
          $( '#rpressModal').find('.modal-title').html( rp_scripts.error );
          $( '#rpressModal .modal-body' ).html(errorString);

          MicroModal.show('rpressModal');
          _self.text( OrderText );
        }
        else {
          window.location.replace( rp_scripts.checkout_page );
        }
      }
    })
  })

  $(document).on('click', 'a.special-instructions-link', function(e) {
    e.preventDefault();
    $(this).parent('div').find('.special-instructions').toggleClass('hide');
  });

  $('body').on('click', '.rpress-filter-toggle', function() {
    $('div.rpress-filter-wrapper').toggleClass('active');
  });

  // Show hide cutlery icon on smaller devices
  $( ".rpress-mobile-cart-icons" ).click(function(){
    $( ".rpress-sidebar-main-wrap" ).css( "left", "0%" );
  });

  $( ".close-cart-ic" ).click(function(){
    $( ".rpress-sidebar-main-wrap" ).css( "left", "100%" );
  });

  // Show Image on Modal
  $(".rpress-thumbnail-popup").fancybox({
    openEffect  : 'elastic',
    closeEffect : 'elastic',

    helpers : {
      title : {
        type : 'inside'
      }
    }
  });

  if ($(window).width() > 991) {
    var totalHeight = $('header:eq(0)').length > 0 ? $('header:eq(0)').height() + 30 : 120;
    if ($(".sticky-sidebar").length != '') {
      $('.sticky-sidebar').rpressStickySidebar({
        additionalMarginTop: totalHeight
      });
    }
  } else {
    var totalHeight = $('header:eq(0)').length > 0 ? $('header:eq(0)').height() + 30 : 70;
  }
});

/* Make addons and Variables clickable for Live Price */
jQuery( document ).ajaxComplete(function() {

  jQuery('#fooditem-details .food-item-list input').on('click', function(event) {
    update_modal_live_price( 'fooditem-details' );
  });

  jQuery('#fooditem-update-details .food-item-list input').on('click', function(event) {
    update_modal_live_price( 'fooditem-update-details' );
  });
});

/* RestroPress Sticky Sidebar - Imported from rp-sticky-sidebar.js */
jQuery(function($){

  if ($(window).width() > 991) {
    var totalHeight = $('header:eq(0)').length > 0 ? $('header:eq(0)').height() + 30 : 120;
    if ($(".sticky-sidebar").length > 0 ) {
      $('.sticky-sidebar').rpressStickySidebar({
        additionalMarginTop: totalHeight
      });
    }
  } else {
    var totalHeight = $('header:eq(0)').length > 0 ? $('header:eq(0)').height() + 30 : 70;
  }

  // Category Navigation
  $('body').on('click', '.rpress-category-link', function(e) {
    e.preventDefault();
    var this_id = $(this).data('id');
    var gotom = setInterval(function () {
        rpress_go_to_navtab(this_id);
        clearInterval( gotom );
    }, 100);
  });

  function rpress_go_to_navtab(id) {
    var scrolling_div = jQuery('div.rpress_fooditems_list').find('div#menu-category-'+id);
    if( scrolling_div.length ) {
      offSet = scrolling_div.offset().top;

      var body = jQuery( "html, body" );

      body.animate({
        scrollTop: offSet - totalHeight
      }, 500);
    }
  }

  $('.rpress-category-item').on('click', function(){
      $('.rpress-category-item').removeClass('current');
      $(this).addClass('current');
  });
});

/* Cart Quantity Changer - Imported from cart-quantity-changer.js */
jQuery(function($) {

  //quantity Minus
  var liveQtyVal;

  jQuery( document ).on('click', '.qtyminus', function(e) {

    // Stop acting like a button
    e.preventDefault();

    // Get the field name
    fieldName = 'quantity';

    // Get its current value
    var currentVal = parseInt( jQuery('input[name='+fieldName+']').val() );

    // If it isn't undefined or its greater than 0
    if ( !isNaN(currentVal) && currentVal > 1 ) {

      // Decrement one only if value is > 1
      jQuery('input[name='+fieldName+']').val(currentVal - 1);
      jQuery('.qtyplus').removeAttr('style');
      liveQtyVal = currentVal - 1;

    } else {

      // Otherwise put a 0 there
      jQuery('input[name='+fieldName+']').val(1);
      jQuery('.qtyminus').css('color','#aaa').css('cursor','not-allowed');
      liveQtyVal = 1;
    }

    jQuery(this).parents('footer.modal-footer').find('a.submit-fooditem-button').attr('data-item-qty', liveQtyVal);
    jQuery(this).parents('footer.modal-footer').find('a.submit-fooditem-button').attr('data-item-qty', liveQtyVal);

    // Updating live price as per quantity
    var total_price = parseFloat(jQuery( '#rpressModal .cart-item-price').attr('data-current'));
    var new_price = parseFloat( total_price * liveQtyVal );
    jQuery( '#rpressModal .cart-item-price').html( rp_scripts.currency_sign + new_price.toFixed(2) );
  });

  jQuery(document).on('click', '.qtyplus', function(e) {

    // Stop acting like a button
    e.preventDefault();

    // Get the field name
    fieldName = 'quantity';

    // Get its current value
    var currentVal = parseInt(jQuery('input[name='+fieldName+']').val());
    // If is not undefined
    if (!isNaN(currentVal)) {
      jQuery('input[name='+fieldName+']').val(currentVal + 1);
      jQuery('.qtyminus').removeAttr('style');
      liveQtyVal = currentVal + 1;
    } else {
      // Otherwise put a 0 there
      jQuery('input[name='+fieldName+']').val(1);
      liveQtyVal = 1;
    }

    jQuery(this).parents('footer.modal-footer').find('a.submit-fooditem-button').attr('data-item-qty', liveQtyVal);
    jQuery(this).parents('footer.modal-footer').find('a.submit-fooditem-button').attr('data-item-qty', liveQtyVal);

    // Updating live price as per quantity
    var total_price = parseFloat(jQuery( '#rpressModal .cart-item-price').attr('data-current'));
    var new_price = parseFloat( total_price * liveQtyVal );
    jQuery( '#rpressModal .cart-item-price').html( rp_scripts.currency_sign + new_price.toFixed(2) );
  });

  jQuery(document).on("input", ".qty", function() {
      this.value = this.value.replace(/\D/g,'');
  });

  jQuery(document).on('keyup', '.qty', function(e) {
    // Updating live price as per quantity
    liveQtyVal = jQuery(this).val();
    var total_price = parseFloat(jQuery( '#rpressModal .cart-item-price').attr('data-current'));
    var new_price = parseFloat( total_price * liveQtyVal );
    jQuery( '#rpressModal .cart-item-price').html( rp_scripts.currency_sign + new_price.toFixed(2) );
  });

});

/* RestroPress Live Search - Imported from live-search.js */
jQuery(function($) {

  jQuery( '.rpress_fooditems_list' ).find( '.rpress-title-holder a' ).each(function(){
    jQuery(this).attr('data-search-term', jQuery(this).text().toLowerCase());
  });

  jQuery( '#rpress-food-search' ).on('keyup', function(){
    var searchTerm = jQuery(this).val().toLowerCase();
    var DataId;
    var SelectedTermId;

    jQuery( '.rpress_fooditems_list' ).find('.rpress-element-title').each(function(index, elem) {
      jQuery(this).removeClass('not-matched');
      jQuery(this).removeClass('matched');
    });

    jQuery('.rpress_fooditems_list').find('.rpress-title-holder a').each(function(){
      DataId = jQuery(this).parents('.rpress_fooditem').attr('data-term-id');

      if ( jQuery(this).filter('[data-search-term *= ' + searchTerm + ']').length > 0 || searchTerm.length < 1 ) {
        jQuery(this).parents('.rpress_fooditem').show();
        jQuery('.rpress_fooditems_list').find('.rpress-element-title').each(function(index, elem) {
          if( jQuery(this).attr('data-term-id') == DataId ) {
            jQuery(this).addClass('matched');
          } else {
            jQuery(this).addClass('not-matched');
          }
        });
      } else {
        jQuery(this).parents('.rpress_fooditem').hide();
        jQuery('.rpress_fooditems_list').find('.rpress-element-title').each(function(index, elem) {
          jQuery(this).addClass('not-matched');
        });
      }
    });
  });
})

/* RestroPress active category highlighter */
jQuery(function($){
  const rp_category_links = $('.rpress-category-lists .rpress-category-link');
  if (rp_category_links.length > 0) {
      const header_height = $('header:eq(0)').height();
      let current_category = rp_category_links.eq('0').attr('href').substr(1);

      function RpScrollingCategories() {
        rp_category_links.each(function(){
          const section_id = $(this).attr('href').substr(1);
          const section = document.querySelector(`.menu-category-wrap[data-cat-id="${section_id}"]`);

          if (section.getBoundingClientRect().top < header_height + 40) {
              current_category = section_id;
          }

          $(this).removeClass('active');
          $(`.rpress-category-lists .rpress-category-link[href="#${ current_category }"]`).addClass('active');

        });
      }
      window.onscroll = function() {
          RpScrollingCategories();
      }
  }
});
