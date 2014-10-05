jQuery(function(){
	jQuery(".ajax-box").each(function(){
		var url = $(this).data("url");
		var interval = $(this).data("interval");
		var container=$(this);
		setInterval(function() {
			jQuery.ajax({
				'url':url,
				'cache':false,
				'success':function(html){
					if(container.html()!=html){
						container.html(html)
					}
				}
			});
		}, interval);
	});
});