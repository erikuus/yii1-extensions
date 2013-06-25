<?php
/**
 * IasPager class file.
 *
 * This widget creates infinite ajax scroll pager for CListView
 * This extension uses Infinite Ajax Scroll, a jQuery plugin
 *
 * Example:
 *
 * Widget:
 * <pre>
 * $this->widget('zii.widgets.CListView', array(
 *     'id'=>'contest-list',
 *     'dataProvider'=>$dataProvider,
 *     'pager'=>array(
 *         'class'=>'ext.widgets.infinitescroll.IasPager',
 *         'header'=>'',
 *         'listViewId'=>'contest-list',
 *         'itemsSelector'=>'.contest-list',
 *         'rowSelector'=>'.contest-row',
 *         'loaderText'=>Yii::t('ui','Loading...'),
 *         'options'=>array(
 *             'history' => false,
 *             'triggerPageTreshold'=>2,
 *             'trigger'=>Yii::t('ui','Load more')
 *         ),
 *     ),
 *     'itemView'=>'_contest',
 *     'template'=>'{items}{pager}',
 *     'itemsCssClass'=>'contest-list',
 * ));
 * </pre>
 *
 * Partial view _contest:
 * <pre>
 * echo CHtml::encode($data->Name);
 * echo CHtml::encode($data->Description);
 * </pre>
 *
 * @link http://www.yiiframework.com/extension/inifinite-scroll-pager/
 * @author Tpoxa
 * @version 0.1
 */

class IasPager extends CLinkPager
{
    public $listViewId;
    public $rowSelector = '.row';
    public $itemsSelector = ' > .items';
    public $nextSelector = '.next:not(.disabled):not(.hidden) a';
    public $pagerSelector = '.pager';
    public $options = array();
    public $linkOptions = array();
    public $loaderText = 'Loading...';
    private $baseUrl;

    public function init()
    {
        parent::init();

        $assets = dirname(__FILE__) . '/assets';
        $this->baseUrl = Yii::app()->assetManager->publish($assets);

        $cs = Yii::app()->getClientScript();
        $cs->registerCoreScript('jquery');
        $cs->registerCSSFile($this->baseUrl . '/css/jquery.ias.css');

        if (YII_DEBUG)
            $cs->registerScriptFile($this->baseUrl . '/js/jquery.ias.js', CClientScript::POS_END);
        else
            $cs->registerScriptFile($this->baseUrl . '/js/jquery-ias.min.js', CClientScript::POS_END);
        return;
    }

    public function run()
    {
        $js = "jQuery.ias(" .
                CJavaScript::encode(
                        CMap::mergeArray($this->options, array(
                            'container' => '#' . $this->listViewId . ' ' . $this->itemsSelector,
                            'item' => $this->rowSelector,
                            'pagination' => '#' . $this->listViewId . ' ' . $this->pagerSelector,
                            'next' => '#' . $this->listViewId . ' ' . $this->nextSelector,
                            'loader' => $this->loaderText,
                        ))) . ");";


        $cs = Yii::app()->clientScript;
        $cs->registerScript(__CLASS__ . $this->id, $js, CClientScript::POS_READY);


        $buttons = $this->createPageButtons();

        echo $this->header; // if any
        echo CHtml::tag('ul', $this->htmlOptions, implode("\n", $buttons));
        echo $this->footer;  // if any
    }

    protected function createPageButton($label, $page, $class, $hidden, $selected)
    {
        if ($hidden || $selected)
            $class .= ' ' . ($hidden ? 'disabled' : 'active');

        return CHtml::tag('li', array('class' => $class), CHtml::link($label, $this->createPageUrl($page), $this->linkOptions));
    }
}