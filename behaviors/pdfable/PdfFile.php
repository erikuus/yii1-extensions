<?php
/**
 * PdfFile
 *
 * PdfFile represents a PDF document.
 *
 * It extends WkHtmlToPdf and adds methods to render PDF pages from view files.
 *
 * To render the view files the current controller must be set in $controller.
 * Alternatively - e.g. for offline rendering - $layout, $layoutPath and $viewPath
 * can be set.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/MIT
 */
require_once(__DIR__.'/WkHtmlToPdf.php');
class PdfFile extends WkHtmlToPdf
{
    /**
     * @var mixed the current controller. If null, $layoutPath and $viewPath must be set.
     */
    public $controller;

    /**
     * @var string the layout name.
     */
    public $layout;

    /**
     * @var string path to the layout directory. If empty, no layout is applied.
     */
    public $layoutPath;

    /**
     * @var string path to the base view directory
     */
    public $baseViewPath;

    /**
     * @var string path to the controller view directory
     */
    public $viewPath;

    /**
     * @var array default PDF options
     */
    public $defaultOptions = array(
        'no-outline',
        'encoding'      => 'UTF-8',
        'margin-top'    => 0,
        'margin-right'  => 0,
        'margin-bottom' => 0,
        'margin-left'   => 0,
    );

    /**
     * @var array default PDF page options
     */
    public $defaultPageOptions = array(
        'disable-smart-shrinking',
    );

    /**
     * Either set supplied options/pageOptions (can also be empty array) or use default
     * options/pageOptions.
     *
     * @param array $options global options for wkhtmltopdf.
     * @param array $pageOptions page options for wkhtmltopdf.
     */
    public function __construct($options=null, $pageOptions=null)
    {
        $this->setOptions($options===null ? $this->defaultOptions : $options);
        $this->setPageOptions($pageOptions===null ? $this->defaultPageOptions : $pageOptions);
    }

    /**
     * Render a single view as PDF and add it to this document
     *
     * @param string $view name of the view to be rendered. See CController::render.
     * @param array $data view data. See CController::render.
     * @param array $options options for this page. See 'wkhtml -H' for available page options
     */
    public function renderPage($view, $data=array(), $options=array())
    {
        $this->addPage($this->render($view, $data), $options);
    }

    /**
     * Render a view file and return result
     *
     * If $controller is not set, the (localized) view file is searched in $viewPath
     * and the layout in $layoutFile is applied, if set.
     *
     * @param string $view name of view to render
     * @param array $data view data
     * @return string rendered content
     */
    private function render($view, $data=array())
    {
        if(($controller=$this->controller)===null)
        {
            static $controller;
            if($controller===null)
                $controller = new CController('pdffile');

            $controller->layout = $this->layout;

            // Required to make console app play nicely during rendering
            Yii::app()->attachBehavior('consoleWorkaround', array(
                'class'         => 'ConsoleAppWorkaround',
                '_viewPath'     => $this->baseViewPath,
                '_layoutPath'   => $this->layoutPath,
            ));

            $file = Yii::app()->findLocalizedFile($this->viewPath.'/'.$view.'.php');
            if($file===false)
                throw new CException('Could not find view file '.$view);

            ini_set('implicit_flush', false);
            $content = $controller->renderInternal($file, $data, true);

            if($controller->layout===null)
                return $content;

            return $controller->renderInternal($controller->getLayoutFile($controller->layout), array(
                'content' => $content,
            ), true);
        }
        else
            return $controller->render($view, $data, true);
    }
}

/**
 * ConsoleAppWorkaround
 *
 * Workaround some limitations of console applications.
 */
class ConsoleAppWorkaround extends CBehavior
{
    public $_layoutPath;
    public $_viewPath;

    private $_factory;

    public function getWidgetFactory()
    {
        if($this->_factory===null)
            $this->_factory = Yii::createComponent(array('class'=>'CWidgetFactory'));

        return $this->_factory;
    }

    public function getLayoutPath()
    {
        return $this->_layoutPath;
    }

    public function getViewPath()
    {
        return $this->_viewPath;
    }
    public function getTheme()
    {
        return null;
    }

    public function getViewRenderer()
    {
        return null;
    }

}
