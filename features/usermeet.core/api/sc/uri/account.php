<?php
class UmScAccountController extends Extension_UmScController {
	const PARAM_FULL_NAME = 'account.full_name';
	const PARAM_CF_SELECT = 'account.cf_select';
	
	function isVisible() {
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		return !empty($active_user);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$address = DAO_Address::get($active_user->id);
		$tpl->assign('address',$address);
		
		$display_address_full_name = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_FULL_NAME, 0);
		$tpl->assign('display_address_full_name', $display_address_full_name);

		$address_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.address');
		$tpl->assign('address_fields', $address_fields);
		
		$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $address->id));
		$tpl->assign('address_field_values', $address_field_values);
		
		$cf_address_select_serial = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(),self::PARAM_CF_SELECT, '');
		$cf_address_select = !empty($cf_address_select_serial) ? unserialize($cf_address_select_serial) : array();
		$tpl->assign('cf_address_select', $cf_address_select);

		$tpl->display("devblocks:usermeet.core:support_center/account/index.tpl:portal_".UmPortalHelper::getCode());
	}

	function saveAccountAction() {
		@$first_name = DevblocksPlatform::importGPC($_REQUEST['first_name'],'string','');
		@$last_name = DevblocksPlatform::importGPC($_REQUEST['last_name'],'string','');
		@$change_password = DevblocksPlatform::importGPC($_REQUEST['change_password'],'string','');
		@$change_password2 = DevblocksPlatform::importGPC($_REQUEST['change_password2'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		if(!empty($active_user)) {
			$fields = array(
				DAO_Address::FIRST_NAME => $first_name,
				DAO_Address::LAST_NAME => $last_name
			);
			
			if(empty($change_password)) {
				// Do nothing
			} elseif(!empty($change_password) && 0 == strcmp($change_password,$change_password2)) {
				$fields[DAO_Address::PASS] = md5($change_password);
			} else {
				$tpl->assign('account_error', "The passwords you entered did not match.");
			}
			
			DAO_Address::update($active_user->id, $fields);
			$tpl->assign('account_success', true);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'account')));
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

		$settings = DevblocksPlatform::getPluginSettingsService();
        
		$address_full_name = DAO_CommunityToolProperty::get($instance->code, self::PARAM_FULL_NAME, 0);
		$tpl->assign('address_full_name', $address_full_name);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Contact: Fields
		$address_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.address');
		$tpl->assign('address_fields', $address_fields);

		$cf_address_select_serial = DAO_CommunityToolProperty::get($instance->code,self::PARAM_CF_SELECT, '');
		$cf_address_select = !empty($cf_address_select_serial) ? unserialize($cf_address_select_serial) : array();
		$tpl->assign('cf_address_select', $cf_address_select);
		
		$tpl->display("file:${tpl_path}portal/sc/config/module/account.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$address_full_name = DevblocksPlatform::importGPC($_POST['address_full_name'],'integer',0);
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_FULL_NAME, $address_full_name);

		$address_fields = DAO_CustomField::getBySource('cerberusweb.fields.source.address');
		foreach ($address_fields as $id => $value) {
			@$cf_address_select[$id] = DevblocksPlatform::importGPC($_POST['cf_address_select_'.$id],'integer',0);
		}
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_CF_SELECT, serialize($cf_address_select));
	}
}