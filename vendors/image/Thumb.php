<?php
/**
 * Image helper functions
 *
 * @author Chris
 * @link http://con.cept.me
 *
 * @author Erik Uus <erik.uus@gmail.com>
 *
 * CONFIGURATION (config/main.php):
 *	'import'=>array(
 *		'vendors.image.Thumb',
 *	),
 *
 * USAGE:
 * $thumb = Yii::app()->request->baseUrl.Thumb::create(100,100,'images/marilyn-monroe.jpg', array('resizeMethod'=>'resize'));
 * echo CHtml::image($thumb, '');
 */
class Thumb
{
	/**
	 * Create a thumbnail of an image and returns relative path in webroot
	 * the options array is an associative array which can take the values
	 * quality (jpg quality) and method (the method for resizing)
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $img
	 * @param array $options
	 * @return string $path
	 */
	public static function create($width, $height, $img, $options=null)
	{
		if(!file_exists($img))
			throw new CException('Image not found');

		// Defaults for options
		$thumbDir = '.tmb';
		$jpegQuality = 80;
		$resizeMethod = 'adaptiveResize';

		if($options)
			extract($options, EXTR_IF_EXISTS);

		$pathinfo = pathinfo($img);
		$thumbName = 'thumb_'.$resizeMethod.'_'.$width.'_'.$height.'_'.$pathinfo['basename'];
		$thumbPath = $pathinfo['dirname'].'/'.$thumbDir.'/';

		if(!file_exists($thumbPath))
			mkdir($thumbPath);

		if(!file_exists($thumbPath.$thumbName) || filemtime($thumbPath.$thumbName) < filemtime($img)) {
			Yii::import('vendors.image.phpThumb.PhpThumbFactory');
			$options = array('jpegQuality' => $jpegQuality);
			$thumb = PhpThumbFactory::create($img, $options);
			$thumb->{$resizeMethod}($width, $height);
			$thumb->save($thumbPath.$thumbName);
		}

		return '/'.$thumbPath.$thumbName;
	}
}