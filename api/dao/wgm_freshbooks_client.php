<?php
class DAO_WgmFreshbooksClient extends C4_ORMHelper {
	const ID = 'id';
	const ACCOUNT_NAME = 'account_name';
	const EMAIL_ID = 'email_id';
	const ORG_ID = 'org_id';
	const UPDATED = 'updated';
	const SYNCHRONIZED = 'synchronized';
	const BALANCE = 'balance';
	const DATA_JSON = 'data_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = $fields[self::ID];
		
		// There's no auto-increment here
		if(empty($id))
			return false;
			
		$sql = sprintf("INSERT INTO wgm_freshbooks_client (id) VALUES (%d)", $id);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'wgm_freshbooks_client', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('wgm_freshbooks_client', $fields, $where);
	}
	
	static function mergeOrgIds($from_ids, $to_id) {
    	$db = DevblocksPlatform::getDatabaseService();
    	
    	if(empty($to_id) || empty($from_ids))
    		return false;
    		
    	if(!is_numeric($to_id) || !is_array($from_ids))
    		return false;
    	
    	$db->Execute(sprintf("UPDATE wgm_freshbooks_client SET org_id = %d WHERE org_id IN (%s)",
    		$to_id,
    		implode(',', $from_ids)
    	));
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WgmFreshbooksClient[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, account_name, email_id, org_id, updated, synchronized, balance, data_json ".
			"FROM wgm_freshbooks_client ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WgmFreshbooksClient	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WgmFreshbooksClient[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WgmFreshbooksClient();
			$object->id = $row['id'];
			$object->account_name = $row['account_name'];
			$object->email_id = $row['email_id'];
			$object->org_id = $row['org_id'];
			$object->updated = $row['updated'];
			$object->synchronized = $row['synchronized'];
			$object->balance = $row['balance'];
			
			if(!empty($row['data_json']))
				$object->data = @json_decode($row['data_json'], true);
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM wgm_freshbooks_client WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WgmFreshbooksClient::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"wgm_freshbooks_client.id as %s, ".
			"wgm_freshbooks_client.account_name as %s, ".
			"wgm_freshbooks_client.email_id as %s, ".
			"address.email as %s, ".
			"address.contact_org_id as %s, ".
			"wgm_freshbooks_client.org_id as %s, ".
			"contact_org.name as %s, ".
			"wgm_freshbooks_client.updated as %s, ".
			"wgm_freshbooks_client.synchronized as %s, ".
			"wgm_freshbooks_client.balance as %s ",
			//"wgm_freshbooks_client.data_json as %s ",
				SearchFields_WgmFreshbooksClient::ID,
				SearchFields_WgmFreshbooksClient::ACCOUNT_NAME,
				SearchFields_WgmFreshbooksClient::EMAIL_ID,
				SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS,
				SearchFields_WgmFreshbooksClient::EMAIL_ORG_ID,
				SearchFields_WgmFreshbooksClient::ORG_ID,
				SearchFields_WgmFreshbooksClient::ORG_NAME,
				SearchFields_WgmFreshbooksClient::UPDATED,
				SearchFields_WgmFreshbooksClient::SYNCHRONIZED,
				SearchFields_WgmFreshbooksClient::BALANCE
				//SearchFields_WgmFreshbooksClient::DATA_JSON
			);
			
		$join_sql = "FROM wgm_freshbooks_client ".
			"LEFT JOIN contact_org ON (wgm_freshbooks_client.org_id = contact_org.id) ".
			"LEFT JOIN address ON (wgm_freshbooks_client.email_id = address.id) "
		;
		
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => 'wgm_freshbooks_client',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY wgm_freshbooks_client.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_WgmFreshbooksClient::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT wgm_freshbooks_client.id) " : "SELECT COUNT(wgm_freshbooks_client.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
	static function updateBalances() {
		$db = DevblocksPlatform::getDatabaseService();

		$db->Execute("DROP TABLE IF EXISTS _tmp_balance");
		$db->Execute("UPDATE wgm_freshbooks_client SET balance = 0.00 WHERE balance > 0");
		$db->Execute("CREATE TEMPORARY TABLE _tmp_balance SELECT SUM(amount) AS balance, client_id FROM freshbooks_invoice WHERE status NOT IN (1,5,6) GROUP BY client_id");
		$db->Execute("UPDATE wgm_freshbooks_client INNER JOIN _tmp_balance ON (_tmp_balance.client_id=wgm_freshbooks_client.id) SET wgm_freshbooks_client.balance=_tmp_balance.balance");
		$db->Execute("DROP TABLE _tmp_balance");
	}
};

class SearchFields_WgmFreshbooksClient implements IDevblocksSearchFields {
	const ID = 'w_id';
	const ACCOUNT_NAME = 'w_account_name';
	const EMAIL_ID = 'w_email_id';
	const ORG_ID = 'w_org_id';
	const UPDATED = 'w_updated';
	const SYNCHRONIZED = 'w_synchronized';
	const BALANCE = 'w_balance';
	const DATA_JSON = 'w_data_json';
	
