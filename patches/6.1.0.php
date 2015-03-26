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
			amount DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
			status TINYINT UNSIGNED NOT NULL DEFAULT 0,
			created INT UNSIGNED NOT NULL DEFAULT 0,
			updated INT UNSIGNED NOT NULL DEFAULT 0,
			data_json TEXT,
			PRIMARY KEY (id),
			INDEX status (status),
			INDEX updated (updated)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
	
	$tables['freshbooks_invoice'] = 'freshbooks_invoice';
}

// ===========================================================================
// Add wgm_freshbooks_client.balance

if(!isset($tables['wgm_freshbooks_client']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('wgm_freshbooks_client');

if(!isset($columns['balance'])) {
	$db->ExecuteMaster("ALTER TABLE wgm_freshbooks_client ADD COLUMN balance DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00");
}

return TRUE;
