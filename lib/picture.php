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


require_once('object.php');

class PictureAsset extends Asset
{
	protected $source;
	protected $origSource;
	public $title;
	public $lossy;
	public $type;
	public $copyright;
	public $license;
	public $width;
	public $height;
	public $depth;
	public $chroma;
	public $filename;
	
	public static function create($db, $class = '')
	{
		if($class == '') $class = 'picture';
		return parent::create($db, $class);
	}
	
	protected function init(&$data)
	{
		parent::init($data);
		$this->assetRefs += array('source', 'origSource');
		$this->propMap['asset_picture'] = array(
			'source' => 'picture_source',
			'origSource' => 'picture_orig_source',
			'title' => 'picture_title',
			'lossy' => 'picture_lossy',
			'type' => 'picture_type',
			'copyright' => 'picture_copyright',
			'license' => 'picture_license',
			'width' => 'picture_xres',
			'height' => 'picture_yres',
			'depth' => 'picture_depth',
			'chroma' => 'picture_chroma',
			'filename' => 'picture_filename',
			);
	}		

	protected function initProperties($source, $map = null)
	{
		parent::initProperties($source, $map);
		if($this->lossy == 'Y')
		{
			$this->lossy = true;
		}
		else if($this->lossy == 'N')
		{
			$this->lossy = false;
		}
	}
}
