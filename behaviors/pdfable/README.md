# PDFable

`PDFable` is a [Yii](http://www.yiiframework.com) extension to create PDFs from web
pages with [PHPWkHtmlToPdf](http://mikehaertl.github.com/phpwkhtmltopdf/) (included).

## Requirements

`PDFable` requires the [wkhtmltopdf](http://code.google.com/p/wkhtmltopdf/) binary to be
installed on your system.

## Installation

Download the package file and unzip it to the `extensions` directory. Note, that you
may want to rename the directory from `pdfable-x.y.z` to `pdfable`.

## Example module

To try out the extension, you can configure the `pdfable` example module in your `main.php`:

```php
<?php
    'modules'=>array(
        'pdfable'=>array(
            'class'=>'ext.modules.pdfable.PdfableModule',
            // Optional: Set path to wkthmltopdf binary
            //'bin' => '/usr/bin/wkhtmltopdf',
        ),
```

The module should then be available from
[http://localhost/index.php?r=pdfable/demo](http://localhost/index.php?r=pdfable/demo)
or whatever you use as hostname. I recommend to have a look at this example module's code.

## Basic configuration

`PDFable` comes as a controller behavior which gets attached through `behaviors`:

```php
<?php
class MyController extends Controller
{
    public function behaviors()
    {
        return array(
            'pdfable'=>array(
                'class' => 'ext.behaviors.pdfable.Pdfable',
            ),
        );
    }
```

## Basic use

To render single page PDFs from a view file you just call `renderPdf()` instead
of `render()`. This will convert the view file to a PDF and display it inline in
the browser:

```php
    public function actionPdfDemo()
    {
        // Render this view as PDF and display inline in the browser:
        $this->renderPdf('pdfDemo');
    }
```

Just as with render() you can of course also render more complex views with
custom data. And if you want to open a download dialog, you can also pass
the download filename as 4th argument:

```php
<?php
    public function actionInvoice($id)
    {
        $invoice=Invoice::model()->findByPk($id);

        $this->renderPdf('invoice',array(
            'invoice' => $invoice,
        ), array(), 'invoice_'.$invoice->id.'.pdf');
    }
```

The third parameter allows you to pass additional PDF page options to `wkhtmltopdf`.
See advanced configuration below.

## Advanced use

### Multi-Page PDFs

PDFs with more than one page (=view) are also possible. Here you would
use the `createPdf()` method. It returns a `PdfFile` object.

```php
<?php
    public function actionPortfolio($id)
    {
        $portfolio = Portfolio::model()->findByPk($id);

        $pdf = $this->createPdf();
        $pdf->renderPage('intro');
        $pdf->renderPage('portfolio',array(
            'portfolio' => $portfolio,
        ));
        $pdf->send('portfolio_'.$id.'.pdf');
    }
```

### Use from console commands

You can also create PDFs from console commands through the low-level `PdfFile` class.
The following example is taken from the example module:

```php
    public function actionIndex($filename)
    {
        $pdf = new PdfFile;

        // We have to set some paths ...
        $pdf->baseViewPath  = Yii::getPathOfAlias('ext.pdfable.pdfable.views');
        $pdf->layoutPath    = Yii::getPathOfAlias('ext.pdfable.pdfable.views.layouts');
        $pdf->viewPath      = Yii::getPathOfAlias('ext.pdfable.pdfable.views.demo');

        // ... and supply our custom CSS file
        $pdf->setOptions(array(
            'user-style-sheet'  => Yii::getPathOfAlias('ext.pdfable.pdfable.assets.css.pdf').'.css',
        ));

        $pdf->renderPage('invoice');
        $pdf->renderPage('page1');
        $pdf->renderPage('page2');

        $pdf->saveAs($filename);
    }
```

If you want to try the example command you can add it to the `commandMap` in your `console.php`:

```php
<?php
    'commandMap' => array(
        'demopdf' => array(
            'class' => 'ext.pdfable.pdfable.commands.DemopdfCommand',
        ),
    ),
```

You then can create an example PDF with `./yiic demopdf --filename=/tmp/demo.pdf`.


## Advanced configuration

It's recommended to have a look at the [PHPWkHtmlToPdf](http://mikehaertl.github.com/phpwkhtmltopdf/)
documentation. All the options described there are also available with `PDFable`.

### Default page options

You can set default PDF options for the document and each page in the
`behaviors()` method:

```php
<?php
   public function behaviors()
   {
       return array(
           'pdfable' => array(
               'class' => 'ext.pdfable.Pdfable',

               // Global PDF options (see wkhtmltopdf -H for details)
               'pdfOptions' => array(
                   'bin'   => '/usr/bin/wkhtmltopdf',  // path to executable (default)
                   'dpi'   => 600,
               ),

               // Default PDF page options (see wkhtmltopdf -H for details)
               'pdfPageOptions' => array(
                   'page-size'         => 'A5',

                   // You probably always need this, because CSS files from <link>
                   // tags in your document are ignored
                   'user-style-sheet'  => Yii::getPathOfAlias('webroot').'/css/pdf.css',
               ),

               // Use other tmp directory instead of Yii::app()->runtimePath
               'tmpAlias' => 'application.var.tmp',
           ),
       );
   }
```

### Override hardcoded page defaults

The behavior uses some hardcoded defaults for document and page options (see PdfFile).
Your configuration above will always be merged with these defaults. If you don't like
this and want complete freedom instead, then configure the behavior like this:

```php
<?php
   public function behaviors()
   {
       return array(
           'pdfable' => array(
               'class' => 'ext.pdfable.Pdfable',

               'defaultPdfOptions' => array(
                   ...
                   // default PDF options here
                   // could also be an empty array to disable all defaults
                   ...
               ),

               'defaultPdfPageOptions' => array(
                   ...
                   // default PDF page options here
                   // could also be an empty array to disable all defaults
                   ...
               ),
           ),
       );
   }
```

### Custom page options

You can supply custom `wkhtmltopdf` page options along as third parameter to 
`renderPdf()`. The format is the same as for `addPage()` in `PHPWkHtmlToPdf`.
