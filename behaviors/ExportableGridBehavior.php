<?php

/**
 * GridView Export Behavior
 *
 * This behaviour allows you to add an 'Export' button near your gridview, that will download the grid filtered data as CSV.
 *
 * @version 2.0
 * @author Geronimo Oñativia / http://www.estudiokroma.com
 * @link http://www.yiiframework.com/extension/exportablegridbehavior
 */
class ExportableGridBehavior extends CBehavior
{
	public $buttonId = 'export-button';
	public $exportParam = 'exportCSV';
	public $csvDelimiter = ',';
	public $csvEnclosure = '"';
	public $filename = 'export.csv';
	public $addUTF8BOM = true;

	private $headersSent = false;

	/**
	 * @param mixed $data A Traversable of CModel or a CModel where data will be fetch from
	 * @param array $attributes Attribute names of CModel to be exported.
	 * @param bool $endApplication Application will be ended if true. false to keep going and export more data. Defautls to TRUE.
	 * @param integer $endLineCount Number of newlines to append below this data. Defaults to 0.
	 */
	public function exportCSV($data, $attributes = array(), $endApplication = true, $endLineCount = 0) {
		if ($this->isExportRequest()) {
			$this->sendHeaders();
			$fileHandle = fopen('php://output', 'w');
			if ($data instanceof CActiveDataProvider) {
				$this->csvRowHeaders($fileHandle, $attributes, $data->model);
				$this->csvRowModels($fileHandle, new CDataProviderIterator($data, 150), $attributes);
			} else if (is_array($data) && current($data) instanceof CModel) {
				$this->csvRowHeaders($fileHandle, $attributes, current($data));
				$this->csvRowModels($fileHandle, $data, $attributes);
			} else if (is_array($data) && is_string(current($data))) {
				fputcsv($fileHandle, $data, $this->csvDelimiter, $this->csvEnclosure);
			} else if ($data instanceof CModel) {
				$this->csvModel($fileHandle, $data, $attributes);
			}
			fprintf($fileHandle, str_repeat("\n", $endLineCount));
			fclose($fileHandle);

			if ($endApplication) {
				Yii::app()->end(0, false);
				exit(0);
			}
		}
	}

	private function csvRowHeaders($fileHandle, $attributes, CModel $model) {
		$row = array();
		foreach ($attributes as $attr) {
			$row[] = $model->getAttributeLabel($attr);
		}
		fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
	}

	private function csvRowModels($fileHandle, Traversable $cModels, $attributes) {
		foreach ($cModels as $cModel) {
			$row = array();
			foreach ($attributes as $attr) {
				$row[] = CHtml::value($cModel, $attr);
			}
			fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
		}
	}

	private function csvModel($fileHandle, CModel $cModel, $attributes) {
		foreach ($attributes as $attr) {
			$row = array();
			$row[] = $cModel->getAttributeLabel($attr);
			$row[] = CHtml::value($cModel, $attr);
			fputcsv($fileHandle, $row, $this->csvDelimiter, $this->csvEnclosure);
		}
	}

	private function sendHeaders() {
		if ($this->headersSent === false) {
			$this->headersSent = true;
			// disable caching
			$now = gmdate("D, d M Y H:i:s");
			header("Expires: Tue, 01 Jan 2013 00:00:00 GMT");
			header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
			header("Last-Modified: {$now} GMT");
			// force download
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			// disposition / encoding on response body
			header("Content-Disposition: attachment;filename=\"{$this->filename}\"");
			header("Content-Transfer-Encoding: binary");
			if ($this->addUTF8BOM)
				print "\xEF\xBB\xBF";
		}
	}

	public function isExportRequest() {
		return Yii::app()->request->getParam($this->exportParam, false);
	}

	public function renderExportGridButton(CGridView $grid, $label='Export', $htmlOptions = array()) {
		if (!isset($htmlOptions['id'])) {
			$htmlOptions['id'] = $this->buttonId;
		}
		echo CHtml::link($label, '#', $htmlOptions);
		Yii::app()->clientScript->registerScript('exportgrid'.$htmlOptions['id'], "$('#" . $htmlOptions['id'] . "').on('click',function() {
			var downloadUrl=$('#" . $grid->id . "').yiiGridView('getUrl');
			downloadUrl+=((downloadUrl.indexOf('?')==-1)?'?':'&');
			downloadUrl+='{$this->exportParam}=1';
			window.open( downloadUrl ,'_blank');
		});");
	}
}