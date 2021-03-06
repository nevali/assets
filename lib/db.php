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

class DBException extends Exception
{
	public $errMsg;
	public $query;
	public $code;
	
	public function __construct($errCode, $errMsg, $query)
	{
		$this->errMsg = $errMsg;
		$this->query = $query;
		parent::__construct($errMsg . ' while executing: ' . $query, $errCode);
	}
}

interface IDBCore
{
	public function __construct($params);
	public function vquery($query, $params);
	public function query($query);
	public function exec($query);
	public function getOne($query);
	public function getRow($query);
	public function getAll($query);
	public function insert($table, $kv);
	public function update($table, $kv, $clause);
	public function quoteObject($name);
	public function quoteObjectRef(&$name);
	public function quoteRef(&$value);
	public function quote($value);
	public function insertId();
	public function begin();
	public function rollback();
	public function commit();
}

abstract class DBCore implements IDBCore
{
	protected $rsClass;
	
	public static function connect($iri)
	{
		if(!is_array($iri))
		{
			$iri = parse_url($iri);
		}
		if(!isset($iri['dbname']))
		{
			$iri['dbname'] = null;
			$x = explode('/', $iri['path']);
			foreach($x as $p)
			{
				if(strlen($p))
				{
					$iri['dbname'] = $p;
					break;
				}
			}
		}
		switch($iri['scheme'])
		{
			case 'mysql':
				return new MySQL($iri);
			default:
				throw new DBException('Unsupported database connection scheme "' . $iri['scheme'] . '"', 'FAIL', null);
		}
	}
	
	public function begin()
	{
		$this->execute('START TRANSACTION');
	}
	
	public function rollback()
	{
		$this->execute('ROLLBACK');
	}
	
	public function vquery($query, $params)
	{
		if(!is_array($params)) $params = array();
		$sql = preg_replace('/\?/e', "\$this->quote(array_shift(\$params))", $query);
		return $this->execute($sql);
	}
	
	public function exec($query)
	{
		$params = func_get_args();
		array_shift($params);
		if($this->vquery($query, $params))
		{
			return true;
		}
		return false;
	}

	/* $rs = $inst->query('SELECT * FROM `sometable` WHERE `field` = ? AND `otherfield` = ?', $something, 27); */
	public function query($query)
	{
		$params = func_get_args();
		array_shift($params);
		if(($r =  $this->vquery($query, $params)))
		{
			return new $this->rsClass($this, $r);
		}
		return null;
	}

	public function getOne($query)
	{
		$row = null;
		$params = func_get_args();
		array_shift($params);
		if(($r =  $this->vquery($query, $params)))
		{
			$rs = new $this->rsClass($this, $r);
			$row = $rs->next();
			$rs = null;
			if($row)
			{
				foreach($row as $v)
				{
					return $v;
				}
			}
		}
		return null;
	}

	public function getRow($query)
	{
		$row = null;
		$params = func_get_args();
		array_shift($params);
		if(($r =  $this->vquery($query, $params)))
		{
			$rs = new $this->rsClass($this, $r);
			$row = $rs->next();
			$rs = null;
		}
		return $row;
	}

	public function getAll($query)
	{
		$rows = null;
		$params = func_get_args();
		array_shift($params);
		if(($r =  $this->vquery($query, $params)))
		{
			$rows = array();
			$rs = new $this->rsClass($this, $r);
			while(($row = $rs->next()))
			{
				$rows[] = $row;
			}
			$rs = null;
		}
		return $rows;
	}
	
	protected function reportError($errcode, $errmsg, $sqlString)
	{
		throw new DBException($errcode, $errmsg, $sqlString);
	}
	
	public function insert($table, $kv)
	{
		$keys = array_keys($kv);
		$klist = array();
		foreach($keys as $k)
		{
			if(substr($k, 0, 1) == '@')
			{
				$values[] = $kv[$k];
				$klist[] = substr($k, 1);
			}
			else
			{
				$klist[] = $this->quoteObject($k);
				$values[] = $this->quote($kv[$k]);
			}
		}
		$sql = 'INSERT INTO ' . $this->quoteObject($table) . ' (' . implode(',', $klist) . ') VALUES (' . implode(',', $values) . ')';
		return $this->execute($sql);
	}
	
