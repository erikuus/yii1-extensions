<?php
/**
 * Pdfable
 *
 * Pdfable is a behavior which adds PDF rendering options to a controller.
 *
 * It uses the PHPWkHtmlToPdf (bundled) to create the PDF, so wkhtmltopdf must be
 * installed (see http://code.google.com/p/wkhtmltopdf/).
 *
 * To attach this behavior to a controller, add it through a behaviors() method:
 *
 *  public function behaviors()
 *  {
 *      return array(
 *          'pdfable' => array(
 *              'class' => 'ext.pdfable.Pdfable',
 *          ),
 *      );
 *  }
 *
 * See below for more configuration options.
 *
 * To render single page PDFs from a view file you just call renderPdf() instead
 * of render. This will convert the view file to a PDF and display it inline in
 * the browser:
 *
 *  public function actionPdfDemo()
 *  {
 *      // Render this view as PDF and display inline in the browser:
 *      $this->renderPdf('pdfDemo');
 *  }
 *
 * Just as with render() you can of course also render more complex views with
 * custom data. And if you want to open a download dialog, you can also pass
 * the download filename as 4th argument:
 *
 *  public function actionInvoice($id)
 *  {
 *      $invoice=Invoice::model()->findByPk($id);
 *
 *      $this->renderPdf('invoice',array(
 *          'invoice' => $invoice,
 *      ), array(), 'invoice_'.$invoice->id.'.pdf');
 *  }
 *
 * PDFs with more than one page are also possible:
 *
 *  public function actionPortfolio($id)
 *  {
 *      $portfolio = Portfolio::model()->findByPk($id);
 *
 *      $pdf = $this->createPdf();
 *      $pdf->renderPage('intro');
 *      $pdf->renderPage('portfolio',array(
 *          'portfolio' => $portfolio,
 *      ));
 *      $pdf->send('portfolio_'.$id.'.pdf');
 *  }
 *
 * You can set default PDF options for the document and each page in the
 * behaviors() method:
 *
 *  public function behaviors()
 *  {
 *      return array(
 *          'pdfable' => array(
 *              'class' => 'ext.pdfable.Pdfable',
 *
 *              // Global PDF options (see wkhtmltopdf -H for details)
 *              'pdfOptions' => array(
 *                  'bin'   => '/usr/bin/wkhtmltopdf',  // path to executable (default)
 *                  'dpi'   => 600,
 *              ),
 *
 *              // Default PDF page options (see wkhtmltopdf -H for details)
 *              'pdfPageOptions' => array(
 *                  'page-size'         => 'A5',
 *
 *                  // You probably always need this, because CSS files from <link>
 *                  // tags in your document are ignored
 *                  'user-style-sheet'  => Yii::getPathOfAlias('webroot').'/css/pdf.css',
 *              ),
 *
 *              // Use other tmp directory instead of Yii::app()->runtimePath
 *              'tmpAlias' => 'application.var.tmp',
 *          ),
 *      );
 *  }
 *
 * The behavior uses some hardcoded defaults for document and page options (see PdfFile).
 * Your configuration above will always be merged with these defaults. If you don't like
 * this and want complete freedom instead, then configure the behavior like this:
 *
 *  public function behaviors()
 *  {
 *      return array(
 *          'pdfable' => array(
 *              'class' => 'ext.pdfable.Pdfable',
 *
 *              'defaultPdfOptions' => array(
 *                  ...
 *                  // default PDF options here
 *                  // could also be an empty array to disable all defaults
 *                  ...
 *              ),
 *
 *              'defaultPdfPageOptions' => array(
 *                  ...
 *                  // default PDF page options here
 *                  // could also be an empty array to disable all defaults
 *                  ...
 *              ),
 *          ),
 *      );
 *  }
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.0.1
 * @license http://www.opensource.org/licenses/MIT
 */
class Pdfable extends CBehavior
{
    /**
     * @var array default PDF options
     */
    public $defaultPdfOptions;

    /**
     * @var array default PDF page options
     */
    public $defaultPdfPageOptions;

    /**
     * @var mixed path alias of temp directory. Defaults to Yii::app()->runtimePath.
     */
    public $tmpAlias = null;

    private $_options = array();
    private $_pageOptions = array();
    private $_tmp;

    /**
     * @return array global PDF options options for wkthmltopdf
     */
    public function getPdfOptions()
    {
        if(!isset($this->_options['bin']))
            $this->_options['bin'] = '/usr/bin/wkhtmltopdf';

        if(!is_executable($this->_options['bin']))
            throw new CException('Could not execute '.$this->_options['bin']);

        if(!isset($this->_options['tmp']))
            $this->_options['tmp'] = $this->getTmpDir();

        return $this->_options;
    }

    /**
     * @param array $value global options for wkhtmltopdf. See 'wkthmltopdf -H'.
     */
    public function setPdfOptions($value)
    {
        $this->_options = $value;
    }

    /**
     * @return array PDF page options for wkhtmltopdf
     */
    public function getPdfPageOptions()
    {
        return $this->_pageOptions;
    }

    /**
     * @param array $value page options for wkhtmltopdf
     */
    public function setPdfPageOptions($value)
    {
        $this->_pageOptions = $value;
    }

    /**
     * @return string the full path to the temp directory
     */
    private function getTmpDir()
    {
        if($this->_tmp===null)
        {
            if($this->tmpAlias!==null)
                $this->_tmp = Yii::getPathOfAlias($this->tmpAlias);
            else
                $this->_tmp = Yii::app()->runtimePath;
        }

        return $this->_tmp;
    }

    /**
     * Render a single view as PDF and send it to the browser
     *
     * @param string $view name of the view to be rendered. See CController::render.
     * @param array $data view data. See CController::render.
     * @param array $options options for this page. See 'wkhtml -H' for available page options
     * @param mixed $filename if provided, a download dialog will open instead of displaying the PDF inline.
     */
    public function renderPdf($view, $data=array(), $options=array(), $filename=null)
    {
        $pdf = $this->createPdf();
        $pdf->renderPage($view, $data, $options);

        if(!$pdf->send($filename))
        {
            throw new CException("Could not create PDF for view $view" . (YII_DEBUG ? "\n\n" . $pdf->getError() : ''));
        }
    }

    /**
     * @return PdfFile a new preconfigured PDF document
     */
    public function createPdf()
    {
        require_once(__DIR__.'/PdfFile.php');

        $pdf = new PdfFile($this->defaultPdfOptions, $this->defaultPdfPageOptions);
        $pdf->controller = $this->owner;
        $pdf->setOptions($this->getPdfOptions());
        $pdf->setPageOptions($this->getPdfPageOptions());

        return $pdf;
    }
}
