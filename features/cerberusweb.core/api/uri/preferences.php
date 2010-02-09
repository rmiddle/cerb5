<?php
class ChPreferencesPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$response = DevblocksPlatform::getHttpResponse();
		$path = $response->path;
		
		array_shift($path); // preferences
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.preferences.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$section = array_shift($path); // section
		switch($section) {
			case 'confirm_email':
				@$code = array_shift($path);
				$active_worker = CerberusApplication::getActiveWorker();
				
				$worker_addresses = DAO_AddressToWorker::getWhere(sprintf("%s = '%s' AND %s = %d",
					DAO_AddressToWorker::CODE,
					addslashes(str_replace(' ','',$code)),
					DAO_AddressToWorker::WORKER_ID,
					$active_worker->id
				));

				@$worker_address = array_shift($worker_addresses);
				
				if(!empty($code) 
					&& null != $worker_address 
					&& $worker_address->code == $code 
					&& $worker_address->code_expire > time()) {
						
						DAO_AddressToWorker::update($worker_address->address,array(
							DAO_AddressToWorker::CODE => '',
							DAO_AddressToWorker::IS_CONFIRMED => 1,
							DAO_AddressToWorker::CODE_EXPIRE => 0
						));
						
						$output = array(vsprintf($translate->_('prefs.address.confirm.tip'), $worker_address->address));
						$tpl->assign('pref_success', $output);
					
				} else {
					$errors = array($translate->_('prefs.address.confirm.invalid_code'));
					$tpl->assign('pref_errors', $errors);
				}
				
				$tpl->display('file:' . $tpl_path . 'preferences/index.tpl');
				break;
			
		    default:
		    	$tpl->assign('tab', $section);
				$tpl->display('file:' . $tpl_path . 'preferences/index.tpl');
				break;
		}
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showGeneralAction() {
		$date_service = DevblocksPlatform::getDateService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
		$tpl->assign('assist_mode', $tour_enabled);

		$keyboard_shortcuts = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1));
		$tpl->assign('keyboard_shortcuts', $keyboard_shortcuts);

		$mail_inline_comments = DAO_WorkerPref::get($worker->id,'mail_inline_comments',1);
		$tpl->assign('mail_inline_comments', $mail_inline_comments);
		
		$mail_always_show_all = DAO_WorkerPref::get($worker->id,'mail_always_show_all',0);
		$tpl->assign('mail_always_show_all', $mail_always_show_all);
		
		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$tpl->assign('addresses', $addresses);
				
		// Timezones
		$tpl->assign('timezones', $date_service->getTimezones());
		@$server_timezone = date_default_timezone_get();
		$tpl->assign('server_timezone', $server_timezone);
		
		// Languages
		$langs = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('langs', $langs);
		$tpl->assign('selected_language', DAO_WorkerPref::get($worker->id,'locale','en_US')); 
		
		$tpl->display('file:' . $tpl_path . 'preferences/modules/general.tpl');
	}
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showRssAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$feeds = DAO_ViewRss::getByWorker($active_worker->id);
		$tpl->assign('feeds', $feeds);
		
		$tpl->display('file:' . $tpl_path . 'preferences/modules/rss.tpl');
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveDefaultsAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
		@$default_signature = DevblocksPlatform::importGPC($_REQUEST['default_signature'],'string');
		@$default_signature_pos = DevblocksPlatform::importGPC($_REQUEST['default_signature_pos'],'integer',0);
		@$reply_box_height = DevblocksPlatform::importGPC($_REQUEST['reply_box_height'],'integer');
	    
		$worker = CerberusApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
   		$tpl = DevblocksPlatform::getTemplateService();
   		$pref_errors = array();
   		
   		// Time
   		$_SESSION['timezone'] = $timezone;
   		@date_default_timezone_set($timezone);
   		DAO_WorkerPref::set($worker->id,'timezone',$timezone);
   		
   		// Language
   		$_SESSION['locale'] = $lang_code;
   		DevblocksPlatform::setLocale($lang_code);
   		DAO_WorkerPref::set($worker->id,'locale',$lang_code);
   		
		@$new_password = DevblocksPlatform::importGPC($_REQUEST['change_pass'],'string');
		@$verify_password = DevblocksPlatform::importGPC($_REQUEST['change_pass_verify'],'string');
    	
		//[mdf] if nonempty passwords match, update worker's password
		if($new_password != "" && $new_password===$verify_password) {
			$session = DevblocksPlatform::getSessionService();
			$fields = array(
				DAO_Worker::PASSWORD => md5($new_password)
			);
			DAO_Worker::updateAgent($worker->id, $fields);
		}

		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);

		@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);

		@$mail_inline_comments = DevblocksPlatform::importGPC($_REQUEST['mail_inline_comments'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_inline_comments', $mail_inline_comments);
		
		@$mail_always_show_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_show_all'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_always_show_all', $mail_always_show_all);
		
		// Alternate Email Addresses
		@$new_email = DevblocksPlatform::importGPC($_REQUEST['new_email'],'string','');
		@$email_delete = DevblocksPlatform::importGPC($_REQUEST['email_delete'],'array',array());

		// Confirm deletions are assigned to the current worker
		if(!empty($email_delete))
		foreach($email_delete as $e) {
			if(null != ($worker_address = DAO_AddressToWorker::getByAddress($e))
				&& $worker_address->worker_id == $worker->id)
				DAO_AddressToWorker::unassign($e);
		}
		
		// Assign a new e-mail address if it's legitimate
		if(!empty($new_email)) {
			if(null != ($addy = DAO_Address::lookupAddress($new_email, true))) {
				if(null == ($assigned = DAO_AddressToWorker::getByAddress($new_email))) {
					$this->_sendConfirmationEmail($new_email, $worker);
				} else {
					$pref_errors[] = vsprintf($translate->_('prefs.address.exists'), $new_email);
				}
			} else {
				$pref_errors[] = vsprintf($translate->_('prefs.address.invalid'), $new_email);
			}
		}
		
		$tpl->assign('pref_errors', $pref_errors);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
	function resendConfirmationAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		$this->_sendConfirmationEmail($email, $worker);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
	private function _sendConfirmationEmail($to, $worker) {
		$translate = DevblocksPlatform::getTranslationService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl = DevblocksPlatform::getTemplateService();
							
		// Tentatively assign the e-mail address to this worker
		DAO_AddressToWorker::assign($to, $worker->id);
		
		// Create a confirmation code and save it
		$code = CerberusApplication::generatePassword(20);
		DAO_AddressToWorker::update($to, array(
			DAO_AddressToWorker::CODE => $code,
			DAO_AddressToWorker::CODE_EXPIRE => (time() + 24*60*60) 
		));
		
		// Email the confirmation code to the address
		// [TODO] This function can return false, and we need to do something different if it does.
		CerberusMail::quickSend(
			$to, 
			vsprintf($translate->_('prefs.address.confirm.mail.subject'), 
				$settings->get('cerberusweb.core',CerberusSettings::HELPDESK_TITLE)
			),
			vsprintf($translate->_('prefs.address.confirm.mail.body'),
				array(
					$worker->getName(),
					$url_writer->write('c=preferences&a=confirm_email&code='.$code,true)
				)
			)
		);
		
		$output = array(vsprintf($translate->_('prefs.address.confirm.mail.subject'), $to));
		$tpl->assign('pref_success', $output);
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveRssAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($feed = DAO_ViewRss::getId($id)) && $feed->worker_id == $active_worker->id) {
			DAO_ViewRss::delete($id);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','rss')));
	}
};
