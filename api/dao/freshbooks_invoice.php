<?php
class DAO_FreshbooksInvoice extends Cerb_ORMHelper {
	const ID = 'id';
	const INVOICE_ID = 'invoice_id';
	const CLIENT_ID = 'client_id';
	const NUMBER = 'number';
	const AMOUNT = 'amount';
	const STATUS = 'status';
	const CREATED = 'created';
	const UPDATED = 'updated';
	const DATA_JSON = 'data_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = "INSERT INTO freshbooks_invoice () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, 'freshbooks_invoice', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				//self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.freshbooks_invoice.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				//DevblocksPlatform::markContextChanged('cerberusweb.contexts.freshbooks.invoice', $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('freshbooks_invoice', $fields, $where);
	}

	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_FreshbooksInvoice[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, invoice_id, client_id, number, amount, status, created, updated, data_json ".
			"FROM freshbooks_invoice ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;

		$rs = $db->Execute($sql);

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_FreshbooksInvoice	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));

		if(isset($objects[$id]))
			return $objects[$id];

		return null;
	}
	
	static function getByInvoiceId($invoice_id) {
		$results = self::getWhere(sprintf("%s = %d", DAO_FreshbooksInvoice::INVOICE_ID, $invoice_id));
		return array_shift($results);
	}

	/**
	 * @param resource $rs
	 * @return Model_FreshbooksInvoice[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_FreshbooksInvoice();
			$object->id = $row['id'];
			$object->invoice_id = $row['invoice_id'];
			$object->client_id = $row['client_id'];
			$object->number = $row['number'];
			$object->amount = $row['amount'];
			$object->status = $row['status'];
			$object->created = $row['created'];
			$object->updated = $row['updated'];
			$object->data_json = $row['data_json'];
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

		$db->Execute(sprintf("DELETE FROM freshbooks_invoice WHERE id IN (%s)", $ids_list));

		// Fire event
		/*
		 $eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
				new Model_DevblocksEvent(
						'context.delete',
						array(
								'context' => 'cerberusweb.contexts.',
								'context_ids' => $ids
						)
				)
		);
		*/

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_FreshbooksInvoice::getFields();

		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);

		$select_sql = sprintf("SELECT ".
			"freshbooks_invoice.id as %s, ".
			"freshbooks_invoice.invoice_id as %s, ".
			"freshbooks_invoice.client_id as %s, ".
			"freshbooks_invoice.number as %s, ".
			"freshbooks_invoice.amount as %s, ".
			"freshbooks_invoice.status as %s, ".
			"freshbooks_invoice.created as %s, ".
			"freshbooks_invoice.updated as %s, ".
			"freshbooks_invoice.data_json as %s, ".
			"wgm_freshbooks_client.account_name as %s ",
				SearchFields_FreshbooksInvoice::ID,
				SearchFields_FreshbooksInvoice::INVOICE_ID,
				SearchFields_FreshbooksInvoice::CLIENT_ID,
				SearchFields_FreshbooksInvoice::NUMBER,
				SearchFields_FreshbooksInvoice::AMOUNT,
				SearchFields_FreshbooksInvoice::STATUS,
				SearchFields_FreshbooksInvoice::CREATED,
				SearchFields_FreshbooksInvoice::UPDATED,
				SearchFields_FreshbooksInvoice::DATA_JSON,
				SearchFields_FreshbooksInvoice::CLIENT_ACCOUNT_NAME
		);
			
		$join_sql = "FROM freshbooks_invoice ".
			"INNER JOIN wgm_freshbooks_client ON (wgm_freshbooks_client.id=freshbooks_invoice.client_id) "
			;

		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'freshbooks_invoice.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";

		array_walk_recursive(
			$params,
			array('DAO_FreshbooksInvoice', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'tables' => &$tables,
				'has_multiple_values' => &$has_multiple_values
			)
		);

		return array(
			'primary_table' => 'freshbooks_invoice',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';

		$param_key = $param->field;
		settype($param_key, 'string');

		switch($param_key) {
			/*
			 case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
			$args['has_multiple_values'] = true;
			self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
			break;
			*/
		}
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
			($has_multiple_values ? 'GROUP BY freshbooks_invoice.id ' : '').
			$sort_sql
			;
				
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
			$object_id = intval($row[SearchFields_FreshbooksInvoice::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql =
			($has_multiple_values ? "SELECT COUNT(DISTINCT freshbooks_invoice.id) " : "SELECT COUNT(freshbooks_invoice.id) ").
			$join_sql.
			$where_sql;
			$total = $db->GetOne($count_sql);
		}

		mysql_free_result($rs);

		return array($results,$total);
	}
	
	static function getStatuses() {
		return array(
			// 0 => 'unknown'
			1 => 'draft',
			2 => 'sent',
			3 => 'viewed',
			4 => 'pending',
			5 => 'paid',
			6 => 'auto-paid',
			7 => 'disputed',
			8 => 'retry',
			9 => 'failed',
		);
	}
};

class SearchFields_FreshbooksInvoice implements IDevblocksSearchFields {
	const ID = 'f_id';
	const INVOICE_ID = 'f_invoice_id';
	const CLIENT_ID = 'f_client_id';
	const NUMBER = 'f_number';
	const AMOUNT = 'f_amount';
	const STATUS = 'f_status';
	const CREATED = 'f_created';
	const UPDATED = 'f_updated';
	const DATA_JSON = 'f_data_json';
	
	const CLIENT_ACCOUNT_NAME = 'fc_account_name';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'freshbooks_invoice', 'id', $translate->_('common.id'), null),
			self::INVOICE_ID => new DevblocksSearchField(self::INVOICE_ID, 'freshbooks_invoice', 'invoice_id', $translate->_('dao.freshbooks_invoice.invoice_id'), Model_CustomField::TYPE_NUMBER),
			self::CLIENT_ID => new DevblocksSearchField(self::CLIENT_ID, 'freshbooks_invoice', 'client_id', $translate->_('dao.freshbooks_invoice.client_id'), Model_CustomField::TYPE_NUMBER),
			self::NUMBER => new DevblocksSearchField(self::NUMBER, 'freshbooks_invoice', 'number', $translate->_('dao.freshbooks_invoice.number'), Model_CustomField::TYPE_NUMBER),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'freshbooks_invoice', 'amount', $translate->_('dao.freshbooks_invoice.amount'), Model_CustomField::TYPE_NUMBER),
			self::STATUS => new DevblocksSearchField(self::STATUS, 'freshbooks_invoice', 'status', $translate->_('common.status'), null),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'freshbooks_invoice', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'freshbooks_invoice', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::DATA_JSON => new DevblocksSearchField(self::DATA_JSON, 'freshbooks_invoice', 'data_json', null, null),
				
			self::CLIENT_ACCOUNT_NAME => new DevblocksSearchField(self::CLIENT_ACCOUNT_NAME, 'wgm_freshbooks_client', 'account_name', $translate->_('dao.wgm_freshbooks_client.account_name'), Model_CustomField::TYPE_SINGLE_LINE),
		);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_FreshbooksInvoice {
	public $id;
	public $invoice_id;
	public $client_id;
	public $number;
	public $amount;
	public $status;
	public $created;
	public $updated;
	public $data_json;
};

class View_FreshbooksInvoice extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'freshbooks_invoice';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Freshbooks Invoices');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_FreshbooksInvoice::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_FreshbooksInvoice::STATUS,
			SearchFields_FreshbooksInvoice::AMOUNT,
			SearchFields_FreshbooksInvoice::CREATED,
			SearchFields_FreshbooksInvoice::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_FreshbooksInvoice::ID,
			SearchFields_FreshbooksInvoice::INVOICE_ID,
			SearchFields_FreshbooksInvoice::DATA_JSON,
		));

		$this->addParamsHidden(array(
			SearchFields_FreshbooksInvoice::ID,
			SearchFields_FreshbooksInvoice::INVOICE_ID,
			SearchFields_FreshbooksInvoice::DATA_JSON,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_FreshbooksInvoice::search(
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

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_FreshbooksInvoice', $ids);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_FreshbooksInvoice', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);

		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
				
			switch($field_key) {
				// Fields
				case SearchFields_FreshbooksInvoice::STATUS:
					$pass = true;
					break;
				
				// Virtuals
				//				case SearchFields_FreshbooksInvoice::VIRTUAL_CONTEXT_LINK:
				//				case SearchFields_FreshbooksInvoice::VIRTUAL_WATCHERS:
				//					$pass = true;
				//					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
				
			if($pass)
				$fields[$field_key] = $field_model;
		}

		return $fields;
	}

	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();

		switch($column) {
			case SearchFields_FreshbooksInvoice::STATUS:
				$label_map = DAO_FreshbooksInvoice::getStatuses();
				$counts = $this->_getSubtotalCountForStringColumn('DAO_FreshbooksInvoice', $column, $label_map, 'in', 'options[]');
				break;
			
			//			case SearchFields_FreshbooksInvoice::EXAMPLE_BOOL:
			//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_FreshbooksInvoice', $column);
			//				break;

			//			case SearchFields_FreshbooksInvoice::EXAMPLE_STRING:
			//				$counts = $this->_getSubtotalCountForStringColumn('DAO_FreshbooksInvoice', $column);
			//				break;

			//			case SearchFields_FreshbooksInvoice::VIRTUAL_CONTEXT_LINK:
			//				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_FreshbooksInvoice', 'example.context', $column);
			//				break;

			//			case SearchFields_FreshbooksInvoice::VIRTUAL_WATCHERS:
			//				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_FreshbooksInvoice', $column);
			//				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_FreshbooksInvoice', $column, 'freshbooks_invoice.id');
				}

				break;
		}

		return $counts;
	}

	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$statuses = DAO_FreshbooksInvoice::getStatuses();
		$tpl->assign('statuses', $statuses);
		
		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

 		$tpl->assign('view_template', 'devblocks:wgm.freshbooks::invoices.tpl');
 		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_FreshbooksInvoice::ID:

			case SearchFields_FreshbooksInvoice::AMOUNT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_FreshbooksInvoice::INVOICE_ID:
			case SearchFields_FreshbooksInvoice::CLIENT_ID:
			case SearchFields_FreshbooksInvoice::NUMBER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_FreshbooksInvoice::CREATED:
			case SearchFields_FreshbooksInvoice::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;

			case SearchFields_FreshbooksInvoice::STATUS:
				$options = DAO_FreshbooksInvoice::getStatuses();
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
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
			case SearchFields_FreshbooksInvoice::STATUS:
				$statuses = DAO_FreshbooksInvoice::getStatuses();
				$strings = array();

				foreach($values as $k) {
					if(isset($statuses[$k]))
						$strings[] = $statuses[$k];
				}
				
				echo implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;

		$translate = DevblocksPlatform::getTranslationService();

		switch($key) {
		}
	}

	function getFields() {
		return SearchFields_FreshbooksInvoice::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_FreshbooksInvoice::ID:

			case SearchFields_FreshbooksInvoice::AMOUNT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_FreshbooksInvoice::CLIENT_ID:
			case SearchFields_FreshbooksInvoice::NUMBER:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_FreshbooksInvoice::CREATED:
			case SearchFields_FreshbooksInvoice::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;

			case SearchFields_FreshbooksInvoice::STATUS:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
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
		@set_time_limit(600); // 10m

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
						//$change_fields[DAO_FreshbooksInvoice::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_FreshbooksInvoice::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_FreshbooksInvoice::ID,
				true,
				false
			);

			$ids = array_merge($ids, array_keys($objects));

		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
				
			if(!empty($change_fields)) {
				DAO_FreshbooksInvoice::update($batch_ids, $change_fields);
			}
	
			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_FreshbooksInvoice::ID, $custom_fields, $batch_ids);
				
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_FreshbooksInvoice extends Extension_DevblocksContext implements IDevblocksContextProfile { //, IDevblocksContextPeek, IDevblocksContextImport
	function getRandom() {
		//return DAO_WgmFreshbooksClient::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=freshbooks_invoice&id='.$context_id, true);
		return $url;
	}

	function getMeta($context_id) {
		$invoice = DAO_FreshbooksInvoice::get($context_id);

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($invoice->number);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return array(
			'id' => $invoice->id,
			'name' => sprintf("#%d - %0.2f", $invoice->number, $invoice->amount),
			'permalink' => $url,
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
					case 'client__label':
						$label = 'Client';
						break;
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'client__label',
			'number',
			'amount',
			'status',
			'created',
			'updated',
		);
	}

	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Freshbooks Invoice:';

		$translate = DevblocksPlatform::getTranslationService();

		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_FreshbooksInvoice::get($object);
		} elseif($object instanceof Model_FreshbooksInvoice) {
			// It's what we want already.
		} else {
			$object = null;
		}

		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'amount' => $prefix.$translate->_('dao.freshbooks_invoice.amount'),
			'created' => $prefix.$translate->_('common.created'),
			'number' => $prefix.$translate->_('dao.freshbooks_invoice.number'),
			'status' => $prefix.$translate->_('common.status'),
			'updated' => $prefix.$translate->_('common.updated'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'amount' => Model_CustomField::TYPE_NUMBER,
			'created' => Model_CustomField::TYPE_DATE,
			'number' => Model_CustomField::TYPE_NUMBER,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
			//'record_url' => Model_CustomField::TYPE_URL,
		);

		// Token values
		$token_values = array();

		$token_values['_context'] = 'wgm.freshbooks.contexts.invoice';
		$token_values['_types'] = $token_types;

		// Invoice token values
		if(null != $object) {
			$client = DAO_WgmFreshbooksClient::get($object->client_id);
			
			$token_values['_loaded'] = true;
			$token_values['_label'] = sprintf("#%s - \$%d%s",
				intval($object->number),
				intval($object->amount),
				(isset($client->account_name) ? (' to ' . $client->account_name) : '')
			);
			$token_values['id'] = $object->id;
			$token_values['amount'] = intval($object->amount);
			$token_values['created'] = $object->created;
			$token_values['number'] = intval($object->number);
			$token_values['updated'] = $object->updated;
			
			$statuses = DAO_FreshbooksInvoice::getStatuses();
			$token_values['status'] = isset($statuses[$object->status]) ? $statuses[$object->status] : '';

			// Client
			$client_id = (null != $object && !empty($object->client_id)) ? $object->client_id : null;
			$token_values['client_id'] = $client_id;
			
			// URL
			// $url_writer = DevblocksPlatform::getUrlService();
			// $token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=address&id=%d-%s",$address->id, DevblocksPlatform::strToPermalink($address->email)), true);
		}

		// Client
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext('wgm.freshbooks.contexts.client', null, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'client_',
			'',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = 'wgm.freshbooks.contexts.invoice';
		$context_id = $dictionary['id'];

		@$is_loaded = $dictionary['_loaded'];
		$values = array();

		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
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
		$view->name = 'Freshbooks Invoices';

		$view->addParamsDefault(array(
		), true);

		$view->addParams($view->getParamsDefault(), true);

		$view->renderSortBy = SearchFields_FreshbooksInvoice::UPDATED;
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
		$view->name = 'Freshbooks Invoices';

		$params_req = array();

		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_FreshbooksInvoice::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_FreshbooksInvoice::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		$view->addParamsRequired($params_req, true);

		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};