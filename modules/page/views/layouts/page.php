<?php
$this->beginContent();

echo strtr($this->module->pageLayout, array(
	'{breadcrumbs}'=>$this->widget('zii.widgets.CBreadcrumbs', array('links'=>$this->breadcrumbs), true),
	'{menu}'=>$this->widget('PageMenuWidget', $this->module->menuWidgetConfig, true),
	'{content}'=>$content
));

$this->endContent();
?>