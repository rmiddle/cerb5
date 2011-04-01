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
class ChGroupsPage extends CerberusPageExtension  {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function getActivity() {
	    return new Model_Activity('activity.default');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack); // groups
		
    	$groups = DAO_Group::getAll();
    	$tpl->assign('groups', $groups);
    	
    	@$team_id = intval(array_shift($stack)); // team_id

		// Only group managers and superusers can configure
		if(empty($team_id) || (!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)) {
			// do nothing (only show list)
			
		} else {
			$teams = DAO_Group::getAll();
			
			$team =& $teams[$team_id];
	    	$tpl->assign('team', $team);
	    	
			// Remember the last tab/URL
			if(null == ($selected_tab = @$response->path[2])) {
				$selected_tab = $visit->get('cerberusweb.groups.tab', '');
			}
			$tpl->assign('selected_tab', $selected_tab);
		}
    	
		$tpl->display('devblocks:cerberusweb.core::groups/index.tpl');
	}
	
	function showTabMailAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'mail');
		
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$team_categories = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('categories', $team_categories);
	    
		$group_settings = DAO_GroupSettings::getSettings($group_id);
		$tpl->assign('group_settings', $group_settings);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::groups/manage/index.tpl');
	}
	
	function showTabInboxAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();

		$visit->set('cerberusweb.groups.tab', 'inbox');
		
		$tpl->assign('group_id', $group_id);
		
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$team_rules = DAO_GroupInboxFilter::getByGroupId($group_id);
		$tpl->assign('team_rules', $team_rules);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Custom Field Sources
		$tpl->assign('context_manifests', Extension_DevblocksContext::getAll());
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/filters/index.tpl');
	}
	
	function saveTabInboxAction() {
	    @$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    
	    // Deletes
	    if(!empty($group_id) && !empty($deletes)) {
	        DAO_GroupInboxFilter::delete($deletes);
	    }
	    
	    // Reordering
	    if(is_array($sticky_ids) && is_array($sticky_order))
	    foreach($sticky_ids as $idx => $id) {
	    	@$order = intval($sticky_order[$idx]);
	    	DAO_GroupInboxFilter::update($id, array(
	    		DAO_GroupInboxFilter::STICKY_ORDER => $order
	    	));
	    }
	    
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
   	
   	function showInboxFilterPanelAction() {
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
   		
		$tpl->assign('group_id', $group_id);
		$tpl->assign('view_id', $view_id);
		
		if(null != ($filter = DAO_GroupInboxFilter::get($id))) {
			$tpl->assign('filter', $filter);
		}

		// Make sure we're allowed to change this group's setup
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		// Load the example ticket + headers if provided
		if(!empty($ticket_id)) {
			$ticket = DAO_Ticket::get($ticket_id);
			$tpl->assign('ticket', $ticket);
	
			$messages = $ticket->getMessages();
			$message = array_shift($messages); /* @var $message Model_Message */
			$message_headers = $message->getHeaders();
			$tpl->assign('message', $message);
			$tpl->assign('message_headers', $message_headers);
		}
		
		$category_name_hash = DAO_Bucket::getCategoryNameHash();
		$tpl->assign('category_name_hash', $category_name_hash);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom Fields: Address
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('org_fields', $org_fields);
		
		// Custom Fields: Tickets
		$ticket_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/filters/peek.tpl');
   	}
   	
   	function saveTabInboxAddAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
   		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
   		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
		@$is_stackable = DevblocksPlatform::importGPC($_POST['is_stackable'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('mail.inbox_filter');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'dayofweek':
					// days
					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
					unset($criteria['value']);
					break;
				case 'timeofday':
					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
					$criteria['from'] = $from;
					$criteria['to'] = $to;
					unset($criteria['value']);
					break;
				case 'subject':
					break;
				case 'from':
					break;
				case 'tocc':
					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				case 'attachment':
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
							case 'W': // worker
								$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
							case 'E': // date
								$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
								$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
							case 'N': // number
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
							case 'C': // checkbox
								$criteria['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Move group/bucket
				case 'move':
					@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
					if(0 != strlen($move_code)) {
						list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				// Watchers
				case 'owner':
					@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner'],'array',array());
					if(!empty($worker_ids))
						$action = array(
							'add' => $worker_ids
						);
					break;
				// Spam training
				case 'spam':
					@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
					if(0 != strlen($is_spam))
						$action = array(
							'is_spam' => (!$is_spam?0:1)
						);
					break;
				// Set status
				case 'status':
					@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
					if(0 != strlen($status)) {
						$action = array(
							'is_waiting' => (3==$status?1:0), // explicit waiting
							'is_closed' => ((0==$status||3==$status)?0:1), // not open or waiting
							'is_deleted' => (2==$status?1:0), // explicit deleted
						);
					}
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($act,0,3)) {
						$field_id = intval(substr($act,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						$action = array();
							
						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'D': // dropdown
							case 'U': // URL
							case 'W': // worker
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
								$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$action['value'] = $out_array;
								break;
							case 'E': // date
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'N': // number
							case 'C': // checkbox
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					break;
			}
			
			$actions[$act] = $action;
		}

   		$fields = array(
   			DAO_GroupInboxFilter::NAME => $name,
   			DAO_GroupInboxFilter::IS_STICKY => $is_sticky,
   			DAO_GroupInboxFilter::CRITERIA_SER => serialize($criterion),
   			DAO_GroupInboxFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Only sticky filters can manual order and be stackable
   		if(!$is_sticky) {
   			$fields[DAO_GroupInboxFilter::STICKY_ORDER] = 0;
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = 0;
   		} else { // is sticky
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = $is_stackable;
   		}
   		
   		// Create
   		if(empty($id)) {
   			$fields[DAO_GroupInboxFilter::GROUP_ID] = $group_id;
   			$fields[DAO_GroupInboxFilter::POS] = 0;
	   		$id = DAO_GroupInboxFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_GroupInboxFilter::update($id, $fields);
   		}
   		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		if(!empty($view_id) && null != $view) {
			/* @var $view View_Ticket */

			// Loop through all the tickets in this inbox
	   		list($inbox_tickets, $null) = DAO_Ticket::search(
	   			null,
	   			array(
	   				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$group_id),
	   				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=','0'),
	   			),
	   			-1,
	   			0,
	   			null,
	   			null,
	   			false
	   		);
	   		
	   		if(is_array($inbox_tickets))
	   		foreach($inbox_tickets as $inbox_ticket) { /* @var $inbox_ticket Model_Ticket */
	   			// Run only this new rule against all tickets in the group inbox
	   			CerberusApplication::runGroupRouting($group_id, intval($inbox_ticket[SearchFields_Ticket::TICKET_ID]), $id);
	   		}
	   		
	   		$view->render();
			return;
		}
		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
	
	// Post
	function saveTabMailAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');

	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    	
	    //========== GENERAL
	    @$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
	    @$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');

	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
	       
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id)));
	}
	
	function showTabMembersAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'members');
		
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('team', $group);
		}
		
		$members = DAO_Group::getTeamMembers($group_id);
	    $tpl->assign('members', $members);
	    
		$workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/members.tpl');
	}
	
	function saveTabMembersAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_REQUEST['worker_levels'],'array',array());
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    @$members = DAO_Group::getTeamMembers($team_id);
	    
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    
	    if(is_array($worker_ids) && !empty($worker_ids))
	    foreach($worker_ids as $idx => $worker_id) {
	    	@$level = $worker_levels[$idx];
	    	if(isset($members[$worker_id]) && empty($level)) {
	    		DAO_Group::unsetTeamMember($team_id, $worker_id);
	    	} elseif(!empty($level)) { // member|manager
				 DAO_Group::setTeamMember($team_id, $worker_id, (1==$level)?false:true);
	    	}
	    }
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id,'members')));
	}
	
	function showTabBucketsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$visit->set('cerberusweb.groups.tab', 'buckets');

		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$buckets = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('buckets', $buckets);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/buckets/index.tpl');
	}
	
	function saveBucketsOrderAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array',array());
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
		
		// Save the order
		if(is_array($bucket_ids))
		foreach($bucket_ids as $pos => $bucket_id) {
			if(empty($bucket_id))
				continue;
				
			DAO_Bucket::update($bucket_id,array(
				DAO_Bucket::POS => $pos,
			));
		}
	}
	
	function showBucketPeekAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string',''); // Keep as string
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(!empty($bucket_id)) {
			$bucket = DAO_Bucket::get($bucket_id);
			$group_id = $bucket->team_id;
			$tpl->assign('bucket', $bucket);
		}
		if(!empty($group_id)) {
			$group = DAO_Group::get($group_id);
			$tpl->assign('group', $group);
		}
		
		$tpl->assign('group_id', $group_id);
		$tpl->assign('bucket_id', $bucket_id);
		$tpl->assign('replyto_addresses', DAO_AddressOutgoing::getAll());
		
		// All buckets
		$buckets = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('buckets', $buckets);
		
		// Signature
		$worker_token_labels = array();
		$worker_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $worker_token_labels, $worker_token_values);
		$tpl->assign('worker_token_labels', $worker_token_labels);

		// Template
		$tpl->display('devblocks:cerberusweb.core::groups/manage/buckets/peek.tpl');
	}
	
	function saveBucketPeekAction() {
		@$form_submit = DevblocksPlatform::importGPC($_REQUEST['form_submit'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string',''); // Keep as string
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$is_hidden = DevblocksPlatform::importGPC($_REQUEST['is_hidden'],'integer',0);
		@$reply_address_id = DevblocksPlatform::importGPC($_REQUEST['reply_address_id'],'integer',0);
		@$reply_personal = DevblocksPlatform::importGPC($_REQUEST['reply_personal'],'string','');
		@$reply_signature = DevblocksPlatform::importGPC($_REQUEST['reply_signature'],'string','');
		
		// ACL
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
		
	    switch($form_submit) {
	    	case 'delete':
	    		@$delete_moveto = DevblocksPlatform::importGPC($_REQUEST['delete_moveto'],'integer',0);
	    		$buckets = DAO_Bucket::getAll();
	    		// Bucket must exist
	    		if(empty($bucket_id) || !isset($buckets[$bucket_id]))
	    			break;
	    		// Destination must be inbox or exist
	    		if(!empty($delete_moveto) && !isset($buckets[$delete_moveto]))
	    			break;
	    		$where = sprintf("%s = %d",DAO_Ticket::CATEGORY_ID, $bucket_id);
	    		DAO_Ticket::updateWhere(array(DAO_Ticket::CATEGORY_ID => $delete_moveto), $where);
	    		DAO_Bucket::delete($bucket_id);
	    		break;
	    		
	    	case 'save':
				if('0' == $bucket_id) { // Inbox
					$fields = array(
						DAO_Group::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Group::REPLY_PERSONAL => $reply_personal,
						DAO_Group::REPLY_SIGNATURE => $reply_signature,
					);
					DAO_Group::updateTeam($group_id, $fields);
					
				} else { // Bucket
					$fields = array(
						DAO_Bucket::NAME => (empty($name) ? 'New Bucket' : $name),
						DAO_Bucket::IS_ASSIGNABLE => ($is_hidden ? 0 : 1),
						DAO_Bucket::REPLY_ADDRESS_ID => $reply_address_id,
						DAO_Bucket::REPLY_PERSONAL => $reply_personal,
						DAO_Bucket::REPLY_SIGNATURE => $reply_signature,
					);
		
					// Create?
					if(empty($bucket_id)) {
						$bucket_id = DAO_Bucket::create($name, $group_id);
					}
						
					DAO_Bucket::update($bucket_id, $fields);
				}
	    		break;
	    }
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'buckets')));
	}
	
	function showTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$visit->set('cerberusweb.groups.tab', 'fields');		
		
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::get($group_id);
			$tpl->assign('team', $group);
		}
		
		$group_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, $group_id); 
		$tpl->assign('group_fields', $group_fields);
                    
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('devblocks:cerberusweb.core::groups/manage/fields.tpl');
	}
	
	// Post
	function saveTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    	
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$allow_delete = DevblocksPlatform::importGPC($_POST['allow_delete'],'integer',0);
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($allow_delete && $delete) {
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order,
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '',
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Add custom field
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::GROUP_ID => $group_id,
				DAO_CustomField::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups',$group_id,'fields')));
	}
	
	function showGroupPanelAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($group_id) && null != ($group = DAO_Group::get($group_id))) {
			$tpl->assign('group', $group);
		}
		
		$tpl->display('devblocks:cerberusweb.core::groups/rpc/peek.tpl');
	}
	
	function saveGroupPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');

		$fields = array(
			DAO_Group::TEAM_NAME => $name			
		);
		
		// [TODO] Delete
		
		if(empty($group_id)) { // new
			$group_id = DAO_Group::create($fields);
			
		} else { // update
			DAO_Group::update($group_id, $fields);
			
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		exit;
	}
};
