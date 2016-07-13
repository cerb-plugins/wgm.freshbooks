<?php
class WgmFreshbooksAPI {
	private static $_instance = null;
	private $_api_url = null;
	private $_api_token = null;
	
	private function __construct() {
		$api_url = DevblocksPlatform::getPluginSetting('wgm.freshbooks','api_url','');
		$api_token = DevblocksPlatform::getPluginSetting('wgm.freshbooks','api_token','');
		
		if(!empty($api_url) && !empty($api_token)) {
			$this->_api_url = $api_url;
			$this->_api_token = $api_token;
		}
	}
	
	/**
	 * @return WgmFreshbooksAPI
	 */
	static function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmFreshbooksAPI();
		}
		
		return self::$_instance;
	}

	private function _execute($request, $api_url=null, $api_token=null) {
		$api_url = !empty($api_url) ? $api_url : $this->_api_url;
		$api_token = !empty($api_token) ? $api_token : $this->_api_token;
		
		$ch = DevblocksPlatform::curlInit();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Cerb ' . APP_VERSION);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $api_token.':X');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$xml_out = DevblocksPlatform::curlExec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		// Check HTTP status code
		if(200 != intval($info['http_code']))
			return false;

		if(false == ($xml = simplexml_load_string($xml_out)))
			return false;
			
		return $xml;
	}
	
	/**
	 * 
	 * @param string $request_xml
	 * @return SimpleXMLElement|false
	 */
	function requestXML($request_xml) {
		if(false == ($xml = $this->_execute($request_xml)))
			return false;
			
		if(0 != strcasecmp((string)$xml['status'],'ok')) {
			return false;
		}
		
		return $xml->asXML();
	}
	
	/**
	 *
	 * @param string $method
	 * @param array $params
	 * @return SimpleXMLElement|false
	 */
	function request($method, $params=array()) {
		$request_xml = simplexml_load_string(sprintf(
			"<?xml version='1.0' encoding='utf-8'?>".
			"<request method='%s'>".
			"</request>",
			$method
		)); /* @var $request_xml SimpleXMLElement */
		
		if(is_array($params))
		foreach($params as $key => $value)
			$request_xml->addChild($key, $value);
		
		if(false == ($xml = $this->_execute($request_xml->asXML())))
			return false;
			
		if(0 != strcasecmp((string)$xml['status'],'ok')) {
			return false;
		}
			
		return $xml;
	}
	
	/**
	 *
	 * @param string $method
	 * @param string $element
	 * @param array $params
	 * @return SimpleXMLElement|false
	 */
	function create($method, $element, $params=array()) {
		$request_xml = simplexml_load_string(sprintf(
			"<?xml version='1.0' encoding='utf-8'?>".
			"<request method='%s'>".
			"</request>",
			$method
		)); /* @var $request_xml SimpleXMLElement */
		
		if(false === $request_xml)
			return false;
		
		$element_xml = $request_xml->addChild($element);
		
		if(is_array($params))
		foreach($params as $key => $value)
			$element_xml->addChild($key, $value);
		
		if(false == ($xml = $this->_execute($request_xml->asXML())))
			return false;
			
		if(0 != strcasecmp((string)$xml['status'],'ok')) {
			return false;
		}
			
		return $xml;
	}
	
	function testAuthentication($api_url, $api_token) {
		$request = <<< EOF
<?xml version="1.0" encoding="utf-8"?>
<request method="staff.current">
</request>
EOF;

		if(false == ($xml = $this->_execute($request, $api_url, $api_token)))
			return false;
			
		if(0 != strcasecmp((string)$xml['status'],'ok'))
			return false;

		@$username = (string) $xml->staff->username;
		
		if(empty($username))
			return false;
		
		return $username;
	}
}

