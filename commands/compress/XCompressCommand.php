<?php
/**
 * XCompressCommand class file.
 *
 * Compress command allows you to combine and compress CSS and JS Files.
 *
 * To use this command, first map it in protected/config/console.php as follows:
 * return array(
 *     'commandMap' => array(
 *         'compress'=>array(
 *             'class'=>'ext.commands.compress.XCompressCommand',
 *             'dir'=>'path/to/your/application/webroot/css',
 *             'options'=>array('type'=>'css'),
 *             'files'=>array('960.css','main.css','form.css'),
 *             'paramsFile'=>'path/to/your/application/webroot/protected/config/params.php',
 *             'param'=>'compressedCss',
 *         )
 *     )
 * );
 *
 * Now, under protected directory, you can run commands:
 * yiic compress yuicompressor
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XCompressCommand extends CConsoleCommand
{
	private $compressorJar;
	private $temporaryDir;
	/**
	 * @var string the directory to js or css files
	 */
	public $dir;
	/**
	 * @var array filenames of js or css files to be combined and compressed
	 */
	public $files;
	/**
	 * @var array compressor options.
	 */
	public $options;
	/**
	 * @var string application parameters configuration filename.
	 */
	public $paramsFile;
	/**
	 * @var string application parameter name for compressed filename
	 * When files are combined and compressed, application/config/params.php will be modified so that
	 * the resulting file name will be put into this application parameter.
	 */
	public $param;

	public function getHelp()
	{
		$out = "Compress command allows you to combine and compress CSS and JS Files.\n\n";
		return $out.parent::getHelp();
	}

	public function init()
	{
		$this->compressorJar=dirname(__FILE__) . '/yuicompressor-2.4.7.jar';
		$this->temporaryDir=dirname(__FILE__);
	}

	public function actionYuicompressor()
	{
		Yii::import('ext.vendors.compress.*');
		require_once('yuicompressor.php');

		// compress and combine
		$yui = new YUICompressor($this->compressorJar, $this->temporaryDir, $this->options);
		foreach ($this->files as $file)
			$yui->addFile($this->dir.$file);
		$code=$yui->compress();

		// save to file
		$compressedFilename='site-'.date('YmdHis').'.'.$this->options['type'];
		$newFile=$this->dir.$compressedFilename;
		file_put_contents($newFile, $code);

		// overwrite application configuration param
		if($this->paramsFile)
			$this->overwriteParamsFile($compressedFilename);
	}

	protected function overwriteParamsFile($filename)
	{
		$params=require($this->paramsFile);

		if(isset($params[$this->param]))
		{
			$oldFile=$this->dir.$params[$this->param];
			if(file_exists($oldFile))
				unlink($oldFile);
		}

		$params[$this->param]=$filename;
		$array=str_replace("\r",'',var_export($params,true));
		$content="<?php return $array;";
		file_put_contents($this->paramsFile, $content);
	}
}