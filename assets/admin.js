function refresh_tr_odd_event(){
    jQuery('.member_area').find('tr:even').removeClass(['odd', 'even']).addClass('even');
    jQuery('.member_area').find('tr:odd').removeClass(['odd', 'even']).addClass('odd');
}

/*function close_current_popup(){
	jQuery('.popup_wrapper').remove();
	parent.jQuery.fancybox.close();
}*/

function call_ajax( params ){
	var ajax_data = params.data;
	var ajax_url = params.url;
	
	var button = false;
	if (typeof params.button !== 'undefined') {
		button = params.button;
	}
	
	var cover = false;
	if (typeof params.cover !== 'undefined') {
		cover = params.cover;
	}

	return jQuery.ajax({
		type: 'POST',
		dataType: 'json',
		url: ajax_url,
		data: ajax_data,
		beforeSend: function() { if(button) button.prop('disabled', true); if(cover) cover.show(); },
		complete: function() { if(button) button.prop('disabled', false); if(cover) cover.hide(); }
	});
}

/*function open_popup( params ){
	var params_src = params.src;
	
	var params_type = 'inline';
	if (typeof params.type !== 'undefined') {
		params_type = params.type;
	}
	
	var params_beforeLoad = false;
	if (typeof params.beforeLoad !== 'undefined') {
		params_beforeLoad = params.beforeLoad;
	}
		
	var params_width = 600;
	if (typeof params.width !== 'undefined') {
		params_width = params.width;
	}
		
	var params_height = 600;
	if (typeof params.height !== 'undefined') {
		params_height = params.height;
	}
		
	jQuery.fancybox.open({
		src: params_src,
		type: params_type,
		touch: false,
		beforeLoad: function () {
			if(params_beforeLoad){
				var fn = params_beforeLoad;
				if(typeof fn === 'function') {
					fn(id, after);
				}				
			}
		},
		afterLoad: function (instance, current) {
			current.width = params_width;
			current.height = params_height;
		}
	});
	
	return true;
}*/

/*function do__submit(obj){
	var button = jQuery(obj);	
	var cover = jQuery('#cover');
	var destination = jQuery(button.data('destination'));
	var dataString = jQuery('.popup_wrapper :input').serialize();

	var params = {};
	params.data = dataString;
	params.url = ajaxurl;
	params.button = button;
	params.cover = cover;
	console.log(params); 
	
	call_ajax(params).done(function(response){
		console.log('DO__SUBMIT AJAX RESPONSE::');
		console.log(response);
		if( response.status == 'ok' && response.html){
			//destination.append(response.html);
			destination.html(response.html);
		}else{
			//dispay error message
			alert('ERROR: ' . response.message);
		}
		
	}).fail(function(response){
		console.log('FAIL: ' . response);
	});
}*/


