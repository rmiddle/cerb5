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
// Remove deprecated CloudGlue

if(isset($tables['tag'])) {
	$db->Execute('DROP TABLE tag');
}

if(isset($tables['tag_seq'])) {
	$db->Execute('DROP TABLE tag_seq');
}

if(isset($tables['tag_index'])) {
	$db->Execute('DROP TABLE tag_index');
}

if(isset($tables['tag_to_content'])) {
	$db->Execute('DROP TABLE tag_to_content');
}

// ===========================================================================
// Remove deprecated F&R

if(isset($tables['fnr_query'])) {
	$db->Execute('DROP TABLE fnr_query');
}

if(isset($tables['fnr_query_seq'])) {
	$db->Execute('DROP TABLE fnr_query_seq');
}

// ===========================================================================
// Migrate to attachment storage service

list($columns, $indexes) = $db->metaTable('attachment');

if(isset($columns['filepath'])) {
	$db->Execute("ALTER TABLE attachment CHANGE COLUMN filepath storage_key VARCHAR(255) DEFAULT '' NOT NULL");
}

if(isset($columns['file_size'])) {
	$db->Execute("ALTER TABLE attachment CHANGE COLUMN file_size storage_size INT UNSIGNED DEFAULT 0 NOT NULL");
}

if(!isset($columns['storage_extension'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN storage_extension VARCHAR(255) DEFAULT '' NOT NULL");
	$db->Execute("UPDATE attachment SET storage_extension='devblocks.storage.engine.disk' WHERE storage_extension=''");
}

// ===========================================================================
// Migrate message content to storage service

if(isset($tables['message_content'])) {
	$db->Execute("RENAME TABLE message_content TO storage_message_content");
	$db->Execute("ALTER TABLE storage_message_content DROP INDEX content");
	$db->Execute("ALTER TABLE storage_message_content CHANGE COLUMN message_id id int unsigned default '0' not null");
	$db->Execute("ALTER TABLE storage_message_content CHANGE COLUMN content data blob");

	unset($tables['message_content']);
	$tables['storage_message_content'] = 'storage_message_content'; 
}

// storage_attachments to 64KB blob

list($columns, $indexes) = $db->metaTable('storage_attachments');

if(isset($columns['data'])
	&& 0 != strcasecmp('blob',$columns['data']['type'])) {
		$db->Execute('ALTER TABLE storage_attachments MODIFY COLUMN data BLOB');
}

if(!isset($columns['chunk'])) {
	$db->Execute("ALTER TABLE storage_attachments ADD COLUMN chunk smallint unsigned default 1");
	$db->Execute("ALTER TABLE storage_attachments ADD INDEX chunk (chunk)");
}

if(isset($indexes['PRIMARY'])) {
	$db->Execute("ALTER TABLE storage_attachments DROP PRIMARY KEY");
}

if(!isset($indexes['id'])) {
	$db->Execute("ALTER TABLE storage_attachments ADD INDEX id (id)");
}

// storage_message_content to 64KB blob

list($columns, $indexes) = $db->metaTable('storage_message_content');

if(isset($columns['data'])
	&& 0 != strcasecmp('blob',$columns['data']['type'])) {
		$db->Execute('ALTER TABLE storage_message_content MODIFY COLUMN data BLOB');
}

if(!isset($columns['chunk'])) {
	$db->Execute("ALTER TABLE storage_message_content ADD COLUMN chunk smallint unsigned default 1");
	$db->Execute("ALTER TABLE storage_message_content ADD INDEX chunk (chunk)");
}

if(isset($indexes['PRIMARY'])) {
	$db->Execute("ALTER TABLE storage_message_content DROP PRIMARY KEY");
}

if(!isset($indexes['id'])) {
	$db->Execute("ALTER TABLE storage_message_content ADD INDEX id (id)");
}

// Add storage columns to 'message'

list($columns, $indexes) = $db->metaTable('message');

if(!isset($columns['storage_extension'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN storage_extension VARCHAR(255) DEFAULT '' NOT NULL");
	$db->Execute("ALTER TABLE message ADD INDEX storage_extension (storage_extension)");
}
$db->Execute("UPDATE message SET storage_extension='devblocks.storage.engine.database' WHERE storage_extension=''");

if(!isset($columns['storage_key'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN storage_key VARCHAR(255) DEFAULT '' NOT NULL");
}

if(!isset($columns['storage_size'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN storage_size INT UNSIGNED DEFAULT 0 NOT NULL");
	$db->Execute("UPDATE message, storage_message_content SET message.storage_size = LENGTH(storage_message_content.data) WHERE message.id=storage_message_content.id");
}

$db->Execute("UPDATE message SET storage_key=id WHERE storage_key = '' AND storage_extension='devblocks.storage.engine.database'");

// ===========================================================================
// Enable storage manager scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('cron.storage', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '1');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'h');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 22:15'));
}

// ===========================================================================
// Migrate storage_extension fields to storage_profile_id

// Attachments
list($columns, $indexes) = $db->metaTable('attachment');

if(!isset($columns['storage_profile_id'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN storage_profile_id INT UNSIGNED DEFAULT 0 NOT NULL");
	$db->Execute("ALTER TABLE attachment ADD INDEX storage_profile_id (storage_profile_id)");
}

// Message Content
list($columns, $indexes) = $db->metaTable('message');

if(!isset($columns['storage_profile_id'])) {
	$db->Execute("ALTER TABLE message ADD COLUMN storage_profile_id INT UNSIGNED DEFAULT 0 NOT NULL");
	$db->Execute("ALTER TABLE message ADD INDEX storage_profile_id (storage_profile_id)");
}

// ===========================================================================
// Enable search indexer scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('cron.search', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '10');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 22:15'));
}

// ===========================================================================
// Worker last activity IP

list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['last_activity_ip'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN last_activity_ip BIGINT UNSIGNED DEFAULT 0 NOT NULL");
}

// ===========================================================================
// Migrate user template tokens from Smarty to Twig

// Default signature
$db->Execute("UPDATE devblocks_setting SET value=REPLACE(value,'#first_name#','{{worker_first_name}}') WHERE setting='default_signature'");
$db->Execute("UPDATE devblocks_setting SET value=REPLACE(value,'#last_name#','{{worker_last_name}}') WHERE setting='default_signature'");
$db->Execute("UPDATE devblocks_setting SET value=REPLACE(value,'#title#','{{worker_title}}') WHERE setting='default_signature'");

// Group signatures
$db->Execute("UPDATE team SET signature=REPLACE(signature,'#first_name#','{{worker_first_name}}')");
$db->Execute("UPDATE team SET signature=REPLACE(signature,'#last_name#','{{worker_last_name}}')");
$db->Execute("UPDATE team SET signature=REPLACE(signature,'#title#','{{worker_title}}')");


return TRUE;