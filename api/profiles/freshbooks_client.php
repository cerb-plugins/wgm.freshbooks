<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
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
		
		// Properties
		
		$properties = array();
		
// 		$properties['status'] = array(
// 			'label' => ucfirst($translate->_('common.status')),
// 			'type' => null,
// 			'is_closed' => $opp->is_closed,
// 			'is_won' => $opp->is_won,
// 		);

		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds('wgm.freshbooks.contexts.client', $client->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields('wgm.freshbooks.contexts.client', $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets('wgm.freshbooks.contexts.client', $client->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			'wgm.freshbooks.contexts.client' => array(
				$client->id => 
					DAO_ContextLink::getContextLinkCounts(
						'wgm.freshbooks.contexts.client',
						$client->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(isset($client->org_id)) {
			$properties_links[CerberusContexts::CONTEXT_ORG] = array(
				$client->org_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ORG,
						$client->org_id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Macros
		
//		$macros = DAO_TriggerEvent::getReadableByActor(
//			$active_worker,
//			'event.macro.'
//		);
// 		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:wgm.freshbooks::profile.tpl');
	}
};