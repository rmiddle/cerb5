<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
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

class PageSection_ProfilesAddress extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();

		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // address
		@$id = array_shift($stack);
		
		$id = intval($id);
		
		@$selected_tab = array_shift($stack);
		
		// Remember the last tab/URL
		$visit = CerberusApplication::getVisit();
		if(null == $selected_tab) {
			$selected_tab = $visit->get(Extension_AddressBookTab::POINT, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.address.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$address = DAO_Address::get($id);
		$tpl->assign('address', $address);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Properties
		
		$properties = array();
		
		if(!empty($address->contact_org_id)) {
			if(null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
				$properties['org'] = array(
					'label' => ucfirst($translate->_('contact_org.name')),
					'type' => null,
					'org_id' => $address->contact_org_id,
					'org' => $org,
				);
			}
		}
		
		$properties['num_spam'] = array(
			'label' => ucfirst($translate->_('address.num_spam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_spam,
		);
		
		$properties['num_nonspam'] = array(
			'label' => ucfirst($translate->_('address.num_nonspam')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $address->num_nonspam,
		);
		
		$properties['is_banned'] = array(
			'label' => ucfirst($translate->_('address.is_banned')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $address->is_banned,
		);
		
		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id)) or array();
		
		foreach($custom_fields as $cf_id => $cfield) {
			if(!isset($values[$cf_id]))
				continue;
		
			$properties['cf_' . $cf_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'value' => $values[$cf_id],
			);
		}
		
		$tpl->assign('properties', $properties);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.address');
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/address.tpl');		
	}
};