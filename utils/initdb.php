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
 
require(dirname(__FILE__) . '/../lib/common.php');

$db = DBCore::connect(DB_IRI);

class AssetsModule extends DBModule
{
	protected $identifier = 'com.nexgenta.assets';
	protected $currentVersion = 117;
	
	protected function performUpdate($ver)
	{
		if($ver == 0)
		{
			return 100;
		}
		if($ver == 100)
		{
			$this->db->exec('CREATE TABLE "asset_object" (' . 
				' "object_id" BIGINT(20) UNSIGNED NOT NULL auto_increment, ' .
				' "object_key" CHAR(8) DEFAULT NULL, ' .
				' "object_class" VARCHAR(16) NOT NULL, ' .
				' "object_created" DATETIME, ' .
				' "object_modified" DATETIME, ' .
				' PRIMARY KEY ("object_id"), ' .
				' UNIQUE ("object_key"), ' .
				' INDEX ("object_class") ' .
				')');
			return true;
		}
		if($ver == 101)
		{
			$this->db->insert('asset_object', array(
				'object_id' => '343597383680',
				'object_key' => 'a0000000',
				'object_class' => 'null',
				'@object_created' => $this->db->now(),
				'@object_modified' => $this->db->now(),
				));
			return true;
		}
		if($ver == 102)
		{
			$this->db->exec('CREATE TABLE "asset_prog" (' . 
				' "object_key" CHAR(8) NOT NULL, ' .
				' "prog_title" TEXT DEFAULT NULL, ' . 
				' "prog_service" CHAR(8) DEFAULT NULL, ' .
				' "prog_brand" CHAR(8) DEFAULT NULL, ' .
				' "prog_series" CHAR(8) DEFAULT NULL, ' .
				' "prog_genre" CHAR(8) DEFAULT NULL, ' . 
				' "prog_format" CHAR(8) DEFAULT NULL, ' . 
				' "prog_position" INT UNSIGNED DEFAULT NULL, ' .
				' "prog_short_synopsis" TEXT DEFAULT NULL, ' . 
				' "prog_medium_synopsis" TEXT DEFAULT NULL, ' .
				' "prog_long_synopsis" TEXT DEFAULT NULL, ' .
				' PRIMARY KEY ("object_key"), ' .
				' INDEX ("prog_service"), ' .
				' INDEX ("prog_brand"), ' .
				' INDEX ("prog_series"), ' .
				' INDEX ("prog_genre"), ' .
				' INDEX ("prog_format") ' .
				')');
			return true;
		}
		if($ver == 103)
		{
			$this->db->exec('CREATE TABLE "asset_genre" (' . 
				' "object_key" CHAR(8) NOT NULL, ' .
				' "genre_parent" CHAR(8) DEFAULT NULL, ' .
				' "genre_title" TEXT DEFAULT NULL, ' .
				' "genre_description" TEXT DEFAULT NULL, ' .
				' "genre_urn" VARCHAR(64) DEFAULT NULL, ' .
				' PRIMARY KEY("object_key"), ' .
				' INDEX ("genre_parent"), ' .
				' INDEX ("genre_urn") ' .
				')');
			return true;
		}
		if($ver == 104)
		{
			$this->db->exec('ALTER TABLE "asset_genre" DROP INDEX "genre_urn"');
			$this->db->exec('ALTER TABLE "asset_genre" DROP COLUMN "genre_urn"');
			return true;
		}
		if($ver == 105)
		{
			$this->db->exec('CREATE TABLE "asset_genre_urn" (' . 
				' "object_key" CHAR(8) NOT NULL, ' .
				' "genre_urn" VARCHAR(64) NOT NULL, ' .
				' PRIMARY KEY ("object_key", "genre_urn"), ' .
				' INDEX ("object_key"), ' .
				' INDEX ("genre_urn") ' .
				')');
			return true;
		}
		if($ver == 106)
		{
			$this->db->exec('CREATE TABLE "asset_mediatype" (' . 
				' "object_key" CHAR(8) NOT NULL, ' .
				' "mediatype_parent" CHAR(8) DEFAULT NULL, ' .
				' "mediatype_title" TEXT DEFAULT NULL, ' .
				' "mediatype_description" TEXT DEFAULT NULL, ' .
				' PRIMARY KEY("object_key"), ' .
				' INDEX ("mediatype_parent") ' .
				')');
			return true;
			
		}
		if($ver == 107)
		{
			$this->db->exec('CREATE TABLE "asset_format" (' . 
				' "object_key" CHAR(8) NOT NULL, ' .
				' "format_parent" CHAR(8) DEFAULT NULL, ' .
				' "format_title" TEXT DEFAULT NULL, ' .
				' "format_description" TEXT DEFAULT NULL, ' .
				' PRIMARY KEY("object_key"), ' .
				' INDEX ("format_parent") ' .
				')');
			return true;
		}
		if($ver == 108)
		{
			$this->db->exec('CREATE TABLE "asset_url" (' .
				' "object_key" CHAR(8) NOT NULL, ' .
				' "object_url" VARCHAR(255) NOT NULL, ' .
				' PRIMARY KEY ("object_url"), ' .
				' INDEX ("object_key") ' .
				')');
			return true;
		}
		if($ver == 109)
		{
			$rs = $this->db->query('SELECT "genre_urn", "object_key" FROM "asset_genre_urn"');
			while(($row = $rs->next()))
			{
				$this->db->insert("asset_url", array(
					'object_key' => $row['object_key'],
					'object_url' => $row['genre_urn'],
				));
			}
			return true;
		}
		if($ver == 110)
		{
			$this->db->exec('DROP TABLE "asset_genre_urn"');
			return true;
		}
		if($ver == 111)
		{
			$this->db->exec('CREATE TABLE "asset_video" (' .
				' "object_key" CHAR(8) NOT NULL, ' .
				' "video_title" VARCHAR(255) NOT NULL, ' .
				' "video_source" CHAR(8) DEFAULT NULL, ' .
				' "video_orig_source" CHAR(8) DEFAULT NULL, ' .
				' "video_lossy" ENUM(\'N\', \'Y\') NOT NULL, ' .
				' "video_type" VARCHAR(64) NOT NULL, ' .
				' "video_copyright" TEXT DEFAULT NULL, ' .
				' "video_license" TEXT DEFAULT NULL, ' . 
				' "video_xres" BIGINT UNSIGNED NOT NULL,  ' .
				' "video_yres" BIGINT UNSIGNED NOT NULL,  ' .
				' "video_depth" TINYINT UNSIGNED NOT NULL,  ' .
				' "video_chroma" VARCHAR(8) NOT NULL, ' .
				' "video_fps" TINYINT UNSIGNED NOT NULL, ' . 
				' "video_frames" BIGINT UNSIGNED NOT NULL, ' .
				' "video_seqformat" VARCHAR(64) DEFAULT NULL, ' .
				' PRIMARY KEY ("object_key"), ' .
				' INDEX ("video_source"), ' .
				' INDEX ("video_orig_source"), ' . 
				' INDEX ("video_lossy"), ' .
				' INDEX ("video_type"), ' .
				' INDEX ("video_chroma"), ' .
				' INDEX ("video_xres"), ' .
				' INDEX ("video_yres"), ' .
				' INDEX ("video_depth"), ' .
				' INDEX ("video_fps") ' .
				')');
			return true;
		}
		if($ver == 112)
		{
			$this->db->exec('CREATE TABLE "asset_picture" (' .
				' "object_key" CHAR(8) NOT NULL, ' .
				' "picture_title" VARCHAR(255) NOT NULL, ' .
				' "picture_source" CHAR(8) DEFAULT NULL, ' .
				' "picture_orig_source" CHAR(8) DEFAULT NULL, ' .
				' "picture_lossy" ENUM(\'N\', \'Y\') NOT NULL, ' .
				' "picture_type" VARCHAR(64) NOT NULL, ' .
				' "picture_copyright" TEXT DEFAULT NULL, ' .
				' "picture_license" TEXT DEFAULT NULL, ' . 				
				' "picture_xres" BIGINT UNSIGNED NOT NULL, ' .
				' "picture_yres" BIGINT UNSIGNED NOT NULL, ' .
				' "picture_depth" BIGINT UNSIGNED NOT NULL, ' .
				' "picture_chroma" BIGINT UNSIGNED NOT NULL, ' . 
				' PRIMARY KEY ("object_key"), ' .
				' INDEX ("picture_source"), ' .
				' INDEX ("picture_orig_source"), ' . 
				' INDEX ("picture_lossy"), ' .
				' INDEX ("picture_type"), ' .
				' INDEX ("picture_chroma"), ' .
				' INDEX ("picture_xres"), ' .
				' INDEX ("picture_yres"), ' .
				' INDEX ("picture_depth") ' .
				')');
			return true;
		}
		if($ver == 113)
		{
			$this->db->exec('ALTER TABLE "asset_object" ADD COLUMN "object_has_manifest" ENUM(\'N\', \'Y\') NOT NULL DEFAULT \'N\'');
			return true;
		}
		if($ver == 114)
		{
			$this->db->exec('ALTER TABLE "asset_object" ADD INDEX "object_has_manifest" ("object_has_manifest")');
			return true;
		}
		if($ver == 115)
		{
			$this->db->exec('ALTER TABLE "asset_picture" DROP INDEX "picture_chroma"');
			$this->db->exec('ALTER TABLE "asset_picture" DROP COLUMN "picture_chroma"');
			$this->db->exec('ALTER TABLE "asset_picture" ADD COLUMN "picture_chroma" VARCHAR(8) NOT NULL');
			$this->db->exec('ALTER TABLE "asset_picture" ADD INDEX "picture_chroma" ("picture_chroma")');
			return true;
		}
		if($ver == 116)
		{
			$this->db->exec('ALTER TABLE "asset_picture" ADD "picture_filename" VARCHAR(64) NOT NULL');
			return true;
		}
		return false;
	}
}

$module = new AssetsModule($db);
$module->update();
