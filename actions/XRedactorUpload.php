<?php
/**
 * XRedactorUpload action
 *
 * This action enables Imperavi Redactor to upload files to server.
 *
 * The following shows how to use XRedactorUpload action.
 *
 * First set up uploadFile action on RequestController actions() method:
 * <pre>
 * return array(
 *     'uploadFile'=>array(
 *         'class'=>'ext.actions.XRedactorUpload',
 *     ),
 * );
 * </pre>
 *
 * And then in the view configure XHEditor widget as follows:
 * <pre>
 * $this->widget('ext.widgets.redactor.ImperaviRedactor',array(
 *     'model'=>$model,
 *     'modelAttribute'=>'content',
 *     'config'=>array(
 *         'fileUpload'=>$this->createUrl('/request/uploadFile'),
 *         'fileMaxSize'=>6291456,
 *         // other options
 *     ),
 * ));
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XRedactorUpload extends CAction
{
	/**
	 * @var string the name of the root directory where files are uploaded
	 * Defaults to 'upload/redactor/'
	 */
	public $uploadDir='upload/redactor/';
	/**
	 * @var integer the maximum upload size for files.
	 * Defaults to 6291456 (=6MB)
	 */
	public $maxFileSize=6291456;
	/**
	 * @var string the list of file types that are allowed to be uploaded
	 */
	public $allowedTypes=array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');

	/**
	 * Run action
	 */
	public function run()
	{
		$response=array();

		if(!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK)
			$response['error']='An error occurred during file upload.';
		else
		{
			$file=$_FILES['file'];

			if($file['size'] > $this->maxFileSize)
				$response['error']='File is too large.';

			if (!in_array($file['type'], $this->allowedTypes))
				$response['error']='Invalid file type.';

			// Generate a unique file name to avoid overwriting existing files
			$filename=uniqid() . '_' . basename($file['name']);
			$uploadFile=$this->uploadDir . $filename;

			// Move the uploaded file to the upload directory
			if(!isset($response['error']) && !move_uploaded_file($file['tmp_name'], $uploadFile))
				$response['error']='Failed to move uploaded file.';

			// Set the file URL in the response if there are no errors
			if(!isset($response['error']))
				$response['filelink']=Yii::app()->baseUrl.'/'.$this->uploadDir.$filename; // Adjust the URL according to your needs
		}

		header('Content-Type: application/json');
		echo json_encode($response);
	}
}