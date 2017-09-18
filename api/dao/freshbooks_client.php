<?php
class DAO_WgmFreshbooksClient extends Cerb_ORMHelper {
	const ACCOUNT_NAME = 'account_name';
	const BALANCE = 'balance';
	const DATA_JSON = 'data_json';
	const EMAIL_ID = 'email_id';
	const ID = 'id';
	const ORG_ID = 'org_id';
	const SYNCHRONIZED = 'synchronized';
	const UPDATED = 'updated';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::ACCOUNT_NAME)
			->string()
			->setMaxLength(255)
			;
		// decimal(8,2) unsigned
		$validation
			->addField(self::BALANCE)
			->float()
			;
		// text
		$validation
			->addField(self::DATA_JSON)
			->string()
			->setMaxLength(65535)
			;
		// int(10) unsigned
		$validation
			->addField(self::EMAIL_ID)
			->id()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::ORG_ID)
			->id()
			;
		// int(10) unsigned
		$validation
			->addField(self::SYNCHRONIZED)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;

		return $validation->getFields();
	}
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		@$id = $fields[self::ID];
		
		// There's no auto-increment here
		if(empty($id))
			return false;
			
		$sql = sprintf("INSERT INTO wgm_freshbooks_client (id) VALUES (%d)", $id);
		$db->ExecuteMaster($sql);
		
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
		$db = DevblocksPlatform::services()->database();
		
		if(empty($to_id) || empty($from_ids))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		$db->ExecuteMaster(sprintf("UPDATE wgm_freshbooks_client SET org_id = %d WHERE org_id IN (%s)",
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
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, account_name, email_id, org_id, updated, synchronized, balance, data_json ".
			"FROM wgm_freshbooks_client ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WgmFreshbooksClient	 */
	static function get($id) {
		if(empty($id))
			return null;
		
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
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('wgm_freshbooks_client');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM wgm_freshbooks_client WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WgmFreshbooksClient::getFields();
		
		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_WgmFreshbooksClient', $sortBy);
		
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
			"LEFT JOIN address ON (wgm_freshbooks_client.email_id = address.id) ".
			'';
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WgmFreshbooksClient');
	
		return array(
			'primary_table' => 'wgm_freshbooks_client',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
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
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_WgmFreshbooksClient::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(wgm_freshbooks_client.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static function updateBalances() {
		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster("DROP TABLE IF EXISTS _tmp_balance");
		$db->ExecuteMaster("UPDATE wgm_freshbooks_client SET balance = 0.00 WHERE balance > 0");
		$db->ExecuteMaster("CREATE TEMPORARY TABLE _tmp_balance (PRIMARY KEY (client_id)) SELECT SUM(amount) AS balance, client_id FROM freshbooks_invoice WHERE status NOT IN (1,5,6) GROUP BY client_id");
		$db->ExecuteMaster("UPDATE wgm_freshbooks_client INNER JOIN _tmp_balance ON (_tmp_balance.client_id=wgm_freshbooks_client.id) SET wgm_freshbooks_client.balance=_tmp_balance.balance");
		$db->ExecuteMaster("DROP TABLE _tmp_balance");
	}
};

class SearchFields_WgmFreshbooksClient extends DevblocksSearchFields {
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
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'wgm_freshbooks_client.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'wgm.freshbooks.contexts.client' => new DevblocksSearchFieldContextKeys('wgm_freshbooks_client.id', self::ID),
			CerberusContexts::CONTEXT_ORG => new DevblocksSearchFieldContextKeys('wgm_freshbooks_client.org_id', self::ORG_ID),
			CerberusContexts::CONTEXT_ADDRESS => new DevblocksSearchFieldContextKeys('wgm_freshbooks_client.email_id', self::EMAIL_ID),
		);
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->value) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, 'wgm.freshbooks.contexts.client', self::getPrimaryKey());
				break;
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'wgm_freshbooks_client', 'id', $translate->_('dao.wgm_freshbooks_client.client_id'), Model_CustomField::TYPE_NUMBER, true),
			self::ACCOUNT_NAME => new DevblocksSearchField(self::ACCOUNT_NAME, 'wgm_freshbooks_client', 'account_name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'wgm_freshbooks_client', 'email_id', $translate->_('common.email'), null, true),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'wgm_freshbooks_client', 'org_id', $translate->_('common.organization'), null, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'wgm_freshbooks_client', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::SYNCHRONIZED => new DevblocksSearchField(self::SYNCHRONIZED, 'wgm_freshbooks_client', 'synchronized', $translate->_('dao.wgm_freshbooks_client.synchronized'), Model_CustomField::TYPE_DATE, true),
			self::BALANCE => new DevblocksSearchField(self::BALANCE, 'wgm_freshbooks_client', 'balance', $translate->_('dao.wgm_freshbooks_client.balance'), Model_CustomField::TYPE_NUMBER, true),
			self::DATA_JSON => new DevblocksSearchField(self::DATA_JSON, 'wgm_freshbooks_client', 'data_json', $translate->_('dao.wgm_freshbooks_client.data_json'), null, false),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'address', 'email', $translate->_('common.email'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EMAIL_ORG_ID => new DevblocksSearchField(self::EMAIL_ORG_ID, 'address', 'contact_org_id', null, null, true),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'contact_org', 'name', $translate->_('common.organization'), Model_CustomField::TYPE_SINGLE_LINE, true),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
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

class View_WgmFreshbooksClient extends C4_AbstractView implements IAbstractView_QuickSearch {
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
			SearchFields_WgmFreshbooksClient::VIRTUAL_CONTEXT_LINK,
			SearchFields_WgmFreshbooksClient::VIRTUAL_HAS_FIELDSET,
			SearchFields_WgmFreshbooksClient::VIRTUAL_WATCHERS,
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_WgmFreshbooksClient');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_WgmFreshbooksClient', $ids);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WgmFreshbooksClient', $size);
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_WgmFreshbooksClient::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::ACCOUNT_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'balance' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::BALANCE),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::ACCOUNT_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'org' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::ORG_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::ID),
				),
			'synced' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::SYNCHRONIZED),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::UPDATED),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_WgmFreshbooksClient::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links');
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('wgm.freshbooks.contexts.client', $fields, 'org');
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->display('devblocks:wgm.freshbooks::clients.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::services()->template();
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
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, 'wgm.freshbooks.contexts.client');
				break;
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
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
	
	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_WgmFreshbooksClient::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_WgmFreshbooksClient::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
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
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_WgmFreshbooksClient::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_WgmFreshbooksClient extends Extension_DevblocksContext implements IDevblocksContextProfile { //, IDevblocksContextPeek, IDevblocksContextImport
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_WgmFreshbooksClient::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
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
			'updated' => $client->updated,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'balance',
			'updated',
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
		} elseif(is_array($object)) {
			$object = Cerb_ORMHelper::recastArrayToModel($object, 'Model_WgmFreshbooksClient');
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'balance' => $prefix.$translate->_('dao.wgm_freshbooks_client.balance'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('dao.wgm_freshbooks_client.account_name'),
			'updated' => $prefix.$translate->_('common.updated'),
//			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'balance' => Model_CustomField::TYPE_NUMBER,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
//			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = 'wgm.freshbooks.contexts.client';
		$token_values['_types'] = $token_types;

		// Address token values
		if(null != $object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->account_name;
			$token_values['id'] = $object->id;
			$token_values['name'] = $object->account_name;
			$token_values['balance'] = $object->balance;
			$token_values['updated'] = $object->updated;

			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($object, $token_values);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'balance' => DAO_WgmFreshbooksClient::BALANCE,
			'id' => DAO_WgmFreshbooksClient::ID,
			'name' => DAO_WgmFreshbooksClient::ACCOUNT_NAME,
			'updated' => DAO_WgmFreshbooksClient::UPDATED,
		];
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'links':
				$links = $this->_lazyLoadLinks($context, $context_id);
				$values = array_merge($values, $links);
				break;
			
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
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Freshbooks Clients';
		
		$view->addParamsDefault(array(
		), true);
		
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_WgmFreshbooksClient::UPDATED;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Freshbooks Clients';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WgmFreshbooksClient::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};