<?php

/* Copyright 2009 Mo McRoberts.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The names of the author(s) of this software may not be used to endorse
 *    or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL
 * AUTHORS OF THIS SOFTWARE BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/* Meta-importer for folders: looks inside the folder to see if there's
 * anything interesting in there, and passes it on to the appropriate
 * class if so. The two major cases are manifest.xml files contained
 * within the folder, which describe the contents explicitly, and
 * sequences of images (e.g., PNGs) which make up a video track.
 */ 

class FolderImport extends ImportMarshal
{
	protected $passthrough;
	
	public static function canImport($source)
	{
		if(!is_dir($source)) return false;
		if(file_exists($source . '/manifest.xml'))
		{
			return self::canImportPassThrough('ManifestImport', $source . '/manifest.xml');
		}
		$d = opendir($source);
		/* Scan the directory for something interesting */
		while(($de = readdir($d)))
		{
			$ext = pathinfo($de, PATHINFO_EXTENSION);
			$name = pathinfo($de, PATHINFO_FILENAME);
			if(isset(ImageImport::$imageFormats[strtolower($ext)]))
			{
				$seqdigits = 0;
				$l = strlen($name);
				for($c = $l - 1; $c >= 0; $c--)
				{
					if(ctype_digit($name[$c]))
					{
						$seqdigits++;
					}
					else
					{
						break;
					}
				}
				if($seqdigits < 2)
				{
					continue;
				}
				$format = sprintf('%%0%dd', $seqdigits);
				$base = substr($name, 0, $l - $seqdigits);
				$found = 0;
				$first = null;
				$firstIdx = -1;
				for($c = 0; $c < 10; $c++)
				{
					$testname = $base . sprintf($format, $c) . '.' . $ext;
					if(file_exists($source . '/' . $testname))
					{
						if(!strlen($first)) $first = $source . '/' . $testname;
						if($firstIdx == -1) $firstIdx = $c;
						$found++;
					}
					/* Allow up to 4 failures */
					if($found <= $c - 4) break;
				}
				if($c == 10)
				{
					$info = array(
						'dir' => $source,
						'ext' => $ext,
						'base' => $base,
						'seqdigits' => $seqdigits,
						'first' => $first,
						'firstIdx' => $firstIdx,
					);
					return self::canImportPassThrough('ImageSequenceImport', $info);
				}
			}
		}
	}
	
	protected static function canImportPassThrough($className, $source)
	{
		if(($data = call_user_func(array($className, 'canImport'), $source)))
		{
			if(!is_array($data))
			{
				$data = array();
			}
			$data['_folderImportClass'] = $className;
			return $data;
		}
		return false;
	}
	
	public function __construct($db, $source, $data = null, $classname = 'null')
	{
		$ptClass = $data['_folderImportClass'];
		$this->passthrough = new $ptClass($db, $source, $data, $classname);
	}
	
	public function import()
	{
		return $this->passthrough->import();
	}
}
