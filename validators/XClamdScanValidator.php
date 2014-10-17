<?php

/**
 * @copyright Copyright (c) 2012 Rodolfo Gonzalez.
 * @license Dual licenced: GNU GPL 2.0 or later or MIT, at your choice.
 *
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.opensource.org/licenses/gpl-2.0.php
 *
 * The MIT license:
 *
 * Copyright (c) 2012 Rodolfo Gonzalez.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * The GPL license:
 *
 * Copyright (C) 2012 Rodolfo Gonzalez.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * XClamdScanFilter scans a file using the STREAM command of ClamAV's clamd.
 *
 * @author Rodolfo Gonzalez <metayii.framework@gmail.com>
 * @license MIT and GPL 2.0
 * @version 1.3
 */
class XClamdScanValidator extends CValidator
{
   /**
    * @var string clamd host.
    */
   public $host = 'tcp://127.0.0.1';

   /**
    * @var integer clamd port.
    */
   public $port = 3310;

   /**
    * @var double max. stream size (file size).
    */
   public $maxStreamSize = 26843545600;

   /**
    * @var boolean whether to mark invalid file on error.
    */
   public $invalidOnError = false;

   /**
    * @var string the error message when invalidOnError.
    */
   public $invalidOnErrorMessage = null;

   /**
    * @var integer max. number of files to scan (not in a package, but on
    * multi file uploads.
    */
   public $maxFiles = 1;

   /**
    * @var boolean whether to allow
    */
   public $allowEmpty = false;

	/**
	 * Set the attribute and then validates using {@link validateFile}.
	 * If there is any error, the error message is added to the object.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		if($this->maxFiles > 1) {
			$files = $object->$attribute;
			if (!is_array($files) || !isset($files[0]) || !$files[0] instanceof CUploadedFile) {
				$files = CUploadedFile::getInstances($object, $attribute);
         }
         var_dump($files);
         if (array()===$files) {
            $this->emptyAttribute($object, $attribute);
            return;
         }
         foreach($files as $file) {
				$this->validateFile($object, $attribute, $file);
         }
		}
		else {
			$file = $object->$attribute;
			if (!$file instanceof CUploadedFile) {
				$file = CUploadedFile::getInstance($object, $attribute);
				if (null===$file) {
               $this->emptyAttribute($object, $attribute);
					return;
            }
			}
			$this->validateFile($object, $attribute, $file);
		}
	}

   /**
    * Check the file.
    *
    * @param Object $object the model object.
    * @param string $attribute the attribute name.
    * @param CFileUpload $file instance of the file to scan.
    * @return null
    */
   public function validateFile($object, $attribute, $file)
   {
      $chunksize = (64 * (1024 * 1024)) - 1;

      if (!is_readable($file->tempName)) {
         if ($this->invalidOnError) {
            $message = $this->invalidOnErrorMessage!==null ? $this->invalidOnErrorMessage : Yii::t('XClamdScanValidator','{attribute} is not a readable file.');
            $this->addError($object, $attribute, $message);
         }
         return;
      }

      if (filesize($file->tempName) >= $this->maxStreamSize) {
         return;
      }

      if (($f = @fopen($file->tempName, 'rb')) !== false) {
         ob_start();
         if ($socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5)) {
            @stream_set_timeout($socket, 120);
            @fwrite($socket, "STREAM\n");
            if (!feof($socket)) {
               $port = HString::trim(fgets($socket, 128));
            }
            if (preg_match("/PORT ([\d]{1,5})$/", $port, $matches)) {
               $port = $matches[1];
            }
            ob_end_flush();
            if ($scanner = @fsockopen($this->host, $port, $errno, $errstr, 5)) {
               $i = 0;
               while (!feof($f)) {
                  @fwrite($scanner, @fread($f, $chunksize));
               }
               @fclose($scanner);
               @fclose($f);
               $result = '';
               while (!feof($socket)) {
                  $result .= @fgets($socket);
               }
               @fclose($socket);
               if (preg_match("/: (.*) FOUND.*$/", $result, $found)) {
                  $message = $this->message!==null ? str_replace('[virus]', $found[1], str_replace('[file]', $file->name, $this->message)) : Yii::t('XClamdScanValidator','Malware detected in file: {v}', array('{v}'=>$found[1]));
                  $this->addError($object, $attribute, $message);
               }
               elseif (preg_match("/: OK.*$/", $result)) {
                  return;
               }
               else {
                  if ($this->invalidOnError) {
                     $message = $this->invalidOnErrorMessage!==null ? $this->invalidOnErrorMessage : Yii::t('XClamdScanValidator','Can not scan the file 1.');
                     $this->addError($object, $attribute, $message);
                  }
               }
            }
         }
         else {
            if ($this->invalidOnError) {
               $message = $this->invalidOnErrorMessage!==null ? $this->invalidOnErrorMessage : Yii::t('XClamdScanValidator','Can not scan the file 2.');
               $this->addError($object, $attribute, $message);
            }
         }
      }
      elseif ($this->invalidOnError) {
         $message = $this->invalidOnErrorMessage!==null ? $this->invalidOnErrorMessage : Yii::t('XClamdScanValidator','{attribute} is not a readable file.');
         $this->addError($object, $attribute, $message);
      }
   }

	/**
	 * Raises an error to inform end user about blank attribute.
	 * @param CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function emptyAttribute($object, $attribute)
	{
		if (!$this->allowEmpty) {
			$message = $this->message!==null ? $this->message : Yii::t('XClamdScanValidator','{attribute} cannot be blank.');
			$this->addError($object, $attribute, $message);
		}
	}
}
