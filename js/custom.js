jQuery(document).ready( function($) {
	jQuery('#update_polls').on('submit',function(e){
		e.preventDefault();
		jQuery('.poll_section_loading').show();
		//console.log( $( this ).serializeArray() );
		var formdata = $( this ).serializeArray();
		 var data = {
			action: 'onevoice_update_poll_details',
			items: formdata
			
		};
		jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success: function(response) {
						jQuery('.poll_section_loading').hide();
						jQuery('.poll_section_success').show();
						setTimeout(function() {
							jQuery('.poll_section_success').hide();
						}, 500);
					}
		});
		
		
		
	});
});