<?php
/**
 * PdfableModule
 *
 * This is the demo module for the Pdfable extension.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.0.0
 * @license http://www.opensource.org/licenses/MIT
 */
class PdfableModule extends CWebModule
{
    /**
     * @var string path to your wkhtmltopdf binary. Defaults to /usr/bin/wkhtmltopdf.
     */
    public $bin = '/usr/bin/wkhtmltopdf';
}
