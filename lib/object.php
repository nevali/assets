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

require_once('db.php');
require_once('base32.php');

abstract class Asset
{
	protected static $classmap = array(
		'null' => 'NullAsset',
		'prog' => 'ProgAsset',
		'genre' => 'GenreAsset',
		'mediatype' => 'MediaTypeAsset',
		'format' => 'FormatAsset',
		'video' => 'VideoAsset',
		'picture' => 'PictureAsset',
	);

	protected $db;
	protected $assetRefs;
	protected $propMap;

	public $class;
	public $key;
	public $created;
	public $modified;
	public $url;
	public $hasManifest;
	public $assetPath;
	public $assetURI;
	
	public static function create($db, $class = '')
	{
		$params = func_get_args();
		array_shift($params);
		array_shift($params);
		$class = strtolower(trim($class));
		if(!strlen($class))
		{
			$class = 'null';
		}
		if(!isset(self::$classmap[$class]))
		{
			trigger_error('Asset::create(): object class "' . $class . '" does not exist', E_USER_ERROR);
			return null;
		}
		$className = self::$classmap[$class];
		$db->insert('asset_object', array(
			'object_class' => $class,
			'@object_created' => $db->now(),
			'@object_modified' => $db->now(),
			'object_has_manifest' => false,
		));
		$id = $db->insertId();
		$key = Base32::encode($id);
		$db->update('asset_object', array('object_key' => $key), array('object_id' => $id));
		echo 'Created new asset of class "' . $class . '" with key "' . $key . '"' . "\n";
		call_user_func_array(array($className, 'createObject'), array($db, $key, $params));
		return self::get($db, $key);
	}
	
	public static function get($db, $key)
	{
		if(!($info = $db->getRow('SELECT * FROM "asset_object" WHERE "object_key" = ?', $key)))
		{
			return null;
		}
		if(!isset(self::$classmap[$info['object_class']]))
		{
			trigger_error('Asset::get(): class "' . $info['object_class'] . '" of object "' . $info['object_key'] . ' does not exist', E_USER_ERROR);
			return null;
		}
		$className = self::$classmap[$info['object_class']];
		if($info['object_class'] != 'null')
		{
			if(($extra = $db->getRow('SELECT * FROM "asset_' . $info['object_class'] . '" WHERE "object_key" = ?', $info['object_key'])))
			{
				$info = $info + $extra;
			}
		}
		$info['url'] = array();
		$urls = $db->getAll('SELECT "object_url" FROM "asset_url" WHERE "object_key" = ?', $info['object_key']);
		foreach($urls as $ur)
		{
			$info['url'][] = $ur['object_url'];
		}
		return new $className($db, $info);
	}
	
	public static function getByURL($db, $url)
	{
		if(($key = $db->getOne('SELECT "object_key" FROM "asset_url" WHERE "object_url" = ?', $url)))
		{
			return self::get($db, $key);
		}
		return null;
	}

	
	protected static function createObject($db, $objectKey, $params)
	{
	}
	
	protected function __construct($db, $data)
	{
		$this->db = $db;
		$this->assetRefs = array();
		$this->propMap = array();
		/* Ensure $this->key is available early */
		$this->key = $data['object_key'];
		$this->assetPath = ASSET_DIR . '/' . $this->key . '/';
		if(defined('ASSET_URI'))
		{
			$this->assetURI = ASSET_URI . '/' . $this->key . '/';
		}
		$this->init($data);
		$this->initProperties($data);
	}

	protected function init(&$data)
	{
		$this->propMap['none'] = array();
		$this->propMap['asset_object'] = array(
			'key' => 'object_key',
			'class' => 'object_class',
			'created' => 'object_created',
			'modified' => 'object_modified',
			'url' => 'object_url',
			'hasManifest' => 'object_has_manifest',
		);
	}
	
	protected function initProperties($source, $map = null)
	{
		if(!$map)
		{
			$map = array();
			foreach($this->propMap as $table => $mapping)
			{
				$map += $mapping;
			}
		}
		foreach($map as $pk => $sk)
		{
			if(!isset($source[$sk])) $source[$sk] = null;
			if(in_array($pk, $this->assetRefs))
			{
				$this->$pk = new AssetRef($this->db, $source[$sk]);
			}
			else
			{
				$this->$pk = $source[$sk];
			}
		}
		if($this->hasManifest == 'Y')
		{
			$this->hasManifest = true;
		}
		else if($this->hasManifest == 'N')
		{
			$this->hasManifest = false;
		}
	}
	
