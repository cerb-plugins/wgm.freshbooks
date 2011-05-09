<?php
class DAO_WgmFreshbooksClient extends C4_ORMHelper {
	const ID = 'id';
	const ACCOUNT_NAME = 'account_name';
	const EMAIL_ID = 'email_id';
	const ORG_ID = 'org_id';
	const UPDATED = 'updated';
	const SYNCHRONIZED = 'synchronized';
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
		$sql = "SELECT id, account_name, email_id, org_id, updated, synchronized, data_json ".
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
			"wgm_freshbooks_client.synchronized as %s ",
			//"wgm_freshbooks_client.data_json as %s ",
				SearchFields_WgmFreshbooksClient::ID,
				SearchFields_WgmFreshbooksClient::ACCOUNT_NAME,
				SearchFields_WgmFreshbooksClient::EMAIL_ID,
				SearchFields_WgmFreshbooksClient::EMAIL_ADDRESS,
				SearchFields_WgmFreshbooksClient::EMAIL_ORG_ID,
				SearchFields_WgmFreshbooksClient::ORG_ID,
				SearchFields_WgmFreshbooksClient::ORG_NAME,
				SearchFields_WgmFreshbooksClient::UPDATED,
				SearchFields_WgmFreshbooksClient::SYNCHRONIZED
				//SearchFields_WgmFreshbooksClient::DATA_JSON
			);
			
		$join_sql = "FROM wgm_freshbooks_client ".
			"LEFT JOIN contact_org ON (wgm_freshbooks_client.org_id = contact_org.id) ".
			"LEFT JOIN address ON (wgm_freshbooks_client.email_id = address.id) "
		;
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'wgm_freshbooks_client.id',
		//	$select_sql,
		//	$join_sql
		//);
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
};

class SearchFields_WgmFreshbooksClient implements IDevblocksSearchFields {
	const ID = 'w_id';
	const ACCOUNT_NAME = 'w_account_name';
	const EMAIL_ID = 'w_email_id';
	const ORG_ID = 'w_org_id';
	const UPDATED = 'w_updated';
	const SYNCHRONIZED = 'w_synchronized';
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
			self::ID => new DevblocksSearchField(self::ID, 'wgm_freshbooks_client', 'id', $translate->_('dao.wgm_freshbooks_client.client_id')),
			self::ACCOUNT_NAME => new DevblocksSearchField(self::ACCOUNT_NAME, 'wgm_freshbooks_client', 'account_name', $translate->_('common.name')),
			self::EMAIL_ID => new DevblocksSearchField(self::EMAIL_ID, 'wgm_freshbooks_client', 'email_id', $translate->_('common.email')),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'wgm_freshbooks_client', 'org_id', $translate->_('contact_org.name')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'wgm_freshbooks_client', 'updated', $translate->_('common.updated')),
			self::SYNCHRONIZED => new DevblocksSearchField(self::SYNCHRONIZED, 'wgm_freshbooks_client', 'synchronized', $translate->_('dao.wgm_freshbooks_client.synchronized')),
			self::DATA_JSON => new DevblocksSearchField(self::DATA_JSON, 'wgm_freshbooks_client', 'data_json', $translate->_('dao.wgm_freshbooks_client.data_json')),
			
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'address', 'email', $translate->_('common.email')),
			self::EMAIL_ORG_ID => new DevblocksSearchField(self::EMAIL_ORG_ID, 'address', 'contact_org_id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'contact_org', 'name', $translate->_('contact_org.name')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

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
			SearchFields_WgmFreshbooksClient::ID,
			SearchFields_WgmFreshbooksClient::UPDATED,
			SearchFields_WgmFreshbooksClient::SYNCHRONIZED,
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

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:wgm.freshbooks::view.tpl');
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
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
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
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_WgmFreshbooksClient::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_WgmFreshbooksClient::UPDATED:
			case SearchFields_WgmFreshbooksClient::SYNCHRONIZED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
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
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
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

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_WgmFreshbooksClient::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

