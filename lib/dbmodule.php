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

abstract class DBModule
{
	protected $db;
	protected $identifier = null;
	protected $currentVersion = 0;
	protected $globalModule = true;
	
	public function __construct($db)
	{
		$this->db = $db;
	}
	
	public function update()
	{
		if(!isset($this->identifier))
		{
			trigger_error(get_class($this) . '::update(): $this->identifier is unset', E_USER_ERROR);
			return false;
		}
		$ver = $this->getModuleVersion($this->identifier, $this->globalModule);
		do
		{
			$this->db->begin();
			$ver = $this->getModuleVersion($this->identifier, $this->globalModule);
			if($ver >= $this->currentVersion)
			{
				$this->db->rollBack();
				break;
			}
			echo "Updating " . $this->identifier . " from version " . $ver . "\n";
			$newVer = $this->performUpdate($ver);
			if($newVer === true)
			{
				$newVer = $ver + 1;				
			}
			else if(empty($newVer) || $newVer < 0)
			{
				$this->db->rollback();
				trigger_error(get_class($this) . '::update(): Update from version ' . $ver . ' failed', E_USER_ERROR);
				return false;
			}
			$this->db->update('_modules', array(
				'module_version' => $newVer,
				'@module_updated' => $this->db->now(),
			), array(
				'module_ident' => $this->identifier,
			));
			if(!$this->db->commit())
			{
				continue;
			}
		}			
		while($ver < $this->currentVersion);
		return true;
	}

	protected function createModulesTable()
	{
		$this->db->exec('CREATE TABLE IF NOT EXISTS "_modules" ( ' .
			'"module_ident" VARCHAR(64) NOT NULL, ' .
			'"module_version" BIGINT(20) UNSIGNED NOT NULL, ' .
			'"module_updated" DATETIME NOT NULL, ' .
			'"module_global" ENUM(\'N\', \'Y\') NOT NULL default \'N\', ' .
			'PRIMARY KEY ("module_ident"), ' .
			'INDEX "module_global" ("module_global")' .
			')');
	}
	
	protected function getModuleVersion($ident, $global = true)
	{
		$row = array();
		try
		{
			$row = $this->db->getRow('SELECT * FROM "_modules" WHERE "module_ident" = ?', $ident);
		}
		catch(DBException $e)
		{
			if($e->code == 1146)
			{
				$this->createModulesTable();
			}
		}
		if(!isset($row['module_version']))
		{
			do
			{
				$this->db->begin();
				$row = $this->db->getRow('SELECT * FROM "_modules" WHERE "module_ident" = ?', $ident);
				if(!$row)
				{
					$this->db->insert('_modules', array(
						'module_ident' => $ident,
						'module_version' => 0,
						'@module_updated' => $this->db->now(),
						'module_global' => $global
					));
				}
			}
			while(!$this->db->commit());
			$row['module_version'] = 0;
		}
		return $row['module_version'];
	}
	
	abstract protected function performUpdate($oldVersion);
}