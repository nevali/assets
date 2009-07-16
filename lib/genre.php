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

class GenreAsset extends Asset
{
	protected $parent;
	public $title;
	public $description;
	
	public static function create($db, $class = '')
	{
		if($class == '') $class = 'genre';
		return parent::create($db, $class);
	}
	
	protected function init(&$data)
	{
		parent::init($data);
		$this->assetRefs += array('parent');
		$this->propMap['asset_genre'] = array(
			'parent' => 'genre_parent',
			'title' => 'genre_title',
			'description' => 'genre_description',
			);
	}		
}

class MediaTypeAsset extends GenreAsset
{
	public static function create($db, $class = '')
	{
		if($class == '') $class = 'mediatype';
		return parent::create($db, $class);
	}
	
	protected function init(&$data)
	{
		parent::init($data);
		unset($this->propMap['asset_genre']);
		$this->propMap['asset_mediatype'] = array(
			'parent' => 'mediatype_parent',
			'title' => 'mediatype_title',
			'description' => 'mediatype_description',
			);
	}
}

class FormatAsset extends GenreAsset
{
	public static function create($db, $class = '')
	{
		if($class == '') $class = 'format';
		return parent::create($db, $class);
	}
	
	protected function init(&$data)
	{
		parent::init($data);
		unset($this->propMap['asset_genre']);
		$this->propMap['asset_format'] = array(
			'parent' => 'format_parent',
			'title' => 'format_title',
			'description' => 'format_description',
			);
	}
}

