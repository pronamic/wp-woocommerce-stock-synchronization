var StockSynchronizationAdmin = {
	
	ready: function() {
		
	}
	
	,single_product: {
		
		config:{
			dom:{}
			,settings:{}
		}
		
		,ready: function() {
			StockSynchronizationAdmin.single_product.config.dom.sync_holder = jQuery('.jStockSync');
			StockSynchronizationAdmin.single_product.config.dom.sync_spinner_holder = jQuery('.jSync_spinner_holder');
			StockSynchronizationAdmin.single_product.config.dom.sync_button = jQuery('.jSyncSingleProductButton');
			
			StockSynchronizationAdmin.single_product.binds();
		}
		
		,binds: function() {
			StockSynchronizationAdmin.single_product.config.dom.sync_button.click(
				StockSynchronizationAdmin.single_product.sync
			);
		}
		
		,sync: function(e) {
			e.preventDefault();
			
			var post_id   = jQuery('#post_ID').val(),
				post_type = jQuery('#post_type').val();
			
			var spinner_img = new Image();
			spinner_img.src = StockSynchronizationVars.single_product.spinner;
			
			StockSynchronizationAdmin.single_product.config.dom.sync_spinner_holder.html(
				spinner_img
			);
			
			jQuery.ajax({
				 type:'POST'
				,url:ajaxurl
				,dataType:'json'
				,data:{
					 action:'stock_sync_single_product'
					,post_id:post_id
					,post_type:post_type
				}
				,success: StockSynchronizationAdmin.single_product.sync_success
				,failed: StockSynchronizationAdmin.single_product.sync_failed
			});
			
		}
		
		,sync_success: function(data) {
			if(true === data.resp) {
				StockSynchronizationAdmin.notice.show_updated_box(
					StockSynchronizationAdmin.single_product.config.dom.sync_holder,
					StockSynchronizationVars.single_product.sync_success_success_message
				);
			} else {
				var errorMsg = data.errors.join(',');
				StockSynchronizationAdmin.notice.show_error_box(
					StockSynchronizationAdmin.single_product.config.dom.sync_holder,
					errorMsg
				);
			}
			
			StockSynchronizationAdmin.single_product.config.dom.sync_spinner_holder.empty();
		}
		
		
		,sync_failed: function(one,two,three) {
			
		}
		
	}
	
	,notice: {
		
		config:{}
		
		,show_updated_box: function(element,msg) {
			var info_box = jQuery('<div></div>');
				
			info_box.addClass('updated').addClass('stock-sync-notice').html(jQuery('<p></p>').html(msg));
			
			element.prepend(info_box);
		}
		
		,show_error_box: function(element,msg) {
			var error_box = jQuery('<div></div>');
			
			error_box.addClass('error').addClass('stock-sync-notice').html(jQuery('<p></p>').html(msg));
			
			element.prepend(error_box);
		}
	}
	
};

jQuery(StockSynchronizationAdmin.ready);