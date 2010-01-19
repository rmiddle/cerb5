<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db,'mysql'); /* @var $datadict ADODB2_mysql */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Hand 'setting' over to 'devblocks_setting' (and copy)

if(isset($tables['setting']) && isset($tables['devblocks_setting'])) {
	$sql = "INSERT INTO devblocks_setting (plugin_id, setting, value) ".
		"SELECT 'cerberusweb.core', setting, value FROM setting";
	$db->Execute($sql);
	
	$sql = $datadict->DropTableSQL('setting');
	$datadict->ExecuteSQLArray($sql);
	
    unset($tables['setting']);
}

// ===========================================================================
// Add the mail_template.team_id to mail_template so we can limit the display of templates based on group ownwership

$columns = $datadict->MetaColumns('mail_template');
$indexes = $datadict->MetaIndexes('mail_template',false);

if(!isset($columns['team_id'])) {
        $sql = $datadict->AddColumnSQL('mail_template', 'team_id I4 DEFAULT 0 NOTNULL');
        $datadict->ExecuteSQLArray($sql);

        $sql = $datadict->CreateIndexSQL('team_id','mail_template','team_id');
        $datadict->ExecuteSQLArray($sql);
}