	public function now()
	{
		return $this->quote(strftime('%Y-%m-%d %H:%M:%S'));
	}
		
	public function update($table, $kv, $clause)
	{
		$sql = 'UPDATE ' . $this->quoteObject($table) . ' SET ';
		$keys = array_keys($kv);
		foreach($keys as $k)
		{
			if(substr($k, 0, 1) == '@')
			{
				$v = $kv[$k];
				$sql .= substr($k, 1) . ' = ' . $v . ', ';
			}
			else
			{
				$sql .= $this->quoteObject($k) . ' = ' . $this->quote($kv[$k]) . ', ';
			}
		}
		$sql = substr($sql, 0, -2);
		if(is_string($clause) && strlen($clause))
		{
			$sql .= ' WHERE ' . $clause;
		}
		else if(is_array($clause) && count($clause))
		{
			$sql .= ' WHERE ';
			foreach($clause as $key => $value)
			{
				$sql .= $this->quoteObject($key) . ' = ' . $this->quote($value) . ' AND ';
			}
			$sql = substr($sql, 0, -4);
		}
		return $this->execute($sql);
	}
	
	public function quoteObject($name)
	{
		$this->quoteObjectRef($name);
		return $name;
	}
	
	public function quote($value)
	{
		$this->quoteRef($value);
		return $value;
	}
		
	public function quoteObjectRef(&$name)
	{
		$name = '"' . $name . '"';
	}

}

/* while(($row = $rs->next())) { ... } */
class DBDataSet
{
	public $fields = array();
	public $EOF = true;
	public $db;
	protected $resource;
	
	public function __construct($db, $resource)
	{
		$this->db = $db;
		$this->resource = $resource;
		$this->EOF = false;
	}
	
	public function next()
	{
		if($this->EOF) return false;
		if(!$this->getRow())
		{
			$this->EOF = true;
			return null;
		}
		return $this->fields;
	}
}

class MySQL extends DBCore
{
	protected $rsClass = 'MySQLSet';
	protected $mysql;
	
	public function __construct($params)
	{
		if(!($this->mysql = mysql_connect($params['host'], $params['user'], $params['pass'])))
		{
			$this->raiseError(null);
		}
		if(!mysql_select_db($params['dbname'], $this->mysql))
		{
			$this->raiseError(null);
		}
		$this->execute("SET NAMES 'utf8'");
		$this->execute("SET sql_mode='ANSI'");
		$this->execute("SET storage_engine='InnoDB'");
		$this->execute("SET time_zone='+00:00'");
	}
	
	protected function execute($sql)
	{
		$r = mysql_query($sql, $this->mysql);
		if($r === false)
		{
			$this->raiseError($sql);
		}
		return $r;
	}
	
	protected function raiseError($query)
	{
		return $this->reportError(mysql_errno($this->mysql), mysql_error($this->mysql), $query);
	}
	
	public function quoteRef(&$string)
	{
		if(is_null($string))
		{
			$string = 'NULL';
		}
		else if(is_bool($string))
		{
			$string = ($string ? "'Y'" : "'N'");
		}
		else
		{
			$string = "'" . mysql_real_escape_string($string, $this->mysql) . "'";
		}
	}
		
	public function getRow($query)
	{
		$row = null;
		$params = func_get_args();
		array_shift($params);
		if(($r =  $this->vquery($query . ' LIMIT 1', $params)))
		{
			$row = mysql_fetch_assoc($r);
		}
		return $row;
	}
	
	public function insertId()
	{
		return mysql_insert_id($this->mysql);
	}

	public function commit()
	{
		try
		{
			$this->execute('COMMIT');
		}
		catch(DBException $e)
		{
			if($e->code == 1213)
			{
				/* 1213 (ER_LOCK_DEADLOCK) Transaction deadlock. You should rerun the transaction. */
				return false;
			}
			else
			{
				/* An error which doesn't imply that the transaciton should be
				 * automatically retried should be thrown, rather than
				 * returning false. This allows transactions to be
				 * contained within a do { … } while(!$db->commit()) block.
				 */
				throw $e;
			}
		}
		return true;
	}

	public function now()
	{
		return 'NOW()';
	}

}

class MySQLSet extends DBDataSet
{
	protected function getRow()
	{
		return ($this->fields = mysql_fetch_assoc($this->resource));
	}
}
