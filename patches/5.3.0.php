<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// wgm_freshbooks_client

if(!isset($tables['wgm_freshbooks_client'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS wgm_freshbooks_client (
			id INT UNSIGNED NOT NULL,
			account_name VARCHAR(255) DEFAULT '',
			email_id INT UNSIGNED NOT NULL DEFAULT 0,
			org_id INT UNSIGNED NOT NULL DEFAULT 0,
			updated INT UNSIGNED NOT NULL DEFAULT 0,
			synchronized INT UNSIGNED NOT NULL DEFAULT 0,
			data_json TEXT,
			PRIMARY KEY (id),
			INDEX email_id (email_id),
			INDEX org_id (org_id),
			INDEX updated (updated),
			INDEX synchronized (synchronized)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['wgm_freshbooks_client'] = 'wgm_freshbooks_client';
}

// ===========================================================================
// Enable scheduled task and give defaults

if(null != ($cron = DevblocksPlatform::getExtension('wgm.freshbooks.cron.sync', true, true))) {
	$cron->setParam(CerberusCronPageExtension::PARAM_ENABLED, true);
	$cron->setParam(CerberusCronPageExtension::PARAM_DURATION, '30');
	$cron->setParam(CerberusCronPageExtension::PARAM_TERM, 'm');
	$cron->setParam(CerberusCronPageExtension::PARAM_LASTRUN, strtotime('Yesterday 22:15'));
}

return TRUE;
