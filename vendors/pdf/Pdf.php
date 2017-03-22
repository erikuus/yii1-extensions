<?php
/**
 * Print to pdf using dompdf HTML to PDF converter
 *
 * For example, to create pdf put this action into controller:
 * <pre>
 * public function actionPrintToPdf()
 * {
 *     Yii::import('ext.vendors.pdf.Pdf',true);
 *     $model = $this->loadModel();
 *     $html = $this->renderPartial('_print', array('model'=>$model), true, true);
 *     $pdf = new Pdf();
 *     $pdf->render($html, '{name of pdf file}');
 * }
 * </pre>
 *
 * @link http://code.google.com/p/dompdf/
 */
class Pdf
{
	/**
	 * Render pdf content
	 * @param string $html
	 * @param string $filename
	 * @param integer $mode [0|1|2];
	 * defaults to 1 meaning that pdf is rendered as attachment;
	 * other options are: 0 - pdf is rendered as inline file, 2 - pdf content is returned
	 * @param string $paper, defaults to 'a4'
	 * @param string $orientation [portrait|landscape], defaults to 'portrait'
	 */
	public function render($html,$filename,$mode=1,$paper='a4',$orientation='portrait')
	{
		Yii::import('ext.vendors.pdf.dompdf.*');
		require_once ('dompdf_config.inc.php');
		Yii::registerAutoloader('DOMPDF_autoload');

		$dompdf=new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_paper($paper,$orientation);
		$dompdf->render();

		if($mode>1)
			return $dompdf->output();
		else
			$dompdf->stream($filename.".pdf", array("Attachment"=>$mode));
	}
}