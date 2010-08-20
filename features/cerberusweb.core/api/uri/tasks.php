<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
if (class_exists('Extension_ActivityTab')):
class ChTasksActivityTab extends Extension_ActivityTab {
	const VIEW_ACTIVITY_TASKS = 'activity_tasks';
	
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}
	
	function showTab() {
		// Remember the tab
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_ACTIVITY_TAB, 'tasks');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// [TODO] Convert to $defaults
		
		if(null == ($view = C4_AbstractViewLoader::getView(self::VIEW_ACTIVITY_TASKS))) {
			$view = new View_Task();
			$view->id = self::VIEW_ACTIVITY_TASKS;
			$view->renderSortBy = SearchFields_Task::DUE_DATE;
			$view->renderSortAsc = 1;
			
			$view->name = $translate->_('activity.tab.tasks');
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}

		$tpl->assign('view', $view);
		
		$tpl->display($tpl_path . 'tasks/activity_tab/index.tpl');		
	}
}
endif;

class ChTasksPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
//	const SESSION_OPP_TAB = '';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		array_shift($stack); // tasks
		
		$module = array_shift($stack); // display
		
		switch($module) {
			default:
			case 'display':
				@$task_id = intval(array_shift($stack));
				if(null == ($task = DAO_Task::get($task_id))) {
					break; // [TODO] Not found
				}
				$tpl->assign('task', $task);			

				if(null == (@$tab_selected = $stack[0])) {
//					$tab_selected = $visit->get(self::SESSION_OPP_TAB, '');
				}
				$tpl->assign('tab_selected', $tab_selected);

				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$tpl->display($this->_TPL_PATH . 'tasks/display/index.tpl');
				break;
		}
	}

	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function showTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);

		// Handle context links ([TODO] as an optional array)
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		if(!empty($id)) {
			$task = DAO_Task::get($id);
			$tpl->assign('task', $task);
		}

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Task::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TASK, $id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);

		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TASK, $id);
		$tpl->assign('context_workers', $context_workers);
		
		// View
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . $path . 'tasks/rpc/peek.tpl');
	}
	
	function saveTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id) && !empty($do_delete)) { // delete
			$task = DAO_Task::get($id);

			// Check privs
			// [TODO] Workers on task
			if(($active_worker->hasPriv('core.tasks.actions.create') /*&& $active_worker->id==$task->worker_id*/)
				|| ($active_worker->hasPriv('core.tasks.actions.update_nobody') /*&& empty($task->worker_id)*/) 
				|| $active_worker->hasPriv('core.tasks.actions.update_all'))
					DAO_Task::delete($id);
			
		} else { // create|update
			$fields = array();
	
			// Title
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
			$fields[DAO_Task::TITLE] = !empty($title) ? $title : 'New Task';
	
			// Completed
			@$completed = DevblocksPlatform::importGPC($_REQUEST['completed'],'integer',0);
			
			$fields[DAO_Task::IS_COMPLETED] = intval($completed);
			
			// [TODO] This shouldn't constantly update the completed date (it should compare)
			if($completed)
				$fields[DAO_Task::COMPLETED_DATE] = time();
			else
				$fields[DAO_Task::COMPLETED_DATE] = 0;
			
			// Updated Date
			$fields[DAO_Task::UPDATED_DATE] = time();
			
			// Due Date
			@$due_date = DevblocksPlatform::importGPC($_REQUEST['due_date'],'string','');
			@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));		
	
			// Save
			if(!empty($id)) {
				DAO_Task::update($id, $fields);
				
			} else {
				$id = DAO_Task::create($fields);
				
				// Content
				@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

				// Context Link (if given)
				@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
				@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
				if(!empty($id) && !empty($context) && !empty($context_id)) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $id, $context, $context_id);
				}
				
				// Append a note from the first content block, if provided				
				if(!empty($content) && !empty($id)) {
					$fields = array(
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
						DAO_Comment::CREATED => time(),
						DAO_Comment::COMMENT => $content,
					);
					$note_id = DAO_Comment::create($fields);
				}
			}

			// Workers
			@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
			CerberusContexts::setWorkers(CerberusContexts::CONTEXT_TASK, $id, $worker_ids);
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Task::ID, $id, $field_ids);
		}
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function showTaskBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
	    $workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
	    
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Task::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'tasks/rpc/bulk.tpl');
	}
	
	function doTaskBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Task fields
		$due = trim(DevblocksPlatform::importGPC($_POST['due'],'string',''));
		$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));

		$do = array();
		
		// Do: Due
		if(0 != strlen($due))
			$do['due'] = $due;
			
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			default:
				break;
		}
		
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
	function viewTasksExploreAction() {
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
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=activity&tab=tasks', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $task_id => $row) {
				if($task_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Task::ID],
					'url' => $url_writer->write(sprintf("c=tasks&tab=display&id=%d", $row[SearchFields_Task::ID]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}		
};
