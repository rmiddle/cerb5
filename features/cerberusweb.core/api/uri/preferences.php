<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani.
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChPreferencesPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}

	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::getTemplateService();
		$response = DevblocksPlatform::getHttpResponse();
		$visit = CerberusApplication::getVisit();

		$path = $response->path;

		array_shift($path); // preferences

		$tab_manifests = DevblocksPlatform::getExtensions(Extension_PreferenceTab::POINT, false);
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

				$tpl->display('devblocks:cerberusweb.core::preferences/index.tpl');
				break;

		    default:
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[1])) {
					$selected_tab = $visit->get(Extension_PreferenceTab::POINT, '');
				}
				$tpl->assign('selected_tab', $selected_tab);

		    	$tpl->assign('tab', $section);
				$tpl->display('devblocks:cerberusweb.core::preferences/index.tpl');
				break;
		}
	}

	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');

		$visit = CerberusApplication::getVisit();

		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id))
			&& null != ($inst = $tab_mft->createInstance())
			&& $inst instanceof Extension_PreferenceTab) {
				$visit->set(Extension_PreferenceTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}

	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');

		if(null != ($inst = DevblocksPlatform::getExtension($ext_id, true))
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
	
	function showWatcherTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Remember tab
		$visit->set(Extension_PreferenceTab::POINT, 'watcher');
		
		// Activities
		$activities = DevblocksPlatform::getActivityPointRegistry();
		$tpl->assign('activities', $activities);

		$dont_notify_on_activities = WorkerPrefs::getDontNotifyOnActivities($active_worker->id);
		$tpl->assign('dont_notify_on_activities', $dont_notify_on_activities);
		
		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/watcher/index.tpl');
	}
	
	function saveWatcherTabAction() {
		@$activity_points = DevblocksPlatform::importGPC($_REQUEST['activity_point'],'array',array());
		@$activity_points_enabled = DevblocksPlatform::importGPC($_REQUEST['activity_enable'],'array',array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dont_notify_on_activities = array_diff($activity_points, $activity_points_enabled);
		WorkerPrefs::setDontNotifyOnActivities($active_worker->id, $dont_notify_on_activities);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','watcher')));
	}

	function showMyNotificationsTabAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember tab
		$visit->set('cerberusweb.profiles.worker.'.$active_worker->id, 'notifications');

		// My Events
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'my_notifications';
		$defaults->class_name = 'View_Notification';
		$defaults->renderLimit = 25;
		$defaults->renderPage = 0;
		$defaults->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$defaults->renderSortAsc = false;

		$myNotificationsView = C4_AbstractViewLoader::getView('my_notifications', $defaults);

		$myNotificationsView->name = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());

		$myNotificationsView->addColumnsHidden(array(
			SearchFields_Notification::ID,
			SearchFields_Notification::WORKER_ID,
		));

		$myNotificationsView->addParamsHidden(array(
			SearchFields_Notification::ID,
			SearchFields_Notification::WORKER_ID,
		), true);
		$myNotificationsView->addParamsDefault(array(
			SearchFields_Notification::IS_READ => new DevblocksSearchCriteria(SearchFields_Notification::IS_READ,'=',0),
		), true);
		$myNotificationsView->addParamsRequired(array(
			SearchFields_Notification::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID,'=',$active_worker->id),
		), true);

		/*
		 * [TODO] This doesn't need to save every display, but it was possible to
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		C4_AbstractViewLoader::setView($myNotificationsView->id, $myNotificationsView);

		$tpl->assign('view', $myNotificationsView);
		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/notifications/index.tpl');
	}

	function showNotificationsBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }

		// Custom Fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/notifications/bulk.tpl');
	}

	function doNotificationsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();

	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);

		// Task fields
		$is_read = trim(DevblocksPlatform::importGPC($_POST['is_read'],'string',''));

		$do = array();

		// Do: Mark Read
		if(0 != strlen($is_read))
			$do['is_read'] = $is_read;

		// Do: Custom fields
		//$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}

		$view->doBulkUpdate($filter, $do, $ids);

		$view->render();
		return;
	}

	function viewNotificationsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();

		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());

		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;

		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=profiles&k=worker&id=me&tab=notifications', true),
					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.notifications',
				);
				$models[] = $model;

				$view->renderTotal = false; // speed up subsequent pages
			}

			if(is_array($results))
			foreach($results as $event_id => $row) {
				if($event_id==$explore_from)
					$orig_pos = $pos;

				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Notification::ID],
					'content' => $row[SearchFields_Notification::MESSAGE],
					'url' => $row[SearchFields_Notification::URL],
				);
				$models[] = $model;
			}

			DAO_ExplorerSet::createFromModels($models);

			$view->renderPage++;

		} while(!empty($results));

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}

	function explorerEventMarkReadAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);

		$worker = CerberusApplication::getActiveWorker();

		if(!empty($id)) {
			DAO_Notification::updateWhere(
				array(
					DAO_Notification::IS_READ => 1,
				),
				sprintf("%s = %d AND %s = %d",
					DAO_Notification::WORKER_ID,
					$worker->id,
					DAO_Notification::ID,
					$id
				)
			);

			DAO_Notification::clearCountCache($worker->id);
		}
	}

	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$worker = CerberusApplication::getActiveWorker();

		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		array_shift($stack); // preferences
		array_shift($stack); // redirectReadAction
		@$id = array_shift($stack); // id

		if(null != ($event = DAO_Notification::get($id))) {
			// Mark as read before we redirect
			DAO_Notification::update($id, array(
				DAO_Notification::IS_READ => 1
			));

			DAO_Notification::clearCountCache($worker->id);

			session_write_close();
			header("Location: " . $event->url);
		}
		exit;
	}

//	function showWorkspacesIntroTabAction() {
//		$translate = DevblocksPlatform::getTranslationService();
//		$active_worker = CerberusApplication::getActiveWorker();
//
//		$tpl = DevblocksPlatform::getTemplateService();
//
//		$tpl->display('devblocks:cerberusweb.core::home/tabs/workspaces_intro/index.tpl');
//	}

//	function doWorkspaceInitAction() {
//		$active_worker = CerberusApplication::getActiveWorker();
//		$visit = CerberusApplication::getVisit();
//
//		$fields = array(
//			DAO_Workspace::NAME => 'My First Workspace',
//			DAO_Workspace::WORKER_ID => $active_worker->id,
//		);
//		$workspace_id = DAO_Workspace::create($fields);
//
//		DAO_Workspace::setEndpointWorkspaces(Extension_PreferencesTab::POINT, $active_worker->id, $workspace_id);
//		$visit->set(Extension_PreferencesTab::POINT, 'w_'.$workspace_id);
//
//		// My Tickets
//
//		$list = new Model_WorkspaceListView();
//		$list->title = 'My Mail';
//		$list->columns = array(
//			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
//			SearchFields_Ticket::TICKET_UPDATED_DATE,
//			SearchFields_Ticket::TICKET_TEAM_ID,
//			SearchFields_Ticket::TICKET_CATEGORY_ID,
//		);
//		$list->params = array(
//			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
//			SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
//			SearchFields_Ticket::VIRTUAL_WATCHERS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WATCHERS,null,array($active_worker->id))
//		);
//		$list->num_rows = 5;
//
//		$fields = array(
//			DAO_WorkspaceList::WORKER_ID => $active_worker->id,
//			DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
//			DAO_WorkspaceList::LIST_POS => 1,
//			DAO_WorkspaceList::LIST_VIEW => serialize($list),
//			DAO_WorkspaceList::CONTEXT => CerberusContexts::CONTEXT_TICKET,
//		);
//		DAO_WorkspaceList::create($fields);
//
//		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences')));
//	}

	function showGeneralTabAction() {
		$date_service = DevblocksPlatform::getDateService();
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$visit->set(Extension_PreferenceTab::POINT, 'general');

		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);

		// [TODO] WorkerPrefs_*?
		$prefs = array();
		$prefs['assist_mode'] = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1)); 
		$prefs['keyboard_shortcuts'] = intval(DAO_WorkerPref::get($worker->id, 'keyboard_shortcuts', 1)); 
		$prefs['mail_always_show_all'] = DAO_WorkerPref::get($worker->id,'mail_always_show_all',0);
		$prefs['mail_reply_button'] = DAO_WorkerPref::get($worker->id,'mail_reply_button',0);
		$prefs['mail_status_compose'] = DAO_WorkerPref::get($worker->id,'mail_status_compose','waiting');
		$prefs['mail_status_create'] = DAO_WorkerPref::get($worker->id,'mail_status_create','open');
		$prefs['mail_status_reply'] = DAO_WorkerPref::get($worker->id,'mail_status_reply','waiting');
		$prefs['mail_signature_pos'] = DAO_WorkerPref::get($worker->id,'mail_signature_pos',2);
		$tpl->assign('prefs', $prefs);
		
		// Alternate addresses
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

		$tpl->display('devblocks:cerberusweb.core::preferences/modules/general.tpl');
	}

	function showRssTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();

		$visit->set(Extension_PreferenceTab::POINT, 'rss');

		$feeds = DAO_ViewRss::getByWorker($active_worker->id);
		$tpl->assign('feeds', $feeds);

		$tpl->display('devblocks:cerberusweb.core::preferences/modules/rss.tpl');
	}

	function saveDefaultsAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
		@$mail_signature_pos = DevblocksPlatform::importGPC($_REQUEST['mail_signature_pos'],'integer',0);

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
			DAO_Worker::update($worker->id, $fields);
		}

		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);

		@$keyboard_shortcuts = DevblocksPlatform::importGPC($_REQUEST['keyboard_shortcuts'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'keyboard_shortcuts', $keyboard_shortcuts);

		@$mail_always_show_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_show_all'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_always_show_all', $mail_always_show_all);

		@$mail_reply_button = DevblocksPlatform::importGPC($_REQUEST['mail_reply_button'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_reply_button', $mail_reply_button);
		
		@$mail_signature_pos = DevblocksPlatform::importGPC($_REQUEST['mail_signature_pos'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_signature_pos', $mail_signature_pos);

		@$mail_status_compose = DevblocksPlatform::importGPC($_REQUEST['mail_status_compose'],'string','waiting');
		DAO_WorkerPref::set($worker->id, 'mail_status_compose', $mail_status_compose);
		
		@$mail_status_create = DevblocksPlatform::importGPC($_REQUEST['mail_status_create'],'string','waiting');
		DAO_WorkerPref::set($worker->id, 'mail_status_create', $mail_status_create);

		@$mail_status_reply = DevblocksPlatform::importGPC($_REQUEST['mail_status_reply'],'string','waiting');
		DAO_WorkerPref::set($worker->id, 'mail_status_reply', $mail_status_reply);
		
		// Alternate Email Addresses
		@$new_email = DevblocksPlatform::importGPC($_REQUEST['new_email'],'string','');
		@$worker_emails = DevblocksPlatform::importGPC($_REQUEST['worker_emails'],'array',array());

		$current_addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$removed_addresses = array_diff(array_keys($current_addresses), $worker_emails);

		// Confirm deletions are assigned to the current worker
		foreach($removed_addresses as $removed_address) {
			if($removed_address == $worker->email)
				continue;

			DAO_AddressToWorker::unassign($removed_address);
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
				$settings->get('cerberusweb.core',CerberusSettings::HELPDESK_TITLE,CerberusSettingsDefaults::HELPDESK_TITLE)
			),
			vsprintf($translate->_('prefs.address.confirm.mail.body'),
				array(
					$worker->getName(),
					$url_writer->writeNoProxy('c=preferences&a=confirm_email&code='.$code,true)
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

class ChExplorerToolbarNotifications extends Extension_ExplorerToolbar {
	function render(Model_ExplorerSet $item) {
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('item', $item);

		$tpl->display('devblocks:cerberusweb.core::preferences/renderer/explorer_toolbar.tpl');
	}
};
