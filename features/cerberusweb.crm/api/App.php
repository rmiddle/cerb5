<?php
class CrmCustomFieldSource_Opportunity extends Extension_CustomFieldSource {
	const ID = 'crm.fields.source.opportunity';
};

class CrmNotesSource_Opportunity extends Extension_NoteSource {
	const ID = 'crm.notes.source.opportunity';
};

// Workspace Sources

class CrmWorkspaceSource_Opportunity extends Extension_WorkspaceSource {
	const ID = 'crm.workspace.source.opportunity';
};

if (class_exists('Extension_ActivityTab')):
class CrmOppsActivityTab extends Extension_ActivityTab {
	const EXTENSION_ID = 'crm.activity.tab.opps';
	const VIEW_ACTIVITY_OPPS = 'activity_opps';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('core_tpl', APP_PATH . '/features/cerberusweb.core/templates/');
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// Remember the tab
		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'opps');
		
		// Read original request
		@$request_path = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$tpl->assign('request_path', $request_path);

		@$stack =  explode('/', $request_path);
		@array_shift($stack); // activity
		@array_shift($stack); // opps
		
		switch(@array_shift($stack)) {
			case 'import':
				if(!$active_worker->hasPriv('crm.opp.actions.import'))
					break;

				switch(@array_shift($stack)) {
					case 'step2':
						// Load first row headings
						$csv_file = $visit->get('crm.import.last.csv','');
						$fp = fopen($csv_file, "rt");
						if($fp) {
							$parts = fgetcsv($fp, 8192, ',', '"');
							$tpl->assign('parts', $parts);
						}
						@fclose($fp);

						$fields = array(
							'name' => $translate->_('crm.opportunity.name'),
							'email' => $translate->_('crm.opportunity.email_address'),
							'created_date' => $translate->_('crm.opportunity.created_date'),
							'updated_date' => $translate->_('crm.opportunity.updated_date'),
							'closed_date' => $translate->_('crm.opportunity.closed_date'),
							'is_won' => $translate->_('crm.opportunity.is_won'),
							'is_closed' => $translate->_('crm.opportunity.is_closed'),
//							'worker_id' => $translate->_('crm.opportunity.worker_id'),
							'worker' => $translate->_('crm.opportunity.worker_id'),
							'amount' => $translate->_('crm.opportunity.amount'),
						);
						$tpl->assign('fields',$fields);
						
						$custom_fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
						$tpl->assign('custom_fields', $custom_fields);
						
						$workers = DAO_Worker::getAllActive();
						$tpl->assign('workers', $workers);
						
						$tpl->display($tpl_path . 'crm/opps/activity_tab/import/mapping.tpl');
						return;
						break;
						
				} // import:switch
				break;
		}
			
		// Index
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CrmOpportunity';
		$defaults->id = self::VIEW_ACTIVITY_OPPS;
		$defaults->name = $translate->_('crm.tab.title');
		$defaults->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_OPPS, $defaults);
		
		$tpl->assign('response_uri', 'activity/opps');

		$quick_search_type = $visit->get('crm.opps.quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', View_CrmOpportunity::getFields());
		$tpl->assign('view_searchable_fields', View_CrmOpportunity::getSearchFields());
		
		$tpl->display($tpl_path . 'crm/opps/activity_tab/index.tpl');		
	}
}
endif;

class CrmPage extends CerberusPageExtension {
	private $plugin_path = '';
	
	const SESSION_OPP_TAB = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->plugin_path = dirname(dirname(__FILE__)).'/';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->plugin_path . '/templates/';
		$tpl->assign('path', $tpl_path);

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // crm
		
		$module = array_shift($stack); // opps
		
		switch($module) {
			default:
			case 'opps':
				@$opp_id = intval(array_shift($stack));
				if(null == ($opp = DAO_CrmOpportunity::get($opp_id))) {
					break; // [TODO] Not found
				}
				$tpl->assign('opp', $opp);						

				if(null == (@$tab_selected = $stack[0])) {
					$tab_selected = $visit->get(self::SESSION_OPP_TAB, '');
				}
				$tpl->assign('tab_selected', $tab_selected);

				$address = DAO_Address::get($opp->primary_email_id);
				$tpl->assign('address', $address);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$task_count = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.opp', $opp_id);
				$tpl->assign('tasks_total', $task_count);
				
				$tpl->display($tpl_path . 'crm/opps/display/index.tpl');
				break;
		}
	}
	
	function showOppPanelAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('email', $email);
		
		if(!empty($opp_id) && null != ($opp = DAO_CrmOpportunity::get($opp_id))) {
			$tpl->assign('opp', $opp);
			
			if(null != ($address = DAO_Address::get($opp->primary_email_id))) {
				$tpl->assign('address', $address);
			}
		}
		
		$custom_fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($opp_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(CrmCustomFieldSource_Opportunity::ID, $opp_id);
			if(isset($custom_field_values[$opp->id]))
				$tpl->assign('custom_field_values', $custom_field_values[$opp->id]);
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/rpc/peek.tpl');
	}
	
	function saveOppPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount_dollars = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0');
		@$amount_cents = DevblocksPlatform::importGPC($_REQUEST['amount_cents'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		@$created_date_str = DevblocksPlatform::importGPC($_REQUEST['created_date'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip commas and decimals and put together the "dollars+cents"
		$amount = intval(str_replace(array(',','.'),'',$amount_dollars)).'.'.number_format($amount_cents,0,'','');
		
		// Dates
		if(false === ($created_date = strtotime($created_date_str)))
			$created_date = time();
			
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = ($is_closed) ? time() : 0;

		if(!$is_closed)
			$closed_date = 0;
			
		// Worker
		$active_worker = CerberusApplication::getActiveWorker();

		// Save
		if($do_delete) {
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id))) {
			
			// Check privs
			if(($active_worker->hasPriv('crm.opp.actions.create') && $active_worker->id==$opp->worker_id)
				|| ($active_worker->hasPriv('crm.opp.actions.update_nobody') && empty($opp->worker_id)) 
				|| $active_worker->hasPriv('crm.opp.actions.update_all'))
					DAO_CrmOpportunity::delete($opp_id);
			}
			
		} elseif(empty($opp_id)) {
			// Check privs
			if(!$active_worker->hasPriv('crm.opp.actions.create'))
				return;
			
			// One opportunity per provided e-mail address
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				return;
				
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
				DAO_CrmOpportunity::WORKER_ID => $worker_id,
			);
			$opp_id = DAO_CrmOpportunity::create($fields);
			
			// Custom fields
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CrmCustomFieldSource_Opportunity::ID, $opp_id, $field_ids);
			
			// If we're adding a first comment
			if(!empty($comment)) {
				$fields = array(
					DAO_Note::CREATED => time(),
					DAO_Note::SOURCE_EXTENSION_ID => CrmNotesSource_Opportunity::ID,
					DAO_Note::SOURCE_ID => $opp_id,
					DAO_Note::CONTENT => $comment,
					DAO_Note::WORKER_ID => $active_worker->id,
				);
				$comment_id = DAO_Note::create($fields);
			}
			
		} else {
			if(empty($opp_id))
				return;
			
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				return;

			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::PRIMARY_EMAIL_ID => $address->id,
				DAO_CrmOpportunity::CREATED_DATE => intval($created_date),
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => intval($closed_date),
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
				DAO_CrmOpportunity::WORKER_ID => $worker_id,
			);
			
			// Check privs
			if(null != ($opp = DAO_CrmOpportunity::get($opp_id))
				&& (
				($active_worker->hasPriv('crm.opp.actions.create') && $active_worker->id==$opp->worker_id) // owns
				|| ($active_worker->hasPriv('crm.opp.actions.update_nobody') && empty($opp->worker_id))  // can edit nobody
				|| $active_worker->hasPriv('crm.opp.actions.update_all')) // can edit anybody
			) {
				DAO_CrmOpportunity::update($opp_id, $fields);
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CrmCustomFieldSource_Opportunity::ID, $opp_id, $field_ids);
			}
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showOppTasksTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
		$visit->set(self::SESSION_OPP_TAB, 'tasks');
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Task';
		$defaults->id = 'opp_tasks';
		$defaults->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		
		$view = C4_AbstractViewLoader::getView('opp_tasks', $defaults);
		$view->name = 'Opportunity Tasks';

		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.opp'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$opp_id),
		);

		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
//		$view->name = "Most recent tickets from " . htmlentities($contact->email);
//		$view->params = array(
//			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
//		);
//		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/tasks.tpl');
	}
	
	function showOppMailTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Remember the selected tab
		$visit->set(self::SESSION_OPP_TAB, 'mail');
		
		// Opp
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		// Recall the history scope
		$scope = $visit->get('crm.opps.history.scope', '');

		// Addy
		$address = DAO_Address::get($opp->primary_email_id);
		$tpl->assign('address', $address);

		// Addy->Org
		if(!empty($address->contact_org_id)) {
			if(null != ($contact_org = DAO_ContactOrg::get($address->contact_org_id)))
				$tpl->assign('contact_org', $contact_org);
		}
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = 'opp_tickets';
		$defaults->name = '';
		$defaults->renderPage = 0;
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
		);
		
		$view = C4_AbstractViewLoader::getView('opp_tickets', $defaults);

		// Sanitize scope options
		if('org'==$scope && empty($contact_org))
			$scope = '';
		if('domain'==$scope) {
			$email_parts = explode('@', $address->email);
			if(!is_array($email_parts) || 2 != count($email_parts))
				$scope = '';
		}

		switch($scope) {
			case 'org':
				$view->params = array(
					SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,'=',$address->contact_org_id),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('contact_org.name')) . ": " . $contact_org->name;
				break;
				
			case 'domain':
				$view->params = array(
					SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'like','*@'.$email_parts[1]),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('common.email')) . ": *@" . $email_parts[1];
				break;
				
			default:
			case 'email':
				$scope = 'email';
				$view->params = array(
					SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array($opp->primary_email_id)),
					SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',0),
				);
				$view->name = ucwords($translate->_('common.email')) . ": " . $address->email;
				break;
		}
		
		$tpl->assign('scope', $scope);
		
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/mail.tpl');
	}
	
	function doOppHistoryScopeAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','');
		
		$visit = CerberusApplication::getVisit();

		$visit->set('crm.opps.history.scope', $scope);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opps',$opp_id,'mail')));
	}
	
	function showOppPropertiesTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
		$visit->set(self::SESSION_OPP_TAB, 'properties');
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		$address = DAO_Address::get($opp->primary_email_id);
		$tpl->assign('address', $address);

		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$custom_fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(CrmCustomFieldSource_Opportunity::ID, $opp->id);
		if(isset($custom_field_values[$opp->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$opp->id]);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/properties.tpl');
	}
	
	function saveOppPropertiesAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer', 0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'integer',0);
		@$amount_dollars = DevblocksPlatform::importGPC($_REQUEST['amount'],'string','0');
		@$amount_cents = DevblocksPlatform::importGPC($_REQUEST['amount_cents'],'integer',0);
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$created_date_str = DevblocksPlatform::importGPC($_REQUEST['created_date'],'string','');
		@$closed_date_str = DevblocksPlatform::importGPC($_REQUEST['closed_date'],'string','');
		
		// State
		$is_closed = (0==$status) ? 0 : 1;
		$is_won = (1==$status) ? 1 : 0;
		
		// Strip commas and decimals and put together the "dollars+cents"
		$amount = intval(str_replace(array(',','.'),'',$amount_dollars)).'.'.number_format($amount_cents,0,'','');

		// Dates
		if(false === ($created_date = strtotime($created_date_str)))
			$created_date = time();
			
		if(false === ($closed_date = strtotime($closed_date_str)))
			$closed_date = ($is_closed) ? time() : 0;

		if(!$is_closed)
			$closed_date = 0;
		
		if(!empty($opp_id)) {
			$fields = array(
				DAO_CrmOpportunity::NAME => $name,
				DAO_CrmOpportunity::AMOUNT => $amount,
				DAO_CrmOpportunity::CREATED_DATE => $created_date,
				DAO_CrmOpportunity::UPDATED_DATE => time(),
				DAO_CrmOpportunity::CLOSED_DATE => $closed_date,
				DAO_CrmOpportunity::IS_CLOSED => $is_closed,
				DAO_CrmOpportunity::IS_WON => $is_won,
				DAO_CrmOpportunity::WORKER_ID => $worker_id,
			);
			
			// Email
			if(null != ($address = DAO_Address::lookupAddress($email, true)))
				$fields[DAO_CrmOpportunity::PRIMARY_EMAIL_ID] = $address->id;
			
			DAO_CrmOpportunity::update($opp_id, $fields);
			
			@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(CrmCustomFieldSource_Opportunity::ID, $opp_id, $field_ids);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opp',$opp_id)));
	}
	
	function showOppNotesTabAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$visit = CerberusApplication::getVisit();
		$visit->set(self::SESSION_OPP_TAB, 'notes');
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		$tpl->assign('opp', $opp);

		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',CrmNotesSource_Opportunity::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$opp->id),
			),
			25,
			0,
			SearchFields_Note::CREATED,
			false,
			false
		);
		$tpl->assign('notes', $notes);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);
				
		$tpl->display('file:' . $tpl_path . 'crm/opps/display/tabs/notes.tpl');
	}
	
	function saveOppNoteAction() {
		@$opp_id = DevblocksPlatform::importGPC($_REQUEST['opp_id'],'integer', 0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($opp_id) && 0 != strlen(trim($content))) {
			$fields = array(
				DAO_Note::SOURCE_EXTENSION_ID => CrmNotesSource_Opportunity::ID,
				DAO_Note::SOURCE_ID => $opp_id,
				DAO_Note::WORKER_ID => $active_worker->id,
				DAO_Note::CREATED => time(),
				DAO_Note::CONTENT => $content,
			);
			$note_id = DAO_Note::create($fields);
		}
		
		$opp = DAO_CrmOpportunity::get($opp_id);
		
		// Worker notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=crm&a=opps&id='.$opp_id,true),
				DAO_WorkerEvent::TITLE => 'New Opportunity Note', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s\n%s notes: %s", $opp->name, $active_worker->getName(), $content), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('crm','opp',$opp_id)));
	}
	
	function showOppBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('opp_ids', implode(',', $id_list));
	    }
		
	    // Workers
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('file:' . dirname(dirname(__FILE__)) . '/templates/crm/opps/bulk.tpl');
	}
	
	function doOppBulkUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Checked rows
	    @$opp_ids_str = DevblocksPlatform::importGPC($_REQUEST['opp_ids'],'string');
		$opp_ids = DevblocksPlatform::parseCsvString($opp_ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Opp fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));
		@$closed_date = trim(DevblocksPlatform::importGPC($_POST['closed_date'],'string',''));
		@$worker_id = trim(DevblocksPlatform::importGPC($_POST['worker_id'],'string',''));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
		// Do: Closed Date
		if(0 != strlen($closed_date))
			@$do['closed_date'] = intval(strtotime($closed_date));
		// Do: Worker
		if(0 != strlen($worker_id))
			$do['worker_id'] = $worker_id;
			
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('crm.opp.view.actions.broadcast')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_group_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_group_id'],'integer',0);
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			if(0 != strlen($do_broadcast) && !empty($broadcast_subject) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'subject' => $broadcast_subject,
					'message' => $broadcast_message,
					'is_queued' => $broadcast_is_queued,
					'group_id' => $broadcast_group_id,
					'worker_id' => $active_worker->id,
				);
			}
		}
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, $do, $opp_ids);
		
		$view->render();
		return;
	}
	
	function doOppBulkUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$view = C4_AbstractViewLoader::getView($view_id);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('crm.opp.view.actions.broadcast')) {
			@$broadcast_subject = DevblocksPlatform::importGPC($_REQUEST['broadcast_subject'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			// Get total
			$view->renderPage = 0;
			$view->renderLimit = 1;
			$view->renderTotal = true;
			list($null, $total) = $view->getData();
			
			// Get the first row from the view
			$view->renderPage = mt_rand(0, $total-1);
			$view->renderLimit = 1;
			$view->renderTotal = false;
			list($results, $null) = $view->getData();
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				@$opp = DAO_CrmOpportunity::get(key($results));
				
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp, $token_labels, $token_values);

				if(empty($broadcast_subject)) {
					$success = false;
					$output = "Subject is blank.";
				
				} else {
					$template = "Subject: $broadcast_subject\n\n$broadcast_message";
					
					if(false === ($out = $tpl_builder->build($template, $token_values))) {
						// If we failed, show the compile errors
						$errors = $tpl_builder->getErrors();
						$success= false;
						$output = @array_shift($errors);
					} else {
						// If successful, return the parsed template
						$success = true;
						$output = $out;
					}
				}
			}
			
			$tpl->assign('success', $success);
			$tpl->assign('output', htmlentities($output, null, LANG_CHARSET_CODE));
			
			$core_tpl_path = APP_PATH . '/features/cerberusweb.core/templates/';
			
			$tpl->display('file:'.$core_tpl_path.'internal/renderers/test_results.tpl');
		}
	}	
	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $translate = DevblocksPlatform::getTranslationService();
		
        if(null == ($searchView = C4_AbstractViewLoader::getView(CrmOppsActivityTab::VIEW_ACTIVITY_OPPS))) {
        	$searchView = new View_CrmOpportunity();
        	$searchView->id = CrmOppsActivityTab::VIEW_ACTIVITY_OPPS;
        	$searchView->name = $translate->_('common.search_results');
        	C4_AbstractViewLoader::setView($searchView->id, $searchView);
        }
		
		$visit->set('crm.opps.quick_search_type', $type);
		
        $params = array();
        
        switch($type) {
            case "title":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::NAME] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::NAME,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
            case "email":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::EMAIL_ADDRESS] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::EMAIL_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
            case "org":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_CrmOpportunity::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,$query);      
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','opps')));
	}
	
	// Ajax
	function showImportPanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->display($tpl_path . 'crm/opps/activity_tab/import/panel.tpl');		
	}
	
	// Post
	function parseUploadAction() {
		@$csv_file = $_FILES['csv_file'];

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;

		if(!is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps')));
			return;
		}
		
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = APP_TEMP_PATH . '/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps')));
			return; // [TODO] Throw error
		}
		
		$visit->set('crm.import.last.csv', $newfilename);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('activity','opps','import','step2')));
	}
	
	// Post
	function doImportAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('crm.opp.actions.import'))
			return;
		
		@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array',array());
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$sync_dupes = DevblocksPlatform::importGPC($_REQUEST['sync_dupes'],'array',array());
		@$include_first = DevblocksPlatform::importGPC($_REQUEST['include_first'],'integer',0);
		@$is_blank_unset = DevblocksPlatform::importGPC($_REQUEST['is_blank_unset'],'integer',0);
		@$opt_assign = DevblocksPlatform::importGPC($_REQUEST['opt_assign'],'integer',0);
		@$opt_assign_worker_id = DevblocksPlatform::importGPC($_REQUEST['opt_assign_worker_id'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		
		$workers = DAO_Worker::getAllActive();
		
		$csv_file = $visit->get('crm.import.last.csv','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp) return;

		// [JAS]: Do we need to consume a first row of headings?
		if(!$include_first)
			@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			
			if(empty($parts) || (1==count($parts) && is_null($parts[0])))
				continue;
			
			$fields = array();
			$custom_fields = array();
			$sync_fields = array();
			
			foreach($pos as $idx => $p) {
				$key = $field[$idx];
				$val = $parts[$idx];
				
				// Special handling
				if(!empty($key)) {
					switch($key) {
						case 'amount':
							if(0 != strlen($val) && is_numeric($val)) {
								@$val = floatval($val);
							} else {
								unset($key);
							}
							break;
						// Translate e-mail address to ID
						case 'email':
							if(null != ($addy = CerberusApplication::hashLookupAddress($val,true))) {
								$key = 'primary_email_id';
								$val = $addy->id;
							} else {
								unset($key);
							}
							break;
						
						// Bools
						case 'is_won':
						case 'is_closed':
							if(0 != strlen($val)) {
								@$val = !empty($val) ? 1 : 0;
							} else {
								unset($key);
							}
							break;
													
						// Dates
						case 'created_date':
						case 'updated_date':
						case 'closed_date':
							if(0 != strlen($val)) {
								@$val = !is_numeric($val) ? strtotime($val) : $val;
							} else {
								unset($key);
							}
							break;

						// Worker by name							
						case 'worker':
							unset($key);
							if(is_array($workers))
							foreach($workers as $worker_id=>$worker)
								if(0==strcasecmp($val,$worker->getName())) {
									$key = 'worker_id';
									$val = $worker_id;
								}
							break;
							
					}

					if(!isset($key))
						continue;

					// Custom fields
					if('cf_' == substr($key,0,3)) {
						$custom_fields[substr($key,3)] = $val;
					} elseif(!empty($key)) {
						$fields[$key] = $val;
					}
					
					// Find dupe combos
					if(in_array($idx,$sync_dupes)) {
						$search_field = '';
						$search_val = '';
						
						switch($key) {
							case 'primary_email_id':
								$search_field = SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID;
								$search_val = intval($val);
								break;
							case 'title':
								$search_field = SearchFields_CrmOpportunity::NAME;
								$search_val = $val;
								break;
							case 'amount':
								$search_field = SearchFields_CrmOpportunity::AMOUNT;
								$search_val = floatval($val);
								break;
							case 'is_won':
								$search_field = SearchFields_CrmOpportunity::IS_WON;
								$search_val = intval($val);
								break;
							case 'is_closed':
								$search_field = SearchFields_CrmOpportunity::IS_CLOSED;
								$search_val = intval($val);
								break;
							case 'created_date':
								$search_field = SearchFields_CrmOpportunity::CREATED_DATE;
								$search_val = intval($val);
								break;
							case 'updated_date':
								$search_field = SearchFields_CrmOpportunity::UPDATED_DATE;
								$search_val = intval($val);
								break;
							case 'closed_date':
								$search_field = SearchFields_CrmOpportunity::CLOSED_DATE;
								$search_val = intval($val);
								break;
							case 'worker_id':
								$search_field = SearchFields_CrmOpportunity::WORKER_ID;
								$search_val = intval($val);
								break;
							default:
								// Custom field dupe
								if('cf_'==substr($key,0,3)) {
									$search_field = $key;
									// [TODO] Need to format this for proper custom fields
									$search_val = $val;
								}
								break;
						}
						
						if(!empty($search_field) && !empty($search_val))
							$sync_fields[$search_field] = new DevblocksSearchCriteria($search_field,'=',$search_val);
					}
				}
			} // end foreach($pos)
			
			// Dupe checking
			if(!empty($fields) && !empty($sync_fields)) {
				list($dupes,$null) = DAO_CrmOpportunity::search(
					array(),
					$sync_fields,
					1, // only need 1 to be a dupe
					0,
					null,
					false,
					false
				);
			}
			
			if(!empty($fields)) {
				if(isset($fields['primary_email_id'])) {
					// Make sure a minimum amount of fields are provided
					if(!isset($fields[DAO_CrmOpportunity::UPDATED_DATE]))
						$fields[DAO_CrmOpportunity::UPDATED_DATE] = time();
					
					if($opt_assign && !isset($fields[DAO_CrmOpportunity::WORKER_ID]))
						$fields[DAO_CrmOpportunity::WORKER_ID] = $opt_assign_worker_id;
					
					if(empty($dupes)) {
						// [TODO] Provide an import prefix for blank names
						if(!isset($fields[DAO_CrmOpportunity::NAME]) && isset($addy))
							$fields[DAO_CrmOpportunity::NAME] = $addy->email;
						if(!isset($fields[DAO_CrmOpportunity::CREATED_DATE]))
							$fields[DAO_CrmOpportunity::CREATED_DATE] = time();
						$id = DAO_CrmOpportunity::create($fields);
						
					} else {
						$id = key($dupes);
						DAO_CrmOpportunity::update($id, $fields);
					}
				}
			}
			
			if(!empty($custom_fields) && !empty($id)) {
				// Format (typecast) and set the custom field types
				$source_ext_id = CrmCustomFieldSource_Opportunity::ID;
				DAO_CustomFieldValue::formatAndSetFieldValues($source_ext_id, $id, $custom_fields, $is_blank_unset, true, true);
			}
			
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('crm.import.last.csv',null);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('activity','opps')));
	}
	
	function viewOppsExploreAction() {
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
		$view->renderLimit = 25;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=activity&tab=opps', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CrmOpportunity::ID],
					'url' => $url_writer->write(sprintf("c=crm&tab=opps&id=%d", $row[SearchFields_CrmOpportunity::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
};

class DAO_CrmOpportunity extends C4_ORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const AMOUNT = 'amount';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const CREATED_DATE = 'created_date';
	const UPDATED_DATE = 'updated_date';
	const CLOSED_DATE = 'closed_date';
	const IS_WON = 'is_won';
	const IS_CLOSED = 'is_closed';
	const WORKER_ID = 'worker_id';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('crm_opportunity_seq');
		
		$sql = sprintf("INSERT INTO crm_opportunity (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		// New opportunity
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'opportunity.create',
                array(
                    'opp_id' => $id,
                	'fields' => $fields,
                )
            )
	    );
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'crm_opportunity', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('crm_opportunity', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_CrmOpportunity[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, amount, primary_email_id, created_date, updated_date, closed_date, is_won, is_closed, worker_id ".
			"FROM crm_opportunity ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_CrmOpportunity	 */
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
	 * @return Model_CrmOpportunity[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CrmOpportunity();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->amount = doubleval($row['amount']);
			$object->primary_email_id = intval($row['primary_email_id']);
			$object->created_date = $row['created_date'];
			$object->updated_date = $row['updated_date'];
			$object->closed_date = $row['closed_date'];
			$object->is_won = $row['is_won'];
			$object->is_closed = $row['is_closed'];
			$object->worker_id = $row['worker_id'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getItemCount() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetOne("SELECT count(id) FROM crm_opportunity");
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();

	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		// Opps
		$db->Execute(sprintf("DELETE QUICK FROM crm_opportunity WHERE id IN (%s)", $ids_list));

		// Custom fields
		DAO_CustomFieldValue::deleteBySourceIds(CrmCustomFieldSource_Opportunity::ID, $ids);
		
		// Notes
		DAO_Note::deleteBySourceIds(CrmNotesSource_Opportunity::ID, $ids);
		
		return true;
	}

    /**
     * Enter description here...
     *
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
		$fields = SearchFields_CrmOpportunity::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"o.id as %s, ".
			"o.name as %s, ".
			"o.amount as %s, ".
			"org.id as %s, ".
			"org.name as %s, ".
			"o.primary_email_id as %s, ".
			"a.email as %s, ".
			"o.created_date as %s, ".
			"o.updated_date as %s, ".
			"o.closed_date as %s, ".
			"o.is_closed as %s, ".
			"o.is_won as %s, ".
			"o.worker_id as %s ",
			    SearchFields_CrmOpportunity::ID,
			    SearchFields_CrmOpportunity::NAME,
			    SearchFields_CrmOpportunity::AMOUNT,
			    SearchFields_CrmOpportunity::ORG_ID,
			    SearchFields_CrmOpportunity::ORG_NAME,
			    SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,
			    SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			    SearchFields_CrmOpportunity::CREATED_DATE,
			    SearchFields_CrmOpportunity::UPDATED_DATE,
			    SearchFields_CrmOpportunity::CLOSED_DATE,
			    SearchFields_CrmOpportunity::IS_CLOSED,
			    SearchFields_CrmOpportunity::IS_WON,
			    SearchFields_CrmOpportunity::WORKER_ID
			);
			
		$join_sql = 
			"FROM crm_opportunity o ".
			"LEFT JOIN address a ON (a.id = o.primary_email_id) ".
			"LEFT JOIN contact_org org ON (org.id = a.contact_org_id) "
		;
			
			// [JAS]: Dynamic table joins
//			(isset($tables['m']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'o.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY o.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_CrmOpportunity::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT o.id) " : "SELECT COUNT(o.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
};

class SearchFields_CrmOpportunity implements IDevblocksSearchFields {
	// Table
	const ID = 'o_id';
	const PRIMARY_EMAIL_ID = 'o_primary_email_id';
	const NAME = 'o_name';
	const AMOUNT = 'o_amount';
	const CREATED_DATE = 'o_created_date';
	const UPDATED_DATE = 'o_updated_date';
	const CLOSED_DATE = 'o_closed_date';
	const IS_WON = 'o_is_won';
	const IS_CLOSED = 'o_is_closed';
	const WORKER_ID = 'o_worker_id';
	
	const ORG_ID = 'org_id';
	const ORG_NAME = 'org_name';

	const EMAIL_ADDRESS = 'a_email';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'o', 'id', $translate->_('crm.opportunity.id')),
			
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'o', 'primary_email_id', $translate->_('crm.opportunity.primary_email_id')),
			self::EMAIL_ADDRESS => new DevblocksSearchField(self::EMAIL_ADDRESS, 'a', 'email', $translate->_('crm.opportunity.email_address')),
			
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'org', 'id'),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'org', 'name', $translate->_('crm.opportunity.org_name')),
			
			self::NAME => new DevblocksSearchField(self::NAME, 'o', 'name', $translate->_('crm.opportunity.name')),
			self::AMOUNT => new DevblocksSearchField(self::AMOUNT, 'o', 'amount', $translate->_('crm.opportunity.amount')),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'o', 'created_date', $translate->_('crm.opportunity.created_date')),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 'o', 'updated_date', $translate->_('crm.opportunity.updated_date')),
			self::CLOSED_DATE => new DevblocksSearchField(self::CLOSED_DATE, 'o', 'closed_date', $translate->_('crm.opportunity.closed_date')),
			self::IS_WON => new DevblocksSearchField(self::IS_WON, 'o', 'is_won', $translate->_('crm.opportunity.is_won')),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'o', 'is_closed', $translate->_('crm.opportunity.is_closed')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'o', 'worker_id', $translate->_('crm.opportunity.worker_id')),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};	

class Model_CrmOpportunity {
	public $id;
	public $name;
	public $amount;
	public $primary_email_id;
	public $created_date;
	public $updated_date;
	public $closed_date;
	public $is_won;
	public $is_closed;
	public $worker_id;
};

class View_CrmOpportunity extends C4_AbstractView {
	const DEFAULT_ID = 'crm_opportunities';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Opportunities';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_CrmOpportunity::UPDATED_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::AMOUNT,
			SearchFields_CrmOpportunity::UPDATED_DATE,
			SearchFields_CrmOpportunity::WORKER_ID,
		);
		
		$this->params = array(
			SearchFields_CrmOpportunity::IS_CLOSED => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
		);
	}

	function getData() {
		$objects = DAO_CrmOpportunity::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(CrmCustomFieldSource_Opportunity::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.crm/templates/crm/opps/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;

			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_CrmOpportunity::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
						$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
						continue;
					else
						$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	// [TODO] change globally to getColumnFields() in AbstractView
	static function getFields() {
		$fields = SearchFields_CrmOpportunity::getFields();
		return $fields;
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_CrmOpportunity::ID]);
		unset($fields[SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID]);
		unset($fields[SearchFields_CrmOpportunity::ORG_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_CrmOpportunity::NAME:
			case SearchFields_CrmOpportunity::ORG_NAME:
			case SearchFields_CrmOpportunity::EMAIL_ADDRESS:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_CrmOpportunity::AMOUNT:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_CrmOpportunity::IS_CLOSED:
			case SearchFields_CrmOpportunity::IS_WON:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_CrmOpportunity::CREATED_DATE:
			case SearchFields_CrmOpportunity::UPDATED_DATE:
			case SearchFields_CrmOpportunity::CLOSED_DATE:		
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_CrmOpportunity::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
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
				case 'status':
					switch(strtolower($v)) {
						case 'open':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 0;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = 0;
							break;
						case 'won':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 1;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
						case 'lost':
							$change_fields[DAO_CrmOpportunity::IS_CLOSED] = 1;
							$change_fields[DAO_CrmOpportunity::IS_WON] = 0;
							$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = time();
							break;
					}
					break;
				case 'closed_date':
					$change_fields[DAO_CrmOpportunity::CLOSED_DATE] = intval($v);
					break;
				case 'worker_id':
					$change_fields[DAO_CrmOpportunity::WORKER_ID] = intval($v);
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects, $null) = DAO_CrmOpportunity::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_CrmOpportunity::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			
		} while(!empty($objects));

		// Broadcast?
		if(isset($do['broadcast'])) {
			$tpl_builder = DevblocksPlatform::getTemplateBuilder();
			
			$params = $do['broadcast'];
			if(
				!isset($params['worker_id']) 
				|| empty($params['worker_id'])
				|| !isset($params['subject']) 
				|| empty($params['subject'])
				|| !isset($params['message']) 
				|| empty($params['message'])
				)
				break;

			$is_queued = (isset($params['is_queued']) && $params['is_queued']) ? true : false; 
			
			if(is_array($ids))
			foreach($ids as $opp_id) {
				try {
					CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $tpl_labels, $tpl_tokens);
					$subject = $tpl_builder->build($params['subject'], $tpl_tokens);
					$body = $tpl_builder->build($params['message'], $tpl_tokens);
					
					$fields = array(
						DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
						DAO_MailQueue::TICKET_ID => 0,
						DAO_MailQueue::WORKER_ID => $params['worker_id'],
						DAO_MailQueue::UPDATED => time(),
						DAO_MailQueue::HINT_TO => $tpl_tokens['email_address'],
						DAO_MailQueue::SUBJECT => $subject,
						DAO_MailQueue::BODY => $body,
						DAO_MailQueue::PARAMS_JSON => json_encode(array(
							'to' => $tpl_tokens['email_address'],
							'group_id' => $params['group_id'],
						)),
					);
					
					if($is_queued) {
						$fields[DAO_MailQueue::IS_QUEUED] = 1;
					}
					
					$draft_id = DAO_MailQueue::create($fields);
					
				} catch (Exception $e) {
					// [TODO] ...
				}
			}
		}		
		
		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_CrmOpportunity::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CrmCustomFieldSource_Opportunity::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}	
};	

class CrmEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'cron.maint':
            	DAO_CrmOpportunity::maint();
            	break;
        }
    }
};

class CrmTaskSource_Opp extends Extension_TaskSource {
	function getSourceName() {
		return "Opportunities";
	}
	
	function getSourceInfo($object_id) {
		if(null == ($opp = DAO_CrmOpportunity::get($object_id)))
			return;
		
		$url = DevblocksPlatform::getUrlService();
		return array(
			'name' => '[Opp] '.$opp->name,
			'url' => $url->write(sprintf('c=crm&a=opps&id=%d',$opp->id)),
		);
	}
};

class CrmOrgOppTab extends Extension_OrgTab {
	function showTab() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org_id', $org_id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CrmOpportunity';
		$defaults->id = 'org_opps';
		$defaults->view_columns = array(
			SearchFields_CrmOpportunity::EMAIL_ADDRESS,
			SearchFields_CrmOpportunity::ORG_NAME,
			SearchFields_CrmOpportunity::AMOUNT,
			SearchFields_CrmOpportunity::UPDATED_DATE,
			SearchFields_CrmOpportunity::WORKER_ID,
		);
		
		$view = C4_AbstractViewLoader::getView('org_opps', $defaults);
		
		$view->name = "Org: " . $org->name;
		$view->params = array(
			SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org_id) 
		);

		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/org/tab.tpl');
	}
	
	function saveTab() {
	}
};

class CrmTicketOppTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		
		$address = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('address', $address);
		
		if(null == ($view = C4_AbstractViewLoader::getView('ticket_opps'))) {
			$view = new View_CrmOpportunity();
			$view->id = 'ticket_opps';
		}

		if(!empty($address->contact_org_id)) { // org
			@$org = DAO_ContactOrg::get($address->contact_org_id);
			
			$view->name = "Org: " . $org->name;
			$view->params = array(
				SearchFields_CrmOpportunity::ORG_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::ORG_ID,'=',$org->id) 
			);
			
		} else { // address
			$view->name = "Requester: " . $address->email;
			$view->params = array(
				SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID => new DevblocksSearchCriteria(SearchFields_CrmOpportunity::PRIMARY_EMAIL_ID,'=',$ticket->first_wrote_address_id) 
			);
		}
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $tpl_path . 'crm/opps/ticket/tab.tpl');
	}
	
	function saveTab() {
	}
};
