<?php
/**
 * XIIPFrame extends CWidget and implements a base class for iframe.
 *
 * The following example shows how to use XIIPZoomifyViewer widget:
 * <pre>
 *    $this->widget('ext.widgets.iipimage.iipzoomifyviewer.XIIPFrame', array(
 *        'options'=>array(
 *            'source'=>'https://www.example.com/path/to/zoomviewer'
 *        )
 *    ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XIIPFrame extends CWidget
{
    public $options;
    public $htmlOptions=array();

    public function init()
    {
        echo CHtml::openTag('div', array('class'=>$this->htmlOptions['class']));

        unset($this->htmlOptions['class']);
        $this->htmlOptions['src']=$this->options['baseUrl'].$this->options['uid'];

        echo CHtml::openTag('iframe', $this->htmlOptions);
    }

    public function run()
    {
        echo "</iframe>";
        echo "</div>";
    }
}