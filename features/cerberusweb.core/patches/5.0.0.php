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
// Fix BLOBS

list($columns, $indexes) = $db->metaTable('group_setting');

if(isset($columns['value'])
	&& 0 != strcasecmp('mediumtext',$columns['value']['type'])) {
		$db->Execute('ALTER TABLE group_setting MODIFY COLUMN value MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('message_header');

if(isset($columns['header_value'])
	&& 0 != strcasecmp('text',$columns['header_value']['type'])) {
		$db->Execute('ALTER TABLE message_header MODIFY COLUMN header_value TEXT');
}

list($columns, $indexes) = $db->metaTable('message_note');

if(isset($columns['content'])
	&& 0 != strcasecmp('mediumtext',$columns['content']['type'])) {
		$db->Execute('ALTER TABLE message_note MODIFY COLUMN content MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('team');

if(isset($columns['signature'])
	&& 0 != strcasecmp('text',$columns['signature']['type'])) {
		$db->Execute('ALTER TABLE team MODIFY COLUMN signature TEXT');
}

list($columns, $indexes) = $db->metaTable('view_rss');

if(isset($columns['params'])
	&& 0 != strcasecmp('mediumtext',$columns['params']['type'])) {
		$db->Execute('ALTER TABLE view_rss MODIFY COLUMN params MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['last_activity'])
	&& 0 != strcasecmp('text',$columns['last_activity']['type'])) {
		$db->Execute('ALTER TABLE worker MODIFY COLUMN last_activity MEDIUMTEXT');
}

list($columns, $indexes) = $db->metaTable('worker_pref');

if(isset($columns['value'])
	&& 0 != strcasecmp('mediumtext',$columns['value']['type'])) {
		$db->Execute('ALTER TABLE worker_pref MODIFY COLUMN value MEDIUMTEXT');
}


// ===========================================================================
// Fix View_* class name refactor

$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:14:\"C4_AddressView\"', 's:12:\"View_Address\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:17:\"C4_AttachmentView\"', 's:15:\"View_Attachment\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:17:\"C4_ContactOrgView\"', 's:15:\"View_ContactOrg\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:11:\"C4_TaskView\"', 's:9:\"View_Task\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:13:\"C4_TicketView\"', 's:11:\"View_Ticket\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:18:\"C4_TranslationView\"', 's:16:\"View_Translation\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:18:\"C4_WorkerEventView\"', 's:16:\"View_WorkerEvent\"') WHERE setting LIKE 'view%'");
$db->Execute("UPDATE worker_pref SET value=REPLACE(value, 's:13:\"C4_WorkerView\"', 's:11:\"View_Worker\"') WHERE setting LIKE 'view%'");


// ===========================================================================
// Add the mail_template.team_id to mail_template so we can limit the display of templates based on group ownwership

list($columns, $indexes) = $db->metaTable('mail_template');

if(!isset($columns['team_id'])) {
	$db->Execute('ALTER TABLE mail_template ADD COLUMN team_id INT DEFAULT 0 NOT NULL');
	$db->Execute('ALTER TABLE mail_template ADD INDEX team_id (team_id)');
}

return TRUE;
