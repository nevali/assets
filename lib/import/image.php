<?php

class ImageImport extends ImportMarshal
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
		if(!is_array($sourceInfo))
		{
			$source = $sourceInfo;
			$sourceInfo = array();
			$sourceInfo['ext'] = pathinfo($source, PATHINFO_EXTENSION);
		}
		else
		{
			$source = $sourceInfo['file'];
		}
		$ext = strtolower($sourceInfo['ext']);
		if(!isset(self::$imageFormats[$ext]))
		{
			return false;
		}
		$sourceInfo['type'] = self::$imageFormats[$ext];
		if(!($xy = getimagesize($source)))
		{
			return false;
		}
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
		if(!isset($sourceInfo['title']))
		{
			$sourceInfo['title'] = basename($source);
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
		$sourceInfo['_ext'] = $sourceInfo['ext'];
		unset($sourceInfo['ext']);
		unset($sourceInfo['file']);
		return $sourceInfo;
	}
	
	public function __construct($db, $source, $data = null)
	{
		parent::__construct($db, $source, $data, 'picture');
		$this->data['hasManifest'] = true;
		$this->data['filename'] = '';
	}

	public function import()
	{
		parent::import();
		$this->asset->filename = $this->asset->key . '.' . $this->data['_ext'];
		copy($this->source, $this->asset->assetPath . $this->asset->filename);
		$this->asset->commit();
	}
}