class WgmFreshbooksHelper {
	static function importOrSyncClientXml($xml_client) {
		$client_id = (integer) $xml_client->client_id;

		if(empty($client_id))
			return false;
		
		// Lookup/created the email address
		$email = (string) $xml_client->email;
		$email_id = 0;
		if(!empty($email)) {
			if(null != ($address = DAO_Address::lookupAddress($email, true)))
				$email_id = $address->id;
		}
		
		// Pull the updated date
		$updated_str = (string) $xml_client->updated;
		
		if(false === ($updated = strtotime($updated_str))) {
			$updated = time();
			
		} else {
			$date = new DateTime($updated_str, new DateTimeZone('America/New_York'));
			$date->setTimezone(new DateTimeZone(DevblocksPlatform::getTimezone()));
			$updated = strtotime($date->format('Y-m-d H:i:s'));
		}
		
		$fields = array(
			DAO_WgmFreshbooksClient::ACCOUNT_NAME => (string) $xml_client->organization,
			DAO_WgmFreshbooksClient::UPDATED => $updated,
			DAO_WgmFreshbooksClient::EMAIL_ID => $email_id,
			DAO_WgmFreshbooksClient::DATA_JSON => json_encode(new SimpleXMLElement($xml_client->asXML(), LIBXML_NOCDATA)),
		);
		
		// Insert/Update
		if(null == ($model = DAO_WgmFreshbooksClient::get($client_id))) {
			$fields[DAO_WgmFreshbooksClient::ID] = $client_id;
			DAO_WgmFreshbooksClient::create($fields);
			
		} else {
			DAO_WgmFreshbooksClient::update($client_id, $fields);
			
		}
		
		// Refresh model
		if(null == ($model = DAO_WgmFreshbooksClient::get($client_id)))
			return false;
		
		return $model;
	}
	
	static function importOrSyncInvoiceXml($xml_invoice) {
		$invoice_id = (integer) $xml_invoice->invoice_id;
	
		$statuses = array_flip(DAO_FreshbooksInvoice::getStatuses());
		
		if(empty($invoice_id))
			return false;
		
		// Pull the created date
		$created_str = (string) $xml_invoice->date;
		
		if(false === ($created = strtotime($created_str))) {
			$created = time();
			
		} else {
			$date = new DateTime($created_str, new DateTimeZone('America/New_York'));
			$date->setTimezone(new DateTimeZone('GMT'));
			$created = strtotime($date->format('Y-m-d H:i:s'));
		}
	
		// Pull the updated date
		$updated_str = (string) $xml_invoice->updated;
		
		if(false === ($updated = strtotime($updated_str))) {
			$updated = time();
			
		} else {
			$date = new DateTime($updated_str, new DateTimeZone('America/New_York'));
			$date->setTimezone(new DateTimeZone(DevblocksPlatform::getTimezone()));
			$updated = strtotime($date->format('Y-m-d H:i:s'));
		}
	
		$status_label = strtolower((string) $xml_invoice->status);
		@$status_id = $statuses[$status_label];
		
		$fields = array(
			DAO_FreshbooksInvoice::INVOICE_ID => $invoice_id,
			DAO_FreshbooksInvoice::CLIENT_ID => (integer) $xml_invoice->client_id,
			DAO_FreshbooksInvoice::NUMBER => (string) $xml_invoice->number,
			DAO_FreshbooksInvoice::AMOUNT => (float) $xml_invoice->amount,
			DAO_FreshbooksInvoice::STATUS => $status_id,
			DAO_FreshbooksInvoice::CREATED => $created,
			DAO_FreshbooksInvoice::UPDATED => $updated,
			DAO_FreshbooksInvoice::DATA_JSON => json_encode(new SimpleXMLElement($xml_invoice->asXML(), LIBXML_NOCDATA)),
		);
	
		// Insert/Update
		if(null == ($model = DAO_FreshbooksInvoice::getByInvoiceId($invoice_id))) {
			DAO_FreshbooksInvoice::create($fields);
			
		} else {
			DAO_FreshbooksInvoice::update($model->id, $fields);
		}
	
		// Refresh model
		if(null == ($model = DAO_FreshbooksInvoice::getByInvoiceId($invoice_id)))
			return false;
	
		return $model;
	}
}

class WgmFreshbooks_EventListener extends DevblocksEventListenerExtension {
		/**
		 * @param Model_DevblocksEvent $event
		 */
		function handleEvent(Model_DevblocksEvent $event) {
			switch($event->id) {
				case 'org.merge':
					$this->_orgMerge($event);
					break;
			}
		}

		private function _orgMerge($event) {
			@$merge_to_id = $event->params['merge_to_id'];
			@$merge_from_ids = $event->params['merge_from_ids'];

			// [TODO] This should merge invoice client_ids too
			DAO_WgmFreshbooksClient::mergeOrgIds($merge_from_ids, $merge_to_id);
		}
}

