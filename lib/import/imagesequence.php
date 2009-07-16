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


/* Imports a set of numbered images as a video resource */

class ImageSequenceImport extends ImportMarshal
{
	public static $imageFormats = array(
		'png' => 'image/png',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'tga' => 'image/targa',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
	);

	public static function canImport($sourceInfo)
	{
		if(!is_array($sourceInfo)) return false;
		if(!isset($sourceInfo['type']))
		{
			$ext = strtolower($sourceInfo['ext']);
			if(!isset(self::$imageFormats[$ext]))
			{
				return false;
			}
			$sourceInfo['type'] = self::$imageFormats[$ext];
		}
		if(!isset($sourceInfo['fps']))
		{
			$sourceInfo['fps'] = 0;
		}
		if(!isset($sourceInfo['lossy']))
		{
			switch($sourceInfo['type'])
			{
				case 'image/jpeg':
				case 'image/gif':
					$sourceInfo['lossy'] = true;
					break;
				default:
					$sourceInfo['lossy'] = false;
					break;
			}
		}
		if(!isset($sourceInfo['source']))
		{
			$sourceInfo['source'] = null;
		}
		if(!isset($sourceInfo['origSource']))
		{
			$sourceInfo['origSource'] = null;
		}
		if(!isset($sourceInfo['seqformat']))
		{
			$sourceInfo['seqformat'] = $sourceInfo['base'] . str_repeat('?', $sourceInfo['seqdigits']) . '.' . $sourceInfo['ext'];
		}
		if(!isset($sourceInfo['title']))
		{
			$sourceInfo['title'] = basename($sourceInfo['dir']);
		}
		$xy = getimagesize($sourceInfo['first']);
		$sourceInfo['width'] = $xy[0];
		$sourceInfo['height'] = $xy[1];
		if(isset($xy['channels']))
		{
			switch($xy['channels'])
			{
				case 3:
					$sourceInfo['chroma'] = 'rgb';
					break;
				case 4:
					$sourceInfo['chroma'] = 'cmyk';
					break;
			}
		}
		if(isset($xy['bits']))
		{
			$sourceInfo['depth'] = $xy['bits'];
		}
		if(!isset($sourceInfo['chroma']))
		{
			$sourceInfo['chroma'] = 'rgb';
		}
		if(!isset($sourceInfo['frames']))
		{
			$sourceInfo['frames'] = 0;
		}
		$sourceInfo['_printf'] = sprintf('%%0%dd', $sourceInfo['seqdigits']);
		$sourceInfo['_base'] = $sourceInfo['base'];
		$sourceInfo['_dir'] = $sourceInfo['dir'];
		$sourceInfo['_ext'] = $sourceInfo['ext'];
		$sourceInfo['_firstIndex'] = $sourceInfo['firstIdx'];
		unset($sourceInfo['first']);
		unset($sourceInfo['dir']);
		unset($sourceInfo['ext']);
		unset($sourceInfo['base']);
		unset($sourceInfo['seqdigits']);
		unset($sourceInfo['first']);
		unset($sourceInfo['firstIdx']);
		return $sourceInfo;
	}
	
	public function __construct($db, $source, $data = null)
	{
		parent::__construct($db, $source, $data, 'video');
	}
	
	public function import()
	{
		if(empty($this->data['frames']))
		{
			echo "Image sequence import: Counting frames...\n";
			$c = intval($this->data['_firstIndex']);
			$nframes = 0;
			while(true)
			{
				$testname = $this->data['_dir'] . '/' . $this->data['_base'] . sprintf($this->data['_printf'], $c) . '.' . $this->data['_ext'];
				if(!file_exists($testname))
				{
					break;
				}
				$nframes++;
				$c++;
			}
			$this->data['frames'] = $nframes;
			echo $nframes . " found.\n";
		}
		parent::import();
	}
}
