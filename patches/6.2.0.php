<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add a property auto_inc primary key

if(!isset($tables['freshbooks_invoice']))
	return FALSE;

list($columns, $indexes) = $db->metaTable('freshbooks_invoice');

if(!isset($columns['invoice_id'])) {
	$db->Execute("ALTER TABLE freshbooks_invoice ADD COLUMN invoice_id INT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("ALTER TABLE freshbooks_invoice MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT");
	$db->Execute("UPDATE freshbooks_invoice SET invoice_id=id");
}

return TRUE;
