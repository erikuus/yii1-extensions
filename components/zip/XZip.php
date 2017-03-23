<?php
/**
 * XZip class file.
 *
 * XZip component enables to add/extract files to zip archive
 *
 * The following shows how to use XZip component.
 *
 * Configure component:
 * <pre>
 * 'components'=>array(
 *     'zip'=> array(
 *         'class'=>'ext.components.zip.XZip',
 *     )
 * )
 * </pre>
 *
 * ADD DOCUMENT
 * <pre>
 * $destination='path/to/file.zip';
 * $source=array(
 *     'path/to/file.jpg',
 *     'path/to/file.png',
 *     'path/to/file.pdf'
 * );
 * Yii::app()->zip->make($source,$destination);
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XZip extends CApplicationComponent
{
	/**
	 * Get information about zip archive
	 * @param string $src path to zip archive
	 * @param boolean $data whether to return data
	 * @return array
	 */
	public function info($src, $data=true)
	{
		if(($zip=zip_open(realpath($src))))
		{
			while(($zip_entry=zip_read($zip)))
			{
				$path=zip_entry_name($zip_entry);
				if(zip_entry_open($zip,$zip_entry,"r"))
				{
					$content[$path]=array(
						'Ratio'=>zip_entry_filesize($zip_entry) ? round(100-zip_entry_compressedsize($zip_entry)/zip_entry_filesize($zip_entry)*100,1) : false,
						'Size'=>zip_entry_compressedsize($zip_entry),
						'NormalSize'=>zip_entry_filesize($zip_entry)
					);

					if($data)
						$content[$path]['Data']=zip_entry_read($zip_entry,zip_entry_filesize($zip_entry));

					zip_entry_close($zip_entry);
				}
				else
					$content[$path]=false;
			}
			zip_close($zip);
			return $content;
		}
		return false;
	}

	/**
	 * Extract zip archive
	 * @param string $src the path to zip archive
	 * @param string $dest the location where to extract the files
	 * @return boolean whether extraction succeeded
	 */
	public function extract($src,$dest)
	{
		$zip=new ZipArchive();
		if($zip->open($src)===true)
		{
			$zip->extractTo($dest);
			$zip->close();
			return true;
		}
		return false;
	}

	/**
	 * Make zip archive
	 * @param mixed $src the path or array of paths to files that make zip
	 * @param string $dest the location where to make zip archive
	 * @return boolean whether making zip succeeded
	 */
	public function make($src,$dest)
	{
		$zip=new ZipArchive();
		$src=is_array($src) ? $src : array($src);

		if($zip->open($dest,ZipArchive::CREATE)===true)
		{
			foreach($src as $item)
			{
				if(file_exists($item))
					$zip->addFile($item, basename($item));
			}
			$zip->close();
			return true;
		}
		return false;
	}
}
?>