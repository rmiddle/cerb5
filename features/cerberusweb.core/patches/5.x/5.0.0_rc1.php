<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// ticket.last_message_id

if(!isset($tables['ticket']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('ticket');

if(!isset($columns['last_message_id'])) {
	$db->Execute("ALTER TABLE ticket ADD COLUMN last_message_id INT UNSIGNED DEFAULT 0 NOT NULL"); // ~3.37s
	$db->Execute("CREATE TABLE tmp_patch_lastmsgid (ticket_id INT UNSIGNED, max_msg_id INT UNSIGNED) ENGINE=MyISAM SELECT ticket_id, MAX(id) as max_msg_id FROM message GROUP BY ticket_id"); // ~0.32s
	$db->Execute("UPDATE ticket INNER JOIN tmp_patch_lastmsgid ON (ticket.id=tmp_patch_lastmsgid.ticket_id) SET ticket.last_message_id=tmp_patch_lastmsgid.max_msg_id"); // ~0.74s 
	$db->Execute("DROP TABLE tmp_patch_lastmsgid"); // ~0s
	$db->Execute("ALTER TABLE ticket ADD INDEX last_message_id (last_message_id)"); // ~2.48s
}

// ===========================================================================
// Snippet token changes

if(!isset($tables['snippet']))
	return FALSE;
	
$db->Execute("UPDATE snippet SET content=REPLACE(content,'{{initial_sender_','{{initial_message_sender_') WHERE context='cerberusweb.snippets.ticket'");
$db->Execute("UPDATE snippet SET content=REPLACE(content,'{{latest_sender_','{{latest_message_sender_') WHERE context='cerberusweb.snippets.ticket'");

// ===========================================================================
// Migrate auto replies to snippet contexts

if(!isset($tables['group_setting']))
	return FALSE;

// Auto-reply (open)
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#timestamp#','{{global_timestamp|date}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#sender#','{{latest_message_sender_address}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#sender_first#','{{latest_message_sender_first_name}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#orig_body#','{{initial_message_content}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#mask#','{{mask}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#ticket_id#','{{id}}') WHERE setting='auto_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#subject#','{{subject}}') WHERE setting='auto_reply'");

// Auto-reply (close)
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#timestamp#','{{global_timestamp|date}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#sender#','{{latest_message_sender_address}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#sender_first#','{{latest_message_sender_first_name}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#orig_body#','{{initial_message_content}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#mask#','{{mask}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#ticket_id#','{{id}}') WHERE setting='close_reply'");
$db->Execute("UPDATE group_setting SET value=REPLACE(value,'#subject#','{{subject}}') WHERE setting='close_reply'");
 
// ===========================================================================
// Snippet->Context changes

if(!isset($tables['snippet']))
	return FALSE;
	
$db->Execute("UPDATE snippet SET context=REPLACE(context,'cerberusweb.snippets.','cerberusweb.contexts.')");

// ===========================================================================
// Mail Queue changes

if(!isset($tables['mail_queue']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('mail_queue');

// Queue Priority
if(isset($columns['priority'])) {
	$db->Execute("ALTER TABLE mail_queue CHANGE COLUMN priority queue_priority TINYINT UNSIGNED DEFAULT 0 NOT NULL");
	$db->Execute("ALTER TABLE mail_queue DROP INDEX priority");
	$db->Execute("ALTER TABLE mail_queue ADD INDEX queue_priority (queue_priority)");
}

// Queue Fails
if(!isset($columns['queue_fails'])) {
	$db->Execute("ALTER TABLE mail_queue ADD COLUMN queue_fails TINYINT UNSIGNED DEFAULT 0 NOT NULL");
}

// ===========================================================================
// Snippet Worker Uses

if(!isset($tables['snippet_usage'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS snippet_usage (
			snippet_id INT UNSIGNED NOT NULL DEFAULT 0,
			worker_id INT UNSIGNED NOT NULL DEFAULT 0,
			hits INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (snippet_id, worker_id),
			INDEX snippet_id (snippet_id),
			INDEX worker_id (worker_id),
			INDEX hits (hits)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['snippet_usage'] = 'snippet_usage';
}

return TRUE;
