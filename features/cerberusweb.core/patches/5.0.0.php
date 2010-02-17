<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Hand 'setting' over to 'devblocks_setting' (and copy)

if(isset($tables['setting']) && isset($tables['devblocks_setting'])) {
	$sql = "INSERT INTO devblocks_setting (plugin_id, setting, value) ".
		"SELECT 'cerberusweb.core', setting, value FROM setting";
	$db->Execute($sql);
	
	$db->Execute('DROP TABLE setting');

	$tables['devblocks_setting'] = 'devblocks_setting';
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

return TRUE;
