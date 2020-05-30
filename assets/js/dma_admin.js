jQuery(document).ready(function($) { 	

	var dmanumca = function() {
		$(document.body).on('click', '.upgrade_no', this.DmaUpgrade);
		$(document.body).on('click', '#dma_modal_cart_export', this.DmaExportJson);
		$(document.body).on('click', '#dma_modal_cart_import', this.DmaImportJson);
		$(document.body).on('change', '#dma_file', this.DmaChangeFile);
	}

	dmanumca.prototype.DmaUpgrade = function() {
		var dma_upgrade_product_id = $('#dma_upgrade_product');
		var upgrade = $(this).text();
		$.post(
		    dma_var_plugin.ajax_url, 
		    {
		        action: "dmanumca",
		       	upgrade: upgrade,
		        nonce: dma_var_plugin.nonce
		    }, 
		    function(response) {
		        dma_upgrade_product_id.html(response);
		    }
		);
	}

	dmanumca.prototype.DmaExportJson = function() {
		$.post(
		    dma_var_plugin.ajax_url, 
		    {
		        action: "dmaExportJson",
		        nonce: dma_var_plugin.nonce
		    }, 
		    function(response) {
		    	const filename = 'data.json';
		        let element = document.createElement('a');
				element.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent(response));
				element.setAttribute('download', filename);

				element.style.display = 'none';
				document.body.appendChild(element);

				element.click();

				document.body.removeChild(element);
		    }
		);
	}
	dmanumca.prototype.DmaChangeFile = function() {
		$('#dma_modal_cart_import').prop( "disabled", false );
	}
	$('#dma_modal_cart_import').prop( "disabled", true );
	dmanumca.prototype.DmaImportJson = function() {
		var file = $('#dma_file').prop('files')[0];
			if(file.type == 'application/json') {
				var myformData = new FormData(); 		
        		myformData.append('file', file);
        		myformData.append('action', 'dmaImportJson');
        		myformData.append('nonce', dma_var_plugin.nonce);
			} else {
				alert('Error 102: Wrong file extension!');
			}
			$.ajax({
		        type: "POST",
		        url: dma_var_plugin.ajax_url,
		        processData: false,
		        contentType: false,
		        data: myformData,
		        success: function (data) {
		        	console.log(data);
		        	if(data.nothing) {
		        		$('.dma_notice').addClass('dma_warning');
		        		setTimeout(function(){ 
    					$('.dma_notice').fadeIn();
	    				}, 500);
			        	$('.dma_notice').html(data.nothing);
	    					setTimeout(function(){ 
	    					$('.dma_notice').fadeOut();
	    				}, 3000);
		        	} else if (data.update) {
		        		setTimeout(function(){
		        			location.reload();
		        		},1500);
		        		$('.dma_notice').addClass('dma_success');
		        		setTimeout(function(){ 
    					$('.dma_notice').fadeIn();
	    				}, 500);
			        	$('.dma_notice').html(data.update);
	    					setTimeout(function(){ 
	    					$('.dma_notice').fadeOut();
	    				}, 3000);
		        	}		
		    	}, 
		    	fail: function(xhr, textStatus, errorThrown){
       				alert('error 101: request failed');
    			}
			});
	}

	new dmanumca;

	if(dma_var_plugin.dma_hook_page == true) {

		//return preview background color, text color and opacity
		$('#form_dma').each(function(){

		var dma_background_color = $('input[name*="dmanumca_name[dma_modal_cart_background_color]"]', this);
		var dma_text_color = $('input[name*="dmanumca_name[dma_modal_cart_text_color]"]', this);
		var dma_title_color = $('input[name*="dmanumca_name[dma_modal_cart_title_color]"]', this);
		var dma_modal_cart_button_view_cart_background_color = $('input[name*="dmanumca_name[dma_modal_cart_button_view_cart_background_color]"]', this);
		var dma_modal_cart_button_view_cart_border_color = $('input[name*="dmanumca_name[dma_modal_cart_button_view_cart_border_color]"]', this);
		var dma_modal_cart_button_checkout_border_color = $('input[name*="dmanumca_name[dma_modal_cart_button_checkout_border_color]"]', this);
		var dma_modal_cart_button_checkout_bg_color = $('input[name*="dmanumca_name[dma_modal_cart_button_checkout_background_color]"]', this);
		var dma_modal_cart_button_checkout_text_color = $('input[name*="dmanumca_name[dma_modal_cart_button_checkout_text_color]"]', this);
		var dma_modal_cart_button_view_cart_text_color = $('input[name*="dmanumca_name[dma_modal_cart_button_view_cart_text_color]"]', this);
		var dma_modal_cart_close_button_color = $('input[name*="dmanumca_name[dma_modal_cart_close_button_color]"]', this);
		var dma_modal_cart_close_button_background_color = $('input[name*="dmanumca_name[dma_modal_cart_close_button_background_color]"]', this);
		var dma_modal_cart_price_background_color = $('input[name*="dmanumca_name[dma_modal_cart_price_background_color]"]', this);
		var dma_modal_cart_title_linked_products_color = $('input[name*="dmanumca_name[dma_modal_cart_title_linked_products_color]"]', this);
		var dma_modal_cart_product_title_linked_products_color = $('input[name*="dmanumca_name[dma_modal_cart_product_title_linked_products_color]"]', this);
		var dma_modal_cart_button_add_to_cart_text_color = $('input[name*="dmanumca_name[dma_modal_cart_button_add_to_cart_text_color]"]', this);
		var dma_modal_cart_button_add_to_cart_border_color = $('input[name*="dmanumca_name[dma_modal_cart_button_add_to_cart_border_color]"]', this);
		var dma_modal_cart_button_add_to_cart_background_color = $('input[name*="dmanumca_name[dma_modal_cart_button_add_to_cart_background_color]"]', this);
		var dma_modal_cart_price_text_color = $('input[name*="dmanumca_name[dma_modal_cart_price_text_color]"]', this);
		var dma_modal_cart_button_count_color = $('input[name*="dmanumca_name[dma_modal_cart_button_count_color]"]', this);
		var dma_modal_cart_button_count_background = $('input[name*="dmanumca_name[dma_modal_cart_button_count_background]"]', this);
		dma_modal_cart_button_view_cart_background_color.disabled = true;
		

		dma_background_color.wpColorPicker();
		dma_text_color.wpColorPicker();
		dma_title_color.wpColorPicker();
		dma_modal_cart_button_view_cart_background_color.wpColorPicker();
		dma_modal_cart_button_view_cart_border_color.wpColorPicker();
		dma_modal_cart_button_checkout_bg_color.wpColorPicker();
		dma_modal_cart_button_checkout_border_color.wpColorPicker();
		dma_modal_cart_button_checkout_text_color.wpColorPicker();
		dma_modal_cart_button_view_cart_text_color.wpColorPicker();
		dma_modal_cart_close_button_color.wpColorPicker();
		dma_modal_cart_close_button_background_color.wpColorPicker();
		dma_modal_cart_price_background_color.wpColorPicker();
		dma_modal_cart_title_linked_products_color.wpColorPicker();
		dma_modal_cart_product_title_linked_products_color.wpColorPicker();
		dma_modal_cart_button_add_to_cart_text_color.wpColorPicker();
		dma_modal_cart_button_add_to_cart_background_color.wpColorPicker();
		dma_modal_cart_button_add_to_cart_border_color.wpColorPicker();
		dma_modal_cart_price_text_color.wpColorPicker();
		dma_modal_cart_button_count_background.wpColorPicker();
		dma_modal_cart_button_count_color.wpColorPicker();

	});


	//wrap into div
	$( "#accordion .form-table" ).wrap('<div></div>');

	//accordion elements
	$( "#accordion" ).accordion({
	 	header: "h3",
	 	active: false,
	 	collapsible:true,
	 	heightStyle: "content",
	 	animate: {"duration": 200, "easing": "swing"},
	 	icons: { "header": "ui-icon-plus", "activeHeader": "ui-icon-minus" },
	});



	//popup stick on display
	var viewportWidth = $(window).width();
	var viewportHeight = $(window).height();

	var elementPosTop = $('.popupCart').position().top;
	
        $(window).scroll(function()
        {
            var wintop = $(window).scrollTop();
            //if top of element is in view
            if (wintop > elementPosTop && viewportWidth >=991 )
            {
                //always in view
                $('.popupCart').removeClass('popup_relative');
                $('.popupCart').addClass('popup_fixed');
               
            }
            else
            {
            	$('.popupCart').removeClass('popup_fixed');
            	$('.popupCart').addClass('popup_relative');
                //reset back to normal viewing
              
            }
        });



    $('#dma_modal_cart_show_button_view_cart').on('change', function(){
    	if($(this).is(":checked")) {
    		$('#view-cart').fadeIn();
    		setTimeout(function(){ 
    			$('#view-cart').css('display', 'inline-flex');
    		}, 500);
    	} else {
    		$('#view-cart').fadeOut();
    		setTimeout(function(){ 
    			$('#view-cart').css('display', 'none');
    		}, 500);		
    	}
    });

    $('#dma_modal_cart_show_button_checkout').on('change', function(){
    	if($(this).is(":checked")) {
    		$('#checkout').fadeIn();
    		setTimeout(function(){ 
    			$('#checkout').css('display', 'inline-flex');
    		}, 500);
    	} else {
    		$('#checkout').fadeOut();
    		setTimeout(function(){ 
    			$('#checkout').css('display', 'none');
    		}, 500);		
    	}
    });
	}
});