	protected function replaceProperties()
	{
		foreach($this->propMap as $table => $mapping)
		{
			if($table == 'none')
			{
				/* Do nothing */
			}
			else if($table == 'asset_object')
			{
				$this->db->exec('UPDATE "asset_object" SET "object_modified" = ' . $this->db->now() . ', "object_has_manifest" = ? WHERE "object_key" = ?', $this->hasManifest, $this->key);
				$this->modified = strftime('%Y-%m-%d %H:%M:%S');
				$this->db->exec('DELETE FROM "asset_url" WHERE "object_key" = ?', $this->key);
				if(is_array($this->url))
				{
					foreach($this->url as $url)
					{
						$this->db->insert('asset_url', array(
							'object_key' => $this->key,
							'object_url' => $url,
						));
					}
				}
			}
			else
			{
				$kv = array('object_key' => $this->key);
				foreach($mapping as $pk => $sk)
				{
					if(is_object($this->$pk))
					{
						$kv[$sk] = $this->$pk->key;
					}
					else
					{
						$kv[$sk] = $this->$pk;
					}
				}
				$this->db->exec('DELETE FROM "' . $table . '" WHERE "object_key" = ?', $this->key);
				$this->db->insert($table, $kv);
			}
		}
	}

	protected function commitManifest()
	{
		if(!$this->hasManifest) return;
		$this->assetPath = ASSET_DIR . '/' . $this->key . '/';
		if(!file_exists($this->assetPath))
		{
			mkdir($this->assetPath);
		}
		$f = fopen($this->assetPath . '/manifest.xml', 'w');
		fwrite($f, '<?xml version="1.0" encoding="UTF-8" ?>' . "\n");
		$this->writeManifest($f);
		fclose($f);
	}
	
	protected function writeManifest($f)
	{
		fwrite($f, '<' . $this->class . ' key="' . $this->key . '" created="' . $this->created . '" modified="' . $this->modified  . '">' . "\n");
		$this->writeManifestArray($f, $this->url, 'url');
		$this->writeManifestProperties($f);
		fwrite($f, '</' . $this->class . '>');
	}

	protected function writeManifestArray($f, $value, $key)
	{
		if(is_array($value) && count($value))
		{
			fwrite($f, "\t" . '<' . $key . ' type="array">' . "\n");
			foreach($value as $v)
			{
				fwrite($f, "\t\t" . '<value>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</value>' . "\n");
			}
			fwrite($f, "\t" . '</' . $key . '>' . "\n");
		}
		else
		{
			fwrite($f, "\t" . '<' . $key . ' type="array" />' . "\n");
		}
	}
	
	protected function writeManifestProperties($f)
	{
		foreach($this->propMap as $src => $map)
		{
			if($src == 'none' || $src == 'asset_object') continue;
			foreach($map as $pk => $sk)
			{
				$v = $this->$pk;
				if(is_array($v))
				{
					$this->writeManifestArray($f, $v, $pk);
				}
				else if(is_object($v))
				{
					if($v->key)
					{
						fwrite($f, "\t" . '<' . $pk . '>' . $v->key . '</' . $pk . '>' . "\n");
					}
					else
					{
						fwrite($f, "\t" . '<' . $pk . ' />' . "\n");
					}
				}
				else if(is_bool($v))
				{
					fwrite($f, "\t" . '<' . $pk . ' type="boolean">' . intval($v) . '</' . $pk . '>' . "\n");
				}
				else
				{
					fwrite($f, "\t" . '<' . $pk . '>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</' . $pk . '>' . "\n");
				}
			}
		}

	}
	
	public function commit()
	{
		$this->replaceProperties();
		$this->commitManifest();
	}
	
	public function __get($name)
	{
		if(in_array($name, $this->assetRefs))
		{
			return $this->$name;
		}
		trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
	}
	
	public function __set($name, $value)
	{
		if(in_array($name, $this->assetRefs))
		{
			if($value != $this->$name->key)
			{
				$this->$name->release();
				$this->$name->key = $value;
			}
			return;
		}
		trigger_error('Undefined or protected property in set: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
	}
}

class AssetRef
{
	public $key;
	protected $object;
	protected $db;
	
	public function __construct($db, $key)
	{
		$this->key = $key;
		$this->db = $db;
	}
	
	public function __get($name)
	{
		if($name == 'object')
		{
			if($this->object == null)
			{
				if($this->key != null)
				{
					$this->object = Asset::get($this->db, $this->key);
				}
			}
			return $this->object;
		}
		trigger_error('Undefined property: ' . get_class($this) . '::$' . $name, E_USER_NOTICE);
	}
	
	public function release()
	{
		$this->object = null;
	}
}

class NullAsset extends Asset
{
}
