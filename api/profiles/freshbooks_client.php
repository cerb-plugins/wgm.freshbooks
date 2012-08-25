<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_ProfilesFreshbooksClient extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // freshbooks_client
		@$id = intval(array_shift($stack));
		
		if(null == ($client = DAO_WgmFreshbooksClient::get($id))) {
			return;
		}
		$tpl->assign('client', $client); /* @var $client Model_WgmFreshbooksClient */
		
		// Remember the last tab/URL
		
		@$selected_tab = array_shift($stack);
		
 		$point = 'wgm.freshbooks.profile';
 		$tpl->assign('point', $point);
		
 		if(null == $selected_tab) {
 			$selected_tab = $visit->get($point, '');
 		}
 		$tpl->assign('selected_tab', $selected_tab);
		
		// Custom fields
		
// 		$custom_fields = DAO_CustomField::getAll();
// 		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$properties = array();
		
// 		$properties['status'] = array(
// 			'label' => ucfirst($translate->_('common.status')),
// 			'type' => null,
// 			'is_closed' => $opp->is_closed,
// 			'is_won' => $opp->is_won,
// 		);
			
// 		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_OPPORTUNITY, $opp->id)) or array();
		
// 		foreach($custom_fields as $cf_id => $cfield) {
// 			if(!isset($values[$cf_id]))
// 				continue;
		
// 			$properties['cf_' . $cf_id] = array(
// 				'label' => $cfield->name,
// 				'type' => $cfield->type,
// 				'value' => $values[$cf_id],
// 			);
// 		}
		
		$tpl->assign('properties', $properties);
		
		// Macros
// 		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.crm.opportunity');
// 		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:wgm.freshbooks::profile.tpl');
	}
};