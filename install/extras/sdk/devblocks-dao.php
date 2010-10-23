<?php
/**
 * Devblocks DAO
 * @author Jeff Standen, WebGroup Media LLC <jeff@webgroupmedia.com>
 * @version 2010-10-02 
 */

$tables = array();

$tables['ExampleTable'] = "
id INT UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(255) DEFAULT '',
";

foreach($tables as $table_name => $field_strs) {
	// Class
	$class_name = str_replace(' ','',ucwords(str_replace('_',' ',$table_name)));

	$table_name = strtolower($table_name);
	
	// Fields
	$fields = array();

	$schema = trim($field_strs);
	$schema = str_replace(array("\r"), array("\n"), $schema);
	$schema = str_replace(array("\n\n"), array("\n"), $schema);
	
	foreach(explode("\n", $schema) as $field_str) {
		$field_props = explode(' ', rtrim($field_str, ",\n\r "));
		$fields[trim($field_props[0])] = trim($field_props[1]);
	}
	
?>
<b>api/dao/<?php echo $table_name; ?>.php</b><br>
<textarea style="width:98%;height:200px;">
class DAO_<?php echo $class_name; ?> extends DevblocksORMHelper {
<?php 
foreach($fields as $field_name => $field_type) {
	printf("\tconst %s = '%s';\n",
		strtoupper($field_name),
		$field_name
	); 
}
?>

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO <?php echo $table_name; ?> () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, '<?php echo $table_name; ?>', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('<?php echo $table_name; ?>', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT <?php echo implode(', ', array_keys($fields)); ?> ".
			"FROM <?php echo $table_name; ?> ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_<?php echo $class_name; ?>
	 */
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
	 * @return Model_<?php echo $class_name; ?>[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_<?php echo $class_name; ?>();
<?php 
foreach($fields as $field_name => $field_type) {
	printf("\t\t\t\$object->%s = \$row['%s'];\n",
		$field_name,
		$field_name
	);	
}
?>
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM <?php echo $table_name; ?> WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_<?php echo $class_name; ?>::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
<?php
$num_fields = 0; 
foreach($fields as $field_name => $field_type) {
	$num_fields++;
	printf("\t\t\t\"%s.%s as %%s%s",
		$table_name,
		$field_name,
		(($num_fields==count($fields)) ? " \",\n" : ", \".\n") // ending
	);
}
$num_fields = 0; 
foreach($fields as $field_name => $field_type) {
	$num_fields++;
	printf("\t\t\t\tSearchFields_%s::%s%s",
		$class_name,
		strtoupper($field_name),
		($num_fields==count($fields)) ? "\n" : ",\n"
	);
}
?>
			);
			
		$join_sql = "FROM <?php echo $table_name; ?> ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'<?php echo $table_name; ?>.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		return array(
			'primary_table' => '<?php echo $table_name; ?>',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
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
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY <?php echo $table_name; ?>.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_<?php echo $class_name; ?>::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT <?php echo $table_name; ?>.id) " : "SELECT COUNT(<?php echo $table_name; ?>.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};
</textarea>

<textarea style="width:98%;height:200px;">
class SearchFields_<?php echo $class_name; ?> implements IDevblocksSearchFields {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\tconst %s = '%s_%s';\n",
		strtoupper($field_name),
		substr($table_name,0,1),
		$field_name
	);
}
?>
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tself::%s => new DevblocksSearchField(self::%s, '%s', '%s', \$translate->_('dao.%s.%s')),\n",
		strtoupper($field_name),
		strtoupper($field_name),
		$table_name,
		$field_name,
		$table_name,
		$field_name
	);
}
?>
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};
</textarea>

<textarea style="width:98%;height:200px;">
class Model_<?php echo $class_name; ?> {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\tpublic \$%s;\n",
		$field_name
	);
};
?>
};
</textarea>

<textarea style="width:98%;height:200px;">
class View_<?php echo $class_name; ?> extends C4_AbstractView {
	const DEFAULT_ID = '<?php echo strtolower($class_name); ?>';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('<?php echo $class_name; ?>');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_<?php echo $class_name; ?>::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tSearchFields_%s::%s,\n",
		$class_name,
		strtoupper($field_name)
	);
}
?>
		);
		// [TODO] Filter fields
		$this->addColumnsHidden(array(
		));
		
		// [TODO] Filter fields
		$this->addParamsHidden(array(
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_<?php echo $class_name; ?>::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_<?php echo $class_name; ?>', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		// [TODO] Set your template path
		$tpl->display('devblocks:example.plugin::path/to/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		// [TODO] Move the fields into the proper data type
		switch($field) {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tcase SearchFields_%s::%s:\n",
		$class_name,
		strtoupper($field_name)
	);
}
?>
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case 'placeholder_number':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case 'placeholder_date':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_<?php echo $class_name; ?>::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		// [TODO] Move fields into the right data type
		switch($field) {
<?php
foreach($fields as $field_name => $field_type) {
	printf("\t\t\tcase SearchFields_%s::%s:\n",
		$class_name,
		strtoupper($field_name)
	);
}
?>
			case 'placeholder_string':
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case 'placeholder_date':
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
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
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_<?php echo $class_name; ?>::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_<?php echo $class_name; ?>::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_<?php echo $class_name; ?>::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_<?php echo $class_name; ?>::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_<?php echo $class_name; ?>::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
</textarea>

<b>plugin.xml</b><br>
<textarea style="width:98%;height:200px;">
<file path="api/dao/<?php echo $table_name; ?>.php">
	<class name="DAO_<?php echo $class_name; ?>" />
	<class name="Model_<?php echo $class_name; ?>" />
	<class name="SearchFields_<?php echo $class_name; ?>" />
	<class name="View_<?php echo $class_name; ?>" />
</file>
</textarea>

<b>strings.xml</b><br>
<textarea style="width:98%;height:200px;">
<!-- <?php echo $class_name; ?> -->

<?php foreach($fields as $field_name => $field_type) { ?>
<tu tuid='dao.<?php echo $table_name; ?>.<?php echo $field_name; ?>'>
	<tuv xml:lang="en_US"><seg><?php echo ucwords(str_replace('_',' ',$field_name)); ?></seg></tuv>
</tu>
<?php } ?>
</textarea>

<?php } ?>