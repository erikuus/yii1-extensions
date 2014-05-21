<?php

class RequestController extends CController
{
	/**
	 * @return array actions
	 */
	public function actions()
	{
		return array(
			'uploadFile'=>array(
				'class'=>'ext.actions.XHEditorUpload',
				'rootDir'=>$this->module->editorUploadRootDir,
				'dirStructure'=>$this->module->editorUploadDirStructure,
				'maxSize'=>$this->module->editorUploadMaxSize,
				'allowedExtensions'=>$this->module->editorUploadAllowedExtensions,
			),
		);
	}
}