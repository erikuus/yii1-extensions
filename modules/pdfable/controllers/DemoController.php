<?php
class DemoController extends Controller
{
    public $layout='//layouts/column1';

    public function behaviors()
    {
        return array(
            'pdfable'=>array(
                'class' => 'ext.behaviors.pdfable.Pdfable',

                // Global default options for wkhtmltopdf
                'pdfOptions' => array(
                    // Use binary path from module config
                    'bin'               => $this->module->bin,
                ),

                // Default page options
                'pdfPageOptions' => array(
                    // We supply the PDF CSS file manually here
                    'user-style-sheet'  => $this->cssFile,
                ),

                // Use this to disable any hardcoded defaults
                // 'defaultPdfOptions' => array(),
                // 'defaultPdfPageOptions' => array(),
            ),
        );
    }

    /**
     * Render index page
     */
    public function actionIndex()
    {
        $this->render('index');
    }

    /**
     * Render single page PDF / Preview
     *
     * @param int $preview wether to show HTML preview
     * @param int $download wether to open download dialog
     */
    public function actionSingle($preview=0, $download=0)
    {
        if($preview)
        {
            $this->registerPreviewCss();
            $this->render('invoice');
        }
        else
            $this->renderPdf('invoice', array(), array(), $download ? 'invoice.pdf' : null);
    }

    /**
     * Render multi page PDF / Preview
     *
     * @param int $preview wether to show HTML preview
     * @param int $download wether to open download dialog
     */
    public function actionMulti($preview=0, $download=0)
    {
        if($preview)
        {
            $this->registerPreviewCss();
            $this->render('page1');
            $this->render('page2');
            $this->render('page3');
        }
        else
        {
            $pdf = $this->createPdf();
            $pdf->renderPage('page1');
            $pdf->renderPage('page2');
            $pdf->renderPage('page3');

            $pdf->send($download ? 'multi.pdf' : null);
        }
    }

    /**
     * Publish the PDF CSS for the HTML preview.
     */
    public function registerPreviewCss()
    {
        $cs = Yii::app()->clientScript;
        $cs->registerCssFile(Yii::app()->assetManager->publish($this->cssFile));
    }

    /**
     * We use clips for some layouts. Reset them after each page.
     */
    public function afterRender($view, &$output)
    {
        $this->clips->clear();
        parent::afterRender($view, $output);
    }

    /**
     * @return string the full path to the PDF CSS file
     */
    public function getCssFile()
    {
        return Yii::getPathOfAlias('ext.modules.pdfable.assets.css.pdf').'.css';
    }
}
