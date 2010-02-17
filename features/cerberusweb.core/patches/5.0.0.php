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

// Add the mail_template.team_id to mail_template so we can limit the display of templates based on group ownwership

list($columns, $indexes) = $db->metaTable('mail_template');

if(!isset($columns['team_id'])) {
	$db->Execute('ALTER TABLE mail_template ADD COLUMN team_id INT DEFAULT 0 NOT NULL');
	$db->Execute('ALTER TABLE mail_template ADD INDEX team_id (team_id)');
}

return TRUE;