	const EMAIL_ADDRESS = 'a_email';
	const EMAIL_ORG_ID = 'a_email_contact_org_id';
	const ORG_NAME = 'o_name';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'wgm_freshbooks_client', 'id', $translate->_('dao.wgm_freshbooks_client.client_id'), Model_CustomField::TYPE_NUMBER),
			self::ACCOUNT_NAME => new DevblocksSearchField(self::ACCOUNT_NAME, 'wgm_freshbooks_client', 'account_name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'wgm_freshbooks_client', 'email_id', $translate->_('common.email')),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'wgm_freshbooks_client', 'org_id', $translate->_('contact_org.name')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'wgm_freshbooks_client', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::SYNCHRONIZED => new DevblocksSearchField(self::SYNCHRONIZED, 'wgm_freshbooks_client', 'synchronized', $translate->_('dao.wgm_freshbooks_client.synchronized'), Model_CustomField::TYPE_DATE),
			self::BALANCE => new DevblocksSearchField(self::BALANCE, 'wgm_freshbooks_client', 'balance', $translate->_('dao.wgm_freshbooks_client.balance'), Model_CustomField::TYPE_NUMBER),
			self::DATA_JSON => new DevblocksSearchField(self::DATA_JSON, 'wgm_freshbooks_client', 'data_json', $translate->_('dao.wgm_freshbooks_client.data_json'), null),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'address', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE),
			self::EMAIL_ORG_ID => new DevblocksSearchField(self::EMAIL_ORG_ID, 'address', 'contact_org_id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'contact_org', 'name', $translate->_('contact_org.name'), Model_CustomField::TYPE_SINGLE_LINE),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_WgmFreshbooksClient {
	public $id;
	public $account_name;
	public $email_id;
	public $org_id;
	public $updated;
	public $synchronized;
	public $balance;
	public $data; // decoded
};

class View_WgmFreshbooksClient extends C4_AbstractView {
	const DEFAULT_ID = 'wgmfreshbooksclient';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('wgm.freshbooks.common.freshbooks_clients');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WgmFreshbooksClient::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WgmFreshbooksClient::ORG_NAME,
			SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS,
			SearchFields_WgmFreshbooksClient::UPDATED,
			SearchFields_WgmFreshbooksClient::BALANCE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_WgmFreshbooksClient::DATA_JSON,
			SearchFields_WgmFreshbooksClient::EMAIL_ID,
			SearchFields_WgmFreshbooksClient::EMAIL_ORG_ID,
			SearchFields_WgmFreshbooksClient::ORG_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_WgmFreshbooksClient::DATA_JSON,
			SearchFields_WgmFreshbooksClient::EMAIL_ID,
			SearchFields_WgmFreshbooksClient::EMAIL_ORG_ID,
			SearchFields_WgmFreshbooksClient::ORG_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WgmFreshbooksClient::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WgmFreshbooksClient', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display('devblocks:wgm.freshbooks::clients.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WgmFreshbooksClient::ACCOUNT_NAME:
			case SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS:
			case SearchFields_WgmFreshbooksClient::ORG_NAME:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_WgmFreshbooksClient::BALANCE:
			case SearchFields_WgmFreshbooksClient::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_WgmFreshbooksClient::UPDATED:
			case SearchFields_WgmFreshbooksClient::SYNCHRONIZED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_WgmFreshbooksClient::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WgmFreshbooksClient::ACCOUNT_NAME:
			case SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS:
			case SearchFields_WgmFreshbooksClient::ORG_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_WgmFreshbooksClient::BALANCE:
			case SearchFields_WgmFreshbooksClient::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_WgmFreshbooksClient::UPDATED:
			case SearchFields_WgmFreshbooksClient::SYNCHRONIZED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_WgmFreshbooksClient::EXAMPLE] = 'some value';
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_WgmFreshbooksClient::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_WgmFreshbooksClient::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_WgmFreshbooksClient::update($batch_ids, $change_fields);

			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_WgmFreshbooksClient extends Extension_DevblocksContext implements IDevblocksContextProfile { //, IDevblocksContextPeek, IDevblocksContextImport
    function getRandom() {
    	//return DAO_WgmFreshbooksClient::random();
    }
    
    function profileGetUrl($context_id) {
    	if(empty($context_id))
    		return '';
    
    	$url_writer = DevblocksPlatform::getUrlService();
    	$url = $url_writer->writeNoProxy('c=profiles&type=freshbooks_client&id='.$context_id, true);
    	return $url;
    }
    
	function getMeta($context_id) {
		$client = DAO_WgmFreshbooksClient::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($client->account_name);

		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $client->id,
			'name' => $client->account_name,
			'permalink' => $url,
		);
	}
    
	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Freshbooks Client:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_WgmFreshbooksClient::get($object);
		} elseif($object instanceof Model_WgmFreshbooksClient) {
			// It's what we want already.
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = array(
// 			'address' => $prefix.$translate->_('address.address'),
// 			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = 'wgm.freshbooks.contexts.client';

		// Address token values
		if(null != $object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->account_name;
			$token_values['id'] = $object->id;

			// URL
// 			$url_writer = DevblocksPlatform::getUrlService();
// 			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s",$address->id, DevblocksPlatform::strToPermalink($address->email)), true);
			
			// Org
// 			$org_id = (null != $address && !empty($address->contact_org_id)) ? $address->contact_org_id : null;
// 			$token_values['org_id'] = $org_id;
		}
		
		// Email Org
// 		$merge_token_labels = array();
// 		$merge_token_values = array();
// 		CerberusContexts::getContext(CerberusContexts::CONTEXT_ORG, null, $merge_token_labels, $merge_token_values, null, true);

// 		CerberusContexts::merge(
// 			'org_',
// 			'',
// 			$merge_token_labels,
// 			$merge_token_values,
// 			$token_labels,
// 			$token_values
// 		);		
		
		return true;		
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = 'wgm.freshbooks.contexts.client';
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				break;
		}
		
		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Freshbooks Clients';
		
// 		$view->view_columns = array(
// 			SearchFields_WgmFreshbooksClient::ACCOUNT_NAME,
// 		);
		
		$view->addParamsDefault(array(
		), true);
		
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_WgmFreshbooksClient::UPDATED;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Freshbooks Clients';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};