<?php

/**
 * XAjaxUpload class file.
 * This extension is a wrapper of http://valums.com/ajax-upload/
 *
 * @author Vladimir Papaev <kosenka@gmail.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Renamed XAjaxUpload class and property names
 * Restructured code
 * Added htmlOptions property
 * Added better comments
 * Added buttonLabel and buttonClass options for fileuploader.js
 * Added saveAsFilename property for class qqFileUploader (qqFileUploader.php)
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 2.0
 */

/*
EXAMPLE OF USAGE:
In form:

<?php echo $form->labelEx($model,'Photo'); ?>
<?php $this->widget('ext.widgets.ajaxupload.XAjaxUpload', array(
	'id'=>'image',
	'options'=>array(
		'action'=>Yii::app()->createUrl('/controller/upload'),
		'allowedExtensions'=>array('jpg', 'jpeg', 'png', 'gif'),
		'sizeLimit'=>2*1024*1024,// maximum file size in bytes
		'onSubmit'=>"js:function(file, extension) {
			$('div.preview').addClass('loading');
		}",
		'onComplete'=>"js:function(file, response, responseJSON) {
			$('div.preview').removeClass('loading');
			$('#".CHtml::activeId($model,'Photo')."').val(responseJSON['filename']);
			$('#photo').attr('src', 'pathToDir'+responseJSON['filename']);
		}",
		'messages'=>array(
			'typeError'=>"{file} has invalid extension. Only {extensions} are allowed.",
			'sizeError'=>"{file} is too large, maximum file size is {sizeLimit}.",
			'emptyError'=>"{file} is empty, please select files again without it.",
			'onLeave'=>"The files are being uploaded, if you leave now the upload will be cancelled."
		),
	  )
)); ?>
<?php echo CHtml::image('image.jpg', 'alternative text', array('id'=>'photo'))?>
<?php echo $form->textField($model,'Photo'); ?>
<?php echo $form->error($model,'Photo'); ?>

In controller:

public function actionUpload()
{
	Yii::import("ext.widgets.ajaxupload.qqFileUploader");
	$folder='upload/'; // folder for uploaded files
	$allowedExtensions = array('jpg'); //array("jpg","jpeg","gif","exe","mov" and etc...
	$sizeLimit = 10 * 1024 * 1024; // maximum file size in bytes
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($folder);
	$result=htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	echo $result; // it's array
}
*/
class XAjaxUpload extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 *
	 * NOTE! If you set it false without specifying your own style definitions
	 * widget may not work as intended.
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