class WgmFreshbooksController extends DevblocksControllerExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}

	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		array_shift($stack); // example
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	    
	    exit;
	}

	function writeResponse(DevblocksHttpResponse $response) {
		return;
	}
	
	function invoicesAction() {
		$freshbooks = WgmFreshbooksAPI::getInstance();

		$params = array(
			//'updated_from' => '2000-01-01 00:00:00',
			'client_id' => 334,
			'page' => 1,
			'per_page' => 100,
			'folder' => 'active',
		);
		
		if(false == ($xml = $freshbooks->request('invoice.list', $params)))
			return false;

		foreach($xml->invoices->invoice as $xml_invoice) {
			// Remove redundant data
			unset($xml_invoice->organization);
			unset($xml_invoice->first_name);
			unset($xml_invoice->last_name);
			unset($xml_invoice->p_street1);
			unset($xml_invoice->p_street2);
			unset($xml_invoice->p_city);
			unset($xml_invoice->p_state);
			unset($xml_invoice->p_country);
			unset($xml_invoice->p_code);
//			var_dump($xml_invoice);
//			var_dump($xml_invoice->lines);
		}
	}
	
	/*
	function getClientsListAction() {
		$freshbooks = WgmFreshbooksAPI::getInstance();
		
		$params = array(
			'updated_from' => '2000-01-01 00:00:00',
			'page' => 1,
			'per_page' => 100,
			'folder' => 'active'
		);

		if(false == ($xml = $freshbooks->request('client.list', $params)))
			return false;
			
		$page = (integer) $xml->clients['page'];
		$num_pages = (integer) $xml->clients['pages'];
		$total = (integer) $xml->clients['total'];
			
		var_dump($page);
		var_dump($num_pages);
		var_dump($total);

		foreach($xml->clients->client as $xml_client) {
			var_dump((integer)$xml_client->client_id);
			var_dump((string)$xml_client->organization);
			var_dump((string)$xml_client->updated);
			//var_dump(json_encode(new SimpleXMLElement($xml_client->asXML(), LIBXML_NOCDATA)));
		}
		
		//var_dump($xml);
			
		return $xml;
	}
	*/
	
	function doOrgAddClientAction() {
		@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		@$first_name = DevblocksPlatform::importGPC($_POST['first_name'],'string','');
		@$last_name = DevblocksPlatform::importGPC($_POST['last_name'],'string','');
		
		$freshbooks = WgmFreshbooksAPI::getInstance();

		// Add to Freshbooks through API
		$params = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'organization' => $name,
			'email' => $email,
		);
		
		if(false == ($xml = $freshbooks->create('client.create', 'client', $params))) {
			//var_dump($xml);
			return false;
		}
		
		$client_id = (integer) $xml->client_id;
		
		if(empty($client_id))
			return false;
			
		// Retrieve fresh XML
		if(false == ($xml = $freshbooks->request('client.get', array('client_id'=>$client_id)))) {
			//var_dump($xml);
			return false;
		}
		
		// Add to local database
		if(null == ($model = WgmFreshbooksHelper::importOrSyncClientXml($xml->client)))
			return false;
			
		//var_dump($model);

		// Link org_id<->client_id and mark synchronized
		DAO_WgmFreshbooksClient::update($model->id, array(
			DAO_WgmFreshbooksClient::ORG_ID => $org_id,
			DAO_WgmFreshbooksClient::SYNCHRONIZED => time(),
		));
		
	}
	
	function viewSetOrgsAction() {
		@$client_ids = DevblocksPlatform::importGPC($_POST['client_id'],'array',array());
		@$org_lookups = DevblocksPlatform::importGPC($_POST['org_lookup'],'array',array());
	
		if(!is_array($client_ids) || !is_array($org_lookups))
			return;

		$clients = DAO_WgmFreshbooksClient::getWhere(sprintf("%s IN (%s)",
			DAO_WgmFreshbooksClient::ID,
			(!empty($client_ids) ? implode(',',$client_ids) : '-1')
		));
			
		foreach($client_ids as $idx => $client_id) {
			// Only if not blank
			if(!isset($org_lookups[$idx]) || empty($org_lookups[$idx]))
				continue;
			
			// Find or create the org
			if(null == ($org_id = DAO_ContactOrg::lookup($org_lookups[$idx], true)))
				continue;
				
			// Link to Freshbooks client
			DAO_WgmFreshbooksClient::update($client_id, array(
				DAO_WgmFreshbooksClient::ORG_ID => $org_id,
			));
			
			// If the address is unlinked, also link it to the org
			if(null != (@$client = $clients[$client_id])) {
				// Is an email address set?
				if(!empty($client->email_id) && null != ($address = DAO_Address::get($client->email_id))) {
					// Blank org?
					if(empty($address->contact_org_id)) {
						DAO_Address::update($address->id, array(
							DAO_Address::CONTACT_ORG_ID => $org_id,
						));
					}
				}
			}
		}
	}
};

class WgmFreshbooks_SetupPageSection extends Extension_PageSection {
	const ID = 'wgm.freshbooks.setup.section.freshbooks';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$params = array();
		$params['api_url'] = DevblocksPlatform::getPluginSetting('wgm.freshbooks','api_url','');
		$params['api_token'] = DevblocksPlatform::getPluginSetting('wgm.freshbooks','api_token','');
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.freshbooks::config/section.tpl');
	}
	
	function saveAction() {
		try {
			@$api_url = DevblocksPlatform::importGPC($_POST['api_url'],'string','');
			@$api_token = DevblocksPlatform::importGPC($_POST['api_token'],'string','');
	
			DevblocksPlatform::setPluginSetting('wgm.freshbooks','api_url',$api_url);
			DevblocksPlatform::setPluginSetting('wgm.freshbooks','api_token',$api_token);
				
		    echo json_encode(array('status'=>true, 'message'=>'Saved!'));
		    return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
		
	}

	function testAuthenticationAction() {
		try {
			@$api_url = DevblocksPlatform::importGPC($_POST['api_url'],'string','');
			@$api_token = DevblocksPlatform::importGPC($_POST['api_token'],'string','');
	
			$freshbooks = WgmFreshbooksAPI::getInstance();
			
			// Test credentials
			$username = $freshbooks->testAuthentication($api_url, $api_token);
			
			if(!empty($username))
				$message = 'Success! Logged in as ' . $username;
			else
				throw new Exception('Authentication failed!');
				
		    echo json_encode(array('status'=>true, 'message'=>$message));
		    return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};

class WgmFreshbooks_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const ID = 'wgm.freshbooks.setup.menu.plugins.freshbooks';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:wgm.freshbooks::config/menu_item.tpl');
	}
};

class WgmFreshbooksSyncCron extends CerberusCronPageExtension {
	const ID = 'wgm.freshbooks.cron.sync';
	
	public function run() {
		$logger = DevblocksPlatform::getConsoleLog("Freshbooks");
		$logger->info("Started");
		
		$this->_downloadClients();
		
		$this->_synchronizeClients();
		
		$this->_downloadInvoices();
		
		$logger->info("Finished");
	}
	
	private function _synchronizeClients() {
		$logger = DevblocksPlatform::getConsoleLog("Freshbooks");
		$freshbooks = WgmFreshbooksAPI::getInstance();
		
		// Retrieve clients that have changed since their sync date with org_id != 0
		$loops = 0;
		$synched = 0;
		do {
			$clients = DAO_WgmFreshbooksClient::getWhere(sprintf("%s != 0 AND %s > %s",
					DAO_WgmFreshbooksClient::ORG_ID,
					DAO_WgmFreshbooksClient::UPDATED,
					DAO_WgmFreshbooksClient::SYNCHRONIZED
				),
				DAO_WgmFreshbooksClient::UPDATED,
				true,
				100
			);
			
			foreach($clients as $client_id => $client) { /* @var $client Model_WgmFreshbooksClient */
				//var_dump($client->data);
				
				// Load + compare org info
				if(!empty($client->org_id) && null != ($org = DAO_ContactOrg::get($client->org_id))) {
					$fields = array();
					
					if(!empty($client->data['organization']))
						$fields[DAO_ContactOrg::NAME] = $client->data['organization'];
					if(!empty($client->data['work_phone']))
						$fields[DAO_ContactOrg::PHONE] = $client->data['work_phone'];
						
					$street = '';
					if(!empty($client->data['p_street1']))
						$street .= $client->data['p_street1'];
					if(!empty($client->data['p_street2']))
						$street .= "\n" . $client->data['p_street2'];
					if(!empty($street))
						$fields[DAO_ContactOrg::STREET] = $street;
						
					if(!empty($client->data['p_city']))
						$fields[DAO_ContactOrg::CITY] = $client->data['p_city'];
					if(!empty($client->data['p_state']))
						$fields[DAO_ContactOrg::PROVINCE] = $client->data['p_state'];
					if(!empty($client->data['p_code']))
						$fields[DAO_ContactOrg::POSTAL] = $client->data['p_code'];
					if(!empty($client->data['p_country']))
						$fields[DAO_ContactOrg::COUNTRY] = $client->data['p_country'];
					
					if(!empty($fields))
						DAO_ContactOrg::update($org->id, $fields);
				}
				
				// Update synchronized timestamp
				DAO_WgmFreshbooksClient::update($client_id, array(
					DAO_WgmFreshbooksClient::SYNCHRONIZED => time(),
				));
				$synched++;
			}
			
		} while(!empty($clients) && ++$loops < 25);
		
		$logger->info(sprintf("Synchronized %d local client records", $synched));
	}
	
	private function _downloadClients() {
		$logger = DevblocksPlatform::getConsoleLog("Freshbooks");
		$freshbooks = WgmFreshbooksAPI::getInstance();
		
		$updated_from_timestamp = $this->getParam('clients.updated_from', 0);
		
		// Pull the synchronize date from settings
		if(empty($updated_from_timestamp)) {
			$updated_from = '2000-01-01 00:00:00';
			
		} else {
			// For whatever weird reason, Freshbooks dealss with EDT/EST timestamps
			$date = new DateTime(gmdate("Y-m-d H:i:s", $updated_from_timestamp), new DateTimeZone('GMT'));
			$date->setTimezone(new DateTimeZone('America/New_York'));
			$updated_from  = $date->format('Y-m-d H:i:s');
		}
		
		$logger->info(sprintf("Downloading client records updated since %s EDT", $updated_from));
		
		$params = array(
			'updated_from' => $updated_from,
			'page' => 1,
			'per_page' => 100,
			'folder' => 'active'
		);

		// [TODO] Disable keys
		// [TODO] Empty table optimization: check count first then always insert if empty
		
		do {
			if(false == ($xml = $freshbooks->request('client.list', $params)))
				return false;

			// Stats
			$page = (integer) $xml->clients['page'];
			$num_pages = (integer) $xml->clients['pages'];
			$total = (integer) $xml->clients['total'];
	
			foreach($xml->clients->client as $xml_client) {
				$model = WgmFreshbooksHelper::importOrSyncClientXml($xml_client);
				
				if($model)
					$updated_from_timestamp = max($updated_from_timestamp, $model->updated);
			}
			
			// Next page, if exists
			$params['page']++;
			
		} while($page < $num_pages);
		
		if(empty($total))
			$updated_from_timestamp = time();
		
		$logger->info(sprintf("Downloaded %d updated client records", $total));
		
		// [TODO] Enable keys
		
		// Save the synchronize date as right now in GMT
		$this->setParam('clients.updated_from', $updated_from_timestamp);
	}
	
	private function _downloadInvoices() {
		$logger = DevblocksPlatform::getConsoleLog("Freshbooks");
		$freshbooks = WgmFreshbooksAPI::getInstance();
	
		$updated_from_timestamp = $this->getParam('invoices.updated_from', 0);
	
		// Pull the synchronize date from settings
		if(empty($updated_from_timestamp)) {
			$updated_from = '2000-01-01 00:00:00';
			
		} else {
			// For whatever weird reason, Freshbooks dealss with EDT/EST timestamps
			$date = new DateTime(gmdate("Y-m-d H:i:s", $updated_from_timestamp), new DateTimeZone('GMT'));
			$date->setTimezone(new DateTimeZone('America/New_York'));
			$updated_from  = $date->format('Y-m-d H:i:s');
		}
	
		$logger->info(sprintf("Downloading invoice records updated since %s EDT", $updated_from));
	
		$params = array(
			'updated_from' => $updated_from,
			'page' => 1,
			'per_page' => 100,
			'folder' => 'active'
		);
	
		// [TODO] Disable keys
		// [TODO] Empty table optimization: check count first then always insert if empty
	
		do {
			if(false == ($xml = $freshbooks->request('invoice.list', $params)))
				return false;
				
			// Stats
			$page = (integer) $xml->invoices['page'];
			$num_pages = (integer) $xml->invoices['pages'];
			$total = (integer) $xml->invoices['total'];
				
			foreach($xml->invoices->invoice as $xml_invoice) {
				$model = WgmFreshbooksHelper::importOrSyncInvoiceXml($xml_invoice); /* @var $model Model_FreshbooksInvoice */
				
				if($model)
					$updated_from_timestamp = max($updated_from_timestamp, $model->updated);
			}
				
			// Next page, if exists
			$params['page']++;
	
		} while($page < $num_pages);
	
		if(empty($total))
			$updated_from_timestamp = time();
		
		$logger->info(sprintf("Downloaded %d updated invoice records", $total));
	
		// [TODO] Enable keys
	
		// Save the synchronize date as right now in GMT
 		$this->setParam('invoices.updated_from', $updated_from_timestamp);
 		
 		// Update balance information
 		DAO_WgmFreshbooksClient::updateBalances();
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";

		// Load settings
		$clients_updated_from = $this->getParam('clients.updated_from', 0);
		if(empty($clients_updated_from))
			$clients_updated_from = gmmktime(0,0,0,1,1,2000);
		
		$invoices_updated_from = $this->getParam('invoices.updated_from', 0);
		if(empty($invoices_updated_from))
			$invoices_updated_from = gmmktime(0,0,0,1,1,2000);
		
		$tpl->assign('clients_updated_from', $clients_updated_from);
		
		$tpl->display('devblocks:wgm.freshbooks::config/cron.tpl');
	}
	
	public function saveConfigurationAction() {
		@$clients_updated_from = DevblocksPlatform::importGPC($_POST['clients_updated_from'], 'string', '');
		@$invoices_updated_from = DevblocksPlatform::importGPC($_POST['invoices_updated_from'], 'string', '');
		
		// Save settings
		$clients_timestamp = intval(@strtotime($clients_updated_from));
		if(!empty($clients_timestamp))
			$this->setParam('clients.updated_from', $clients_timestamp);
		
		$invoices_timestamp = intval(@strtotime($invoices_updated_from));
		if(!empty($invoices_timestamp))
			$this->setParam('invoices.updated_from', $invoices_timestamp);
	}
};

if (class_exists('Extension_ContextProfileTab')):
class WgmFreshbooksOrgTab extends Extension_ContextProfileTab {
	function showTab($context, $context_id) {
		if(0 != strcasecmp($context, CerberusContexts::CONTEXT_ORG))
			return;
		
		$org_id = $context_id;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Check if this org_id is in the Freshbooks client table
		$clients = $client = DAO_WgmFreshbooksClient::getWhere(sprintf("%s = %d",
				DAO_WgmFreshbooksClient::ORG_ID,
				$org_id
			),
			null,
			null,
			1
		);

		if(empty($clients)) {
			if(null != ($org = DAO_ContactOrg::get($org_id)))
				$tpl->assign('org', $org);
				
			$addresses = DAO_Address::getWhere(sprintf("%s = %d",
					DAO_Address::CONTACT_ORG_ID,
					$org_id
				),
				DAO_Address::NUM_NONSPAM,
				false
			);
			$tpl->assign('addresses', $addresses);
				
			$tpl->display('devblocks:wgm.freshbooks::orgs/notfound/tab.tpl');
			
		} else {
			$client = array_shift($clients);
			$tpl->assign('client', $client);
			
			$tpl->display('devblocks:wgm.freshbooks::orgs/exists/tab.tpl');
		}
		
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class WgmFreshbooks_EventActionApiCall extends Extension_DevblocksEventAction {
	const ID = 'wgm.freshbooks.event.action.api_call';
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:wgm.freshbooks::events/action_freshbooks_api_call.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$freshbooks = WgmFreshbooksAPI::getInstance();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$out = null;
		
		@$xml = $tpl_builder->build($params['xml'], $dict);
		@$response_placeholder = $params['response_placeholder'];
		@$run_in_simulator = $params['run_in_simulator'];
		
		if(empty($response_placeholder))
			return "[ERROR] No result placeholder given.";
		
		// Output
		$out = sprintf(">>> Sending request to Freshbooks API:\n\n%s\n\n",
			$xml
		);
		
		// Run in simulator?
		
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
			
			@$xml_response = $dict->$response_placeholder;
			
			$out .= sprintf(">>> API response is:\n\n%s\n\n",
				$xml_response
			);
			
			// Placeholder
			$out .= sprintf(">>> Saving response to placeholder:\n%s\n",
				$response_placeholder
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$freshbooks = WgmFreshbooksAPI::getInstance();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$xml = $tpl_builder->build($params['xml'], $dict);
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($response_placeholder))
			return false;
		
		$response = $freshbooks->requestXML($xml);
		$dict->$response_placeholder = $response;
	}
};
endif;