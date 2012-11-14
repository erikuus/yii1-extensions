jQuery(function(){
	jQuery(".ajax-box").each(function(){
		var url = $(this).data("url");
		var container=$(this);
		jQuery.ajax({
			'url':url,
			'cache':false,
			'success':function(html){container.html(html)}
		});
	});
});