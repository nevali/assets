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


/* Import TV-Anytime classification scheme XML */

abstract class TVAImport extends ImportMarshal
{
	protected static function canImportWithPrefix($source, $nsp)
	{
		$data = array();
		if(!is_file($source))
		{
			return null;
		}
		if((@$xml = simplexml_load_file($source)))
		{
			$data['_xml'] = $xml;
			if($xml->getName() == 'ClassificationScheme')
			{
				$attrs = $xml->attributes();
				$s = trim($attrs->uri);
				$data['_uri'] = $s;
				if(0 == strncmp($s, $nsp, strlen($nsp)))
				{
					return $data;
				}
			}
		}
		return false;
	}

	public function import()
	{
		foreach($this->data['_xml']->Term as $node)
		{
			$this->importTerm($node, null, $this->data['_uri']);
		}
	}
	
	protected function importTerm($node, $parentKey, $uriBase)
	{
		$attrs = $node->attributes();
		$id = strval($attrs->termID);
		if(!strlen($id))
		{
			echo "Warning: Skipping Term with no termID\n";
			return;
		}
		$uri = $uriBase . ':' . $id;
		echo "Asset URL is " . $uri . "\n";
		$name = strval($node->Name);
		echo "Name is " . $name . "\n";
		$defn = strval($node->Definition);
		echo "Definition is " . $defn . "\n";
		if(!($asset = Asset::getByURL($this->db, $uri)))
		{
			$asset = Asset::create($this->db, $this->classname);
			echo "Created new term with key " . $asset->key . "\n";
			$asset->title = $name;
			$asset->description = $defn;
			$asset->url[] = $uri;
			if($parentKey)
			{
				$asset->parent = $parentKey;
			}
			$asset->commit();
		}
		else
		{
			echo "Matched existing term with key " . $asset->key . "\n";
		}
		foreach($node->Term as $term)
		{
			$this->importTerm($term, $asset->key, $uriBase);
		}
	}
}

class TVAGenreImport extends TVAImport
{
	public static function canImport($source)
	{
		return self::canImportWithPrefix($source, 'urn:tva:metadata:cs:ContentCS:');
	}
	
	public function __construct($db, $source, $data = null)
	{
		parent::__construct($db, $source, $data, 'genre');
	}
	
}

class TVAFormatImport extends TVAImport
{
	public static function canImport($source)
	{
		return self::canImportWithPrefix($source, 'urn:tva:metadata:cs:FormatCS:');
	}
	
	public function __construct($db, $source, $data = null)
	{
		parent::__construct($db, $source, $data, 'format');
	}
}

class TVAMediaTypeImport extends TVAImport
{
	public static function canImport($source)
	{
		return self::canImportWithPrefix($source, 'urn:tva:metadata:cs:MediaTypeCS:');
	}
	
	public function __construct($db, $source, $data = null)
	{
		parent::__construct($db, $source, $data, 'mediatype');
	}
}

