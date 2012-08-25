<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// freshbooks_invoice

if(!isset($tables['freshbooks_invoice'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS freshbooks_invoice (
			id INT UNSIGNED NOT NULL,
			client_id INT UNSIGNED NOT NULL DEFAULT 0,
			number VARCHAR(255) NOT NULL DEFAULT '',
			amount FLOAT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(15) NOT NULL DEFAULT '',
			created INT UNSIGNED NOT NULL DEFAULT 0,
			updated INT UNSIGNED NOT NULL DEFAULT 0,
			data_json TEXT,
			PRIMARY KEY (id),
			INDEX updated_date (updated_date)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

return TRUE;
