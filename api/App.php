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
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Cerberus Helpdesk ' . APP_VERSION);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $api_token.':X');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$xml_out = curl_exec($ch);
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
			$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
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
	
}

class WgmFreshbooks_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const ID = 'wgm.freshbooks.setup.menu.plugins.freshbooks';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:wgm.freshbooks::config/menu_item.tpl');
	}
}

class WgmFreshbooksContactsTab extends Extension_AddressBookTab {
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();

		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_WgmFreshbooksClient';
		$defaults->id = View_WgmFreshbooksClient::DEFAULT_ID;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:wgm.freshbooks::contacts/tab.tpl');		
	}
	
	function doQuickSearchAction() {
		@$type = DevblocksPlatform::importGPC($_POST['type'],'string');
		@$query = DevblocksPlatform::importGPC($_POST['query'],'string');
	
		$query = trim($query);
	
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_WgmFreshbooksClient';
		$defaults->id = View_WgmFreshbooksClient::DEFAULT_ID;
	
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
	
		$params = array();
		if(!is_numeric($query))
			if($query && false===strpos($query,'*'))
				$query = '*' . $query . '*';
		
		switch($type) {
			case "account_name":
				$params[SearchFields_WgmFreshbooksClient::ACCOUNT_NAME] = new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::ACCOUNT_NAME, DevblocksSearchCriteria::OPER_LIKE, strtolower($query));
				break;
			case "email":
				$params[SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS] = new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS, DevblocksSearchCriteria::OPER_LIKE, strtolower($query));
				break;
			case "org":
				$params[SearchFields_WgmFreshbooksClient::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::ORG_NAME, DevblocksSearchCriteria::OPER_LIKE, strtolower($query));
				break;
			case "client_id":
				$params[SearchFields_WgmFreshbooksClient::ID] = new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::ID, DevblocksSearchCriteria::OPER_EQ, intval($query));
				break;
		}
	
		$view->addParams($params, true);
		$view->renderPage = 0;
		$view->renderSortBy = null;
	
		C4_AbstractViewLoader::setView($defaults->id,$view);
	
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','freshbooks')));
	}
	
};

class WgmFreshbooksSyncCron extends CerberusCronPageExtension {
	const ID = 'wgm.freshbooks.cron.sync';
	
	public function run() {
		$logger = DevblocksPlatform::getConsoleLog("Freshbooks");
		$logger->info("Started");
			
		$this->_downloadClients();
		$this->_synchronizeClients();
		
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
				
				// Load + compare email info
				if(!empty($client->email_id) && null != ($address = DAO_Address::get($client->email_id))) {
					$fields = array();
					
					if(!empty($client->data['first_name']))
						$fields[DAO_Address::FIRST_NAME] = $client->data['first_name'];
					if(!empty($client->data['last_name']))
						$fields[DAO_Address::LAST_NAME] = $client->data['last_name'];
						
					if(!empty($fields))
						DAO_Address::update($address->id, $fields);
				}
				
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
				WgmFreshbooksHelper::importOrSyncClientXml($xml_client);
			}
			
			// Next page, if exists
			$params['page']++;
			
		} while($page < $num_pages);
		
		$logger->info(sprintf("Downloaded %d updated client records", $total));
		
		// [TODO] Enable keys
		
		// Save the synchronize date as right now in GMT
		$this->setParam('clients.updated_from', time());
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";

		// Load settings
		$updated_from = $this->getParam('clients.updated_from', 0);
		if(empty($updated_from))
			$updated_from = gmmktime(0,0,0,1,1,2000);
		$tpl->assign('updated_from', $updated_from);
		
		$tpl->display('devblocks:wgm.freshbooks::config/cron.tpl');
	}
	
	public function saveConfigurationAction() {
		@$updated_from = DevblocksPlatform::importGPC($_POST['updated_from'], 'string', '');
		
		// Save settings
		$timestamp = intval(@strtotime($updated_from));
		if(!empty($timestamp))
			$this->setParam('clients.updated_from', $timestamp);
	}
};

if (class_exists('Extension_OrgTab')):
class WgmFreshbooksOrgTab extends Extension_OrgTab {
	function showTab() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		
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
}
endif;