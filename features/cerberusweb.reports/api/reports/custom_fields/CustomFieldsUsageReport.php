<?php
class ChReportCustomFieldUsage extends Extension_Report {
	function render() {
		$db = DevblocksPlatform::getDatabaseService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Custom Field contexts (tickets, orgs, etc.)
		$tpl->assign('context_manifests', DAO_CustomField::getContexts());

		// Custom Fields
		$custom_fields = DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Table + Chart
		@$field_id = DevblocksPlatform::importGPC($_REQUEST['field_id'],'integer',0);
		$tpl->assign('field_id', $field_id);
		
		if(!empty($field_id) && isset($custom_fields[$field_id])) {
			$field = $custom_fields[$field_id];
			$tpl->assign('field', $field);
		
			// Table
			
			$value_counts = self::_getValueCounts($field_id);
			$tpl->assign('value_counts', $value_counts);

			// Chart
			
			$data = array();
			$iter = 0;
			if(is_array($value_counts))
			foreach($value_counts as $value=>$hits) {
				$data[$iter++] = array('value'=>$value,'hits'=>$hits);
			}
			
			// Sort the data in descending order (chart reverses it)
			uasort($data, array('ChReportSorters','sortDataAsc'));
			
			$tpl->assign('data', $data);
		}
		
		$tpl->display('devblocks:cerberusweb.reports::reports/custom_fields/usage/index.tpl');
	}
	
	private function _getValueCounts($field_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Selected custom field
		if(null == ($field = DAO_CustomField::get($field_id)))
			return;

		if(null == ($table = DAO_CustomFieldValue::getValueTableName($field_id)))
			return;
			
		$sql = sprintf("SELECT field_value, count(field_value) AS hits ".
			"FROM %s ".
			"WHERE context = %s ".
			"AND field_id = %d ".
			"GROUP BY field_value ",
			$table,
			$db->qstr($field->context),
			$field->id
		);
		$rs = $db->Execute($sql);
	
		$value_counts = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$value = $row['field_value'];
			$hits = intval($row['hits']);

			switch($field->type) {
				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 'Yes' : 'No';
					break;
				case Model_CustomField::TYPE_DATE:
					$value = gmdate("Y-m-d H:i:s", $value);
					break;
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_Worker::getAll();
					$value = (isset($workers[$value])) ? $workers[$value]->getName() : $value;
					break;
			}
			
			$value_counts[$value] = intval($hits);
		}
		
		mysql_free_result($rs);
		
		arsort($value_counts);
		return $value_counts;
	}
};