<?php


class DAO_FreshbooksInvoice extends C4_ORMHelper {
	const ID = 'id';
	const CLIENT_ID = 'client_id';
	const NUMBER = 'number';
	const AMOUNT = 'amount';
	const STATUS = 'status';
	const CREATED = 'created_date';
	const UPDATED = 'updated_date';
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
		parent::_update($ids, 'freshbooks_invoice', $fields);

		// Log the context update
		//DevblocksPlatform::markContextChanged('example.context', $ids);
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
		$sql = "SELECT id, client_id, number, amount, status, created, updated, data_json ".
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

	/**
	 * @param resource $rs
	 * @return Model_FreshbooksInvoice[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_FreshbooksInvoice();
			$object->id = $row['id'];
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
				"freshbooks_invoice.client_id as %s, ".
				"freshbooks_invoice.number as %s, ".
				"freshbooks_invoice.amount as %s, ".
				"freshbooks_invoice.status as %s, ".
				"freshbooks_invoice.created as %s, ".
				"freshbooks_invoice.updated as %s, ".
				"freshbooks_invoice.data_json as %s ",
				SearchFields_FreshbooksInvoice::ID,
				SearchFields_FreshbooksInvoice::CLIENT_ID,
				SearchFields_FreshbooksInvoice::NUMBER,
				SearchFields_FreshbooksInvoice::AMOUNT,
				SearchFields_FreshbooksInvoice::STATUS,
				SearchFields_FreshbooksInvoice::CREATED,
				SearchFields_FreshbooksInvoice::UPDATED,
				SearchFields_FreshbooksInvoice::DATA_JSON
		);
			
		$join_sql = "FROM freshbooks_invoice ";

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
			self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
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

};

class SearchFields_FreshbooksInvoice implements IDevblocksSearchFields {
	const ID = 'f_id';
	const CLIENT_ID = 'f_client_id';
	const NUMBER = 'f_number';
	const AMOUNT = 'f_amount';
	const STATUS = 'f_status';
	const CREATED = 'f_created';
	const UPDATED = 'f_updated';
	const DATA_JSON = 'f_data_json';

	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'freshbooks_invoice', 'id', $translate->_('dao.freshbooks_invoice.id')),
			self::CLIENT_ID => new DevblocksSearchField(self::CLIENT_ID, 'freshbooks_invoice', 'client_id', $translate->_('dao.freshbooks_invoice.client_id')),
			self::NUMBER => new DevblocksSearchField(self::NUMBER, 'freshbooks_invoice', 'number', $translate->_('dao.freshbooks_invoice.number')),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'freshbooks_invoice', 'amount', $translate->_('dao.freshbooks_invoice.amount')),
			self::STATUS => new DevblocksSearchField(self::STATUS, 'freshbooks_invoice', 'status', $translate->_('dao.freshbooks_invoice.status')),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'freshbooks_invoice', 'created', $translate->_('dao.freshbooks_invoice.created')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'freshbooks_invoice', 'updated', $translate->_('dao.freshbooks_invoice.updated')),
			self::DATA_JSON => new DevblocksSearchField(self::DATA_JSON, 'freshbooks_invoice', 'data_json', $translate->_('dao.freshbooks_invoice.data_json')),
		);

		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		//}

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_FreshbooksInvoice {
	public $id;
	public $client_id;
	public $number;
	public $amount;
	public $status;
	public $created;
	public $updated;
	public $data_json;
};

class View_FreshbooksInvoice extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'freshbooksinvoice';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('FreshbooksInvoice');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_FreshbooksInvoice::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
				SearchFields_FreshbooksInvoice::ID,
				SearchFields_FreshbooksInvoice::CLIENT_ID,
				SearchFields_FreshbooksInvoice::NUMBER,
				SearchFields_FreshbooksInvoice::AMOUNT,
				SearchFields_FreshbooksInvoice::STATUS,
				SearchFields_FreshbooksInvoice::CREATED,
				SearchFields_FreshbooksInvoice::UPDATED,
		);
		// [TODO] Filter fields
		$this->addColumnsHidden(array(
			SearchFields_FreshbooksInvoice::DATA_JSON,
		));

		// [TODO] Filter fields
		$this->addParamsHidden(array(
			SearchFields_WgmFreshbooksClient::DATA_JSON,
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
		$all_fields = $this->getParamsAvailable();

		$fields = array();

		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
			$pass = false;
				
			switch($field_key) {
				// Fields
				//				case SearchFields_FreshbooksInvoice::EXAMPLE:
				//					$pass = true;
				//					break;
					
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

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		// [TODO] Set your template path
		$tpl->display('devblocks:wgm.freshbooks::invoices.tpl');
// 		$tpl->assign('view_template', 'devblocks:wgm.freshbooks:invoices.tpl');
// 		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
			case SearchFields_FreshbooksInvoice::ID:

			case SearchFields_FreshbooksInvoice::AMOUNT:
			case SearchFields_FreshbooksInvoice::STATUS:

			case SearchFields_FreshbooksInvoice::DATA_JSON:
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;

			case SearchFields_FreshbooksInvoice::CLIENT_ID:
			case SearchFields_FreshbooksInvoice::NUMBER:
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;

			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_FreshbooksInvoice::CREATED:
			case SearchFields_FreshbooksInvoice::UPDATED:
			case 'placeholder_date':
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

		// [TODO] Move fields into the right data type
		switch($field) {
			case SearchFields_FreshbooksInvoice::ID:

			case SearchFields_FreshbooksInvoice::AMOUNT:
			case SearchFields_FreshbooksInvoice::STATUS:

			case SearchFields_FreshbooksInvoice::DATA_JSON:
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_FreshbooksInvoice::CLIENT_ID:
			case SearchFields_FreshbooksInvoice::NUMBER:
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_FreshbooksInvoice::CREATED:
			case SearchFields_FreshbooksInvoice::UPDATED:
			case 'placeholder_date':
				$criteria = $this->_doSetCriteriaDate($field, $oper);
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
		$url = $url_writer->writeNoProxy('c=profiles&type=freshbooks_invoicet&id='.$context_id, true);
		return $url;
	}

	function getMeta($context_id) {
		$client = DAO_FreshbooksInvoice::get($context_id);

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($invoice->number);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return array(
				'id' => $invoice->id,
				'name' => $invoice->number,
				'permalink' => $url,
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
		// 			'address' => $prefix.$translate->_('address.address'),
		// 			'record_url' => $prefix.$translate->_('common.url.record'),
		);

		// Token values
		$token_values = array();

		$token_values['_context'] = 'wgm.freshbooks.contexts.invoice';

		// Address token values
		if(null != $object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->number;
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

		$context = 'wgm.freshbooks.contexts.invoice';
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
		$view->name = 'Freshbooks Invoices';

		// 		$view->view_columns = array(
		// 			SearchFields_WgmFreshbooksClient::ACCOUNT_NAME,
		// 		);

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