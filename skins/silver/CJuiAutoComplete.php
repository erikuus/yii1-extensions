<?php
return array(
	'default'=>array(
		'options'=>array(
			'delay'=>300,
			'minLength'=>2,
			'open'=>'js: function( event, ui ) {
				$( this ).autocomplete( "widget" )
					.find( "ui-menu-item-alternate" )
					.removeClass( "ui-menu-item-alternate" )
					.end()
					.find( "li.ui-menu-item:odd a" )
					.addClass( "ui-menu-item-alternate" );
			}',
		)
	)
);