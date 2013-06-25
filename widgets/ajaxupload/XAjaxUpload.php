<?php

/**
 * XAjaxUpload class file.
 *
 * This widget enables file uploads via ajax
 * This extension is a wrapper of http://valums.com/ajax-upload/
 *
 * Original version by Vladimir Papaev <kosenka@gmail.com>
 *
 * Changes to original version:
 * renamed XAjaxUpload class and property names,
 * restructured code,
 * added htmlOptions property,
 * added better comments,
 * added buttonLabel and buttonClass options for fileuploader.js,
 * added saveAsFilename property for class qqFileUploader (qqFileUploader.php).
 *
 * Example:
 *
 * Inside CActiveForm:
 *
 * <pre>
 * echo $form->labelEx($model,'Photo');
 * $this->widget('ext.widgets.ajaxupload.XAjaxUpload', array(
 *     'id'=>'image',
 *     'options'=>array(
 *         'action'=>Yii::app()->createUrl('/controller/upload'),
 *         'allowedExtensions'=>array('jpg', 'jpeg', 'png', 'gif'),
 *         'sizeLimit'=>2*1024*1024, // maximum file size in bytes
 *         'onSubmit'=>"js:function(file, extension) {
 *             $('div.preview').addClass('loading');
 *         }",
 *         'onComplete'=>"js:function(file, response, responseJSON) {
 *             $('div.preview').removeClass('loading');
 *             $('#".CHtml::activeId($model,'Photo')."').val(responseJSON['filename']); // add filename to hidden input
 *             $('#photo').attr('src', '/app/upload/'+responseJSON['filename']); // change image on page
 *         }",
 *         'messages'=>array(
 *             'typeError'=>Yii::t('vd','{file} has invalid extension. Only {extensions} are allowed.'),
 *             'sizeError'=>Yii::t('vd','{file} is too large, maximum file size is {sizeLimit}.'),
 *             'emptyError'=>Yii::t('vd','{file} is empty, please select files again without it.'),
 *             'onLeave'=>Yii::t('vd','The files are being uploaded, if you leave now the upload will be cancelled.')
 *         ),
 *     )
 * ));
 * echo CHtml::image('image.jpg', 'alternative text', array('id'=>'photo'));
 * echo $form->hiddenField($model,'Photo');
 * echo $form->error($model,'Photo');
 * </pre>
 *
 * In controller:
 *
 * <pre>
 * public function actionUpload()
 * {
 *     Yii::import("ext.widgets.ajaxupload.qqFileUploader");
 *     $folder='upload/'; // folder for uploaded files
 *     $allowedExtensions = array('jpg'); // array('jpg','jpeg','gif','exe','mov' and etc...
 *     $sizeLimit = 10 * 1024 * 1024; // maximum file size in bytes
 *     $fileName = 'new_filename'; // if not set, original filename is used
 *     $uploader = new qqFileUploader($allowedExtensions, $sizeLimit, $fileName);
 *     $result = $uploader->handleUpload($folder);
 *     $result = htmlspecialchars(json_encode($result), ENT_NOQUOTES);
 *     echo $result;
 * }
 * </pre>
 *
 * Above method will return
 * - on success: array('success'=>true,'filename'=>$filename.'.'.$ext);
 * - on failure: array('error'=>'Could not save uploaded file.'.'The upload was cancelled, or server error encountered');
 *
 * @author Vladimir Papaev <kosenka@gmail.com>
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */

class XAjaxUpload extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;

	/**
	 * @var array HTML attributes for the container tag
	 */
	public $htmlOptions=array();

	/**
	 * @var array additional options that can be passed to the constructor of the js object.
	 * You can set following options:
	 * buttonLabel: 'Upload File',
	 * buttonClass: null,
	 * debug: false,
	 * action: '/server/upload',
	 * params: {},
	 * button: null,
	 * multiple: true,
	 * maxConnections: 3,
	 * allowedExtensions: [],
	 * sizeLimit: 0,
	 * minSizeLimit: 0,
	 * onSubmit: function(id, fileName){},
	 * onProgress: function(id, fileName, loaded, total){},
	 * onComplete: function(id, fileName, responseJSON){},
	 * onCancel: function(id, fileName){},
	 * messages: {
	 *     typeError: "{file} has invalid extension. Only {extensions} are allowed.",
	 *     sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
	 *     minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
	 *     emptyError: "{file} is empty, please select files again without it.",
	 *     onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
	 * },
	 * showMessage: function(message){
	 *     alert(message);
	 * }
	 */
	public $options=array();

	/**
	 * @var array additional post params for upload request.
	 */
	public $postParams=array();

	/**
	 * Initializes the widget.
	 * Publish and register client files
	 */
	public function init()
	{
		if(isset($this->htmlOptions['id']))
			$this->id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$this->id;

		$this->registerClientScript();
		$this->registerClientScriptFiles();
	}

	/**
	 * Render widget tag.
	 */
	public function run()
	{
		echo CHtml::tag('div',$this->htmlOptions, null)."\n";
	}

	/**
	 * Register necessary inline client scripts.
	 */
	protected function registerClientScript()
	{
		// prepare options
		$postParams=array('PHPSESSID'=>session_id(),'YII_CSRF_TOKEN'=>Yii::app()->request->csrfToken);
		if(isset($this->postParams))
			$postParams=array_merge($postParams,$this->postParams);

		$options=array(
			'element'=>"js:document.getElementById('{$this->id}')",
			'debug'=>false,
			'multiple'=>false
		);
		$options=array_merge($options,$this->options);
		$options['params']=$postParams;
		$options=CJavaScript::encode($options);

		// register inline javascript
		$script ="var FileUploader_{$this->id} = new qq.FileUploader({$options})";

		if(Yii::app()->request->isAjaxRequest)
			echo CHtml::script($script);
		else
			Yii::app()->clientScript->registerScript(__CLASS__.$this->id, $script, CClientScript::POS_LOAD);
	}

	/**
	 * Publish and register necessary client script files.
	 */
	protected function registerClientScriptFiles()
	{
		$cs=Yii::app()->clientScript;
		$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');

		// register css file
		if($this->cssFile===null)
		{
			$assets=Yii::app()->assetManager->publish(dirname(__FILE__).DIRECTORY_SEPARATOR.'assets');
			$cs->registerCssFile($assets.'/fileuploader.css');
		}
		else if($this->cssFile!==false)
			$cs->registerCssFile($this->cssFile);

		// register js file
		$cs->registerScriptFile($assets.'/fileuploader.js', CClientScript::POS_HEAD);
	}
}