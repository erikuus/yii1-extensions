<?php
$this->beginContent();

echo strtr($this->module->pageLayout, array(
	'{menu}'=>$this->widget('PageMenuWidget', array(), true),
	'{breadcrumbs}'=>$this->widget('zii.widgets.CBreadcrumbs', array('links'=>$this->breadcrumbs), true),
	'{content}'=>$content
));

$this->endContent();
?>