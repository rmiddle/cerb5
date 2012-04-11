<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Calendar Recurring Events

if(!isset($tables['calendar_recurring_profile'])) {
	$sql = sprintf("CREATE TABLE IF NOT EXISTS calendar_recurring_profile (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		event_name VARCHAR(255) NOT NULL DEFAULT '',
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		is_available TINYINT UNSIGNED NOT NULL DEFAULT 0,
		date_start INT UNSIGNED NOT NULL DEFAULT 0,
		date_end INT UNSIGNED NOT NULL DEFAULT 0,
		params_json TEXT,
		PRIMARY KEY (id),
		INDEX owner (owner_context, owner_context_id)
	) ENGINE=%s", APP_DB_ENGINE);
	
	if(false === $db->Execute($sql))
		return FALSE;
		
	$tables['calendar_recurring_profile'] = 'calendar_recurring_profile';
}

// ===========================================================================
// Calendar Events

if(!isset($tables['calendar_event'])) {
	$sql = sprintf("CREATE TABLE IF NOT EXISTS calendar_event (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		name VARCHAR(255) NOT NULL DEFAULT '',
		recurring_id INT UNSIGNED NOT NULL DEFAULT 0,
		is_available TINYINT UNSIGNED NOT NULL DEFAULT 0,
		date_start INT UNSIGNED NOT NULL DEFAULT 0,
		date_end INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX owner (owner_context, owner_context_id),
		INDEX (recurring_id),
		INDEX (is_available),
		INDEX (date_start),
		INDEX (date_end)
	) ENGINE=%s", APP_DB_ENGINE);
	
	if(false === $db->Execute($sql))
		return FALSE;
		
	$tables['calendar_event'] = 'calendar_event';
}

// ===========================================================================
// Add placeholder columns to worker_view_model

if(!isset($tables['worker_view_model'])) {
	$logger->error("The 'worker_view_model' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker_view_model');

if(!isset($columns['placeholder_labels_json'])) {
	$db->Execute("ALTER TABLE worker_view_model ADD COLUMN placeholder_labels_json TEXT");
}

if(!isset($columns['placeholder_values_json'])) {
	$db->Execute("ALTER TABLE worker_view_model ADD COLUMN placeholder_values_json TEXT");
}

// ===========================================================================
// Add recurring to context_scheduled_behavior

if(!isset($tables['context_scheduled_behavior'])) {
	$logger->error("The 'context_scheduled_behavior' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('context_scheduled_behavior');

if(!isset($columns['repeat_json'])) {
	$db->Execute("ALTER TABLE context_scheduled_behavior ADD COLUMN repeat_json TEXT");
}

return TRUE;