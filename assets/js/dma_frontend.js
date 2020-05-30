(function($) {  

    if(ajax_object.dma_ajax_cart_single_product != null) {

        var dmaAddToCartHandlerSingleProduct = function() {
 
       // this.run        = this.run.bind( this );
            $(document.body).on('submit', '.entry-summary form.cart', { form: this }, this.dmaAddToCartButtonSubmit);
            
        }

        dmaAddToCartHandlerSingleProduct.prototype.dmaAddToCartButtonSubmit = function(e) {

            var value_single_add_to_cart = $('.single_add_to_cart_button').val();

            e.preventDefault();

            $('.single_add_to_cart_button').block({

                message: null,

                overlayCSS: {

                    cursor: 'none'

                }

            });

            var product_url = window.location,
                form = $(this);
                
                //console.log(form.serialize());
            if(value_single_add_to_cart != "") {

                $.post(product_url, form.serialize() + '&add-to-cart=' + value_single_add_to_cart, function (result)
                    {
                
                        var cart_dropdown = $('.widget_shopping_cart', result);

                        // update dropdown cart
                        $('.widget_shopping_cart').replaceWith(cart_dropdown);

                        // update fragments
                        $.ajax($warp_fragment_refresh);

                        $('.single_add_to_cart_button').unblock();
                    });

            } else {

                $.post(product_url, form.serialize() + '&_wp_http_referer=' + product_url, function (result)
                    {
                
                        var cart_dropdown = $('.widget_shopping_cart', result);

                        // update dropdown cart
                        $('.widget_shopping_cart').replaceWith(cart_dropdown);

                        // update fragments
                        $.ajax($warp_fragment_refresh);

                        $('.single_add_to_cart_button').unblock();
                    });
            }

        }

    new dmaAddToCartHandlerSingleProduct();

    }

    var dmaRemoveCart = function() {
        $(document.body).on( 'click', '.remove_from_cart_button', { addToCartHandler: this }, this.onRemoveFromCart );
        $(document.body).on( 'click', '.dma_modal_close', this.closeModal );
    }

            /**
     * Update fragments after remove from cart event in mini-cart.
     */
    dmaRemoveCart.prototype.onRemoveFromCart = function( e ) {
        var fncDmaRemoveCart = e.data.addToCartHandler;
        var $thisbutton = $( this ),
            $row        = $thisbutton.closest( 'ul.woocommerce-mini-cart-item' );

        e.preventDefault();

        $row.block({
            message: null,
            overlayCSS: {
                opacity: 0.6
            }
        });
        var product_url = window.location;
        var idRemove = $thisbutton.attr('data-product_id');

        $.ajax({
           url:product_url,
           type:'GET',
           success: function(data) {
            var $get_product = $('.product').find('.added_to_cart');
            var get_id_product = $get_product.prev().attr('data-product_id');
                $get_product.prev().each(function(index, el) {
                    var attrID = $(el).attr('data-product_id');
                    if(attrID == idRemove) {
                        $(el).next().remove();
                   }
               });
           }
        });

        $.ajax({
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_from_cart' ),
                type: 'POST',
                data: { cart_item_key : $thisbutton.data( 'cart_item_key' ) },
                success: function( data ) {
                    if(data.cart_hash == "") {
                        //window.location = $thisbutton.attr( 'href' );
                        //return;
                        
                        fncDmaRemoveCart.closeModal();
                    } 
                }
        });
    
    };

    dmaRemoveCart.prototype.closeModal = function(e) {
        remove_modal();
    };

    new dmaRemoveCart();



    $('.dma_fixed_cart').on('click', function() {
        $.ajax($warp_fragment_refresh);
    });

    var $warp_fragment_refresh = {

        url: wc_cart_fragments_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),

        type: 'POST',

        success: function( data ) {


            if ( data && data.fragments ) {

              // console.log(data.fragments);

                $.each( data.fragments, function( key, value ) {

                    $( key ).replaceWith( value );

            });

            var dma_modal = $('.dma_modal');

            if(typeof data.fragments['div.dma_widget_shopping_cart_content'] == 'undefined') {
                alert('Error 103: Widget Shopping Cart is not working!');
            }

            if(typeof data.fragments['related_products'] != 'undefined') {
                dma_modal.find('.popupCart').html(data.fragments['div.dma_widget_shopping_cart_content'] + data.fragments['related_products'] 
                );

            } else {
                dma_modal.find('.popupCart').html(data.fragments['div.dma_widget_shopping_cart_content']
                );
            }
              
            add_modal();

            $(".dma_modal_close").click(function(){  
                remove_modal();
            });

            $(document.body).on('click keydown', function(event) {   

                if (!$(event.target).closest('.popupCart').length && dma_modal.is(":visible") || event.keydown == 27) {   

                    remove_modal();

                }

            });   

            $( document.body ).trigger( 'wc_fragments_refreshed' );

            }

        },
    };

    $(".dma_modal_close").on('click', function(){  
        remove_modal();
    });

    function add_modal() {
        $('body').addClass('dma_body_block');
        $('.dma_modal').removeClass('dma_modal_hide');
        $('.dma_modal').addClass('dma_modal_show');
    }

    function remove_modal() {
        $('body').removeClass('dma_body_block');
        $('.dma_modal').removeClass('dma_modal_show');
        $('.dma_modal').addClass('dma_modal_hide');
    }


    if(ajax_object.dma_ajax_cart_loop_products != null) {

        var dmaAddToCartHandlerLoopProducts = function() {
            $(document.body).on('click', '.add_to_cart_button', { value: this}, this.dmaAddToCartLoopProducts);
        }


        dmaAddToCartHandlerLoopProducts.prototype.dmaAddToCartLoopProducts = function(e) {

            var $button_add_to_cart = $(this);

            if($button_add_to_cart.is('.ajax_add_to_cart')) {

                if(!$button_add_to_cart.attr('data-product_id')) {

                   alert('Error 104: Something is wrong!');

                }

                e.preventDefault();

                $button_add_to_cart.removeClass( 'added' );
                $button_add_to_cart.addClass( 'loading' );

                var data = {};

                $.each( $button_add_to_cart.data(), function( key, value ) {
                    data[ key ] = value;
                });

                // Fetch changes that are directly added by calling $thisbutton.data( key, value )
                $.each( $button_add_to_cart[0].dataset, function( key, value ) {
                    data[ key ] = value;
                });

                var product_url = window.location;
                
                //console.log(data);

                 $.post(product_url, data['quantity'] + '&add-to-cart=' + data['product_id'], function (result)
                {
            
                    var cart_dropdown = $('.widget_shopping_cart', result);

                    // update dropdown cart
                    $('.widget_shopping_cart').replaceWith(cart_dropdown);

                    // update fragments
                    $.ajax($warp_fragment_refresh);

                    $button_add_to_cart.removeClass('loading');

                });
            }
      
        }

        new dmaAddToCartHandlerLoopProducts();
    }



})(jQuery);

