<?php
class ChReportWaitingTickets extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		
	   	// Top Buckets
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		// Year shortcuts
		$years = array();
		$sql = "SELECT date_format(from_unixtime(created_date),'%Y') as year FROM ticket WHERE created_date > 0 GROUP BY year having year <= date_format(now(),'%Y') ORDER BY year desc limit 0,10";
		$rs = $db->Execute($sql);
		
		
		while($row = mysql_fetch_assoc($rs)) {
			$years[] = intval($row['year']);
		}
		$tpl->assign('years', $years);
		
		mysql_free_result($rs);
		
		// Date
		
		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		@$age = DevblocksPlatform::importGPC($_REQUEST['age'],'string','30d');
		
		// Table
		
		$sql = "SELECT count(*) AS hits, group_id, bucket_id ".
			"FROM ticket ".
			"WHERE is_deleted = 0 ".
			"AND is_closed = 0 ".
			"AND spam_score < 0.9000 ".
			"AND spam_training != 'S' ".
			"AND is_waiting = 1 " .
			"GROUP BY group_id, bucket_id ";
		$rs = $db->Execute($sql);
	
		$group_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$group_id = intval($row['group_id']);
			$bucket_id = intval($row['bucket_id']);
			$hits = intval($row['hits']);
			
			if(!isset($group_counts[$group_id]))
				$group_counts[$group_id] = array();
				
			$group_counts[$group_id][$bucket_id] = $hits;
			@$group_counts[$group_id]['total'] = intval($group_counts[$group_id]['total']) + $hits;
		}
		$tpl->assign('group_counts', $group_counts);
		
		mysql_free_result($rs);
		
		// Chart
		
		$sql = "SELECT worker_group.id as group_id, ".
			"count(*) as hits ".
			"FROM ticket t INNER JOIN worker_group on t.group_id = worker_group.id ".
			"WHERE t.is_deleted = 0 ".
			"AND t.is_closed = 0 ".
			"AND t.spam_score < 0.9000 ".
			"AND t.spam_training != 'S' ".
			"AND is_waiting = 1 " .				
			"GROUP BY group_id ORDER by worker_group.name desc ";

		$rs = $db->Execute($sql);

		$iter = 0;
		$data = array();
	    
	    while($row = mysql_fetch_assoc($rs)) {
	    	$hits = intval($row['hits']);
			$group_id = $row['group_id'];
			
			if(!isset($groups[$group_id]))
				continue;
			
			$data[$iter++] = array('value'=>$groups[$group_id]->name, 'hits'=> $hits);
	    }
	    
		// Sort the data in descending order (chart reverses)
		uasort($data, array('ChReportSorters','sortDataAsc'));
	    
	    $tpl->assign('data', $data);
	    
	    mysql_free_result($rs);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.reports::reports/ticket/waiting_tickets/index.tpl');
	}
};