<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Remove the 'logo_url' property (where set) and replace it with 'header_html'

if(isset($tables['community_tool_property'])) {
	$rs = $db->Execute("SELECT tool_code, property_value FROM community_tool_property WHERE property_key = 'common.logo_url'");
	
	while(!$rs->EOF) {
		$tool_code = $rs->fields['tool_code'];
		$logo_url = $rs->fields['property_value'];
		
		// Do we have a header already?
		$header_property = $db->GetOne(sprintf("SELECT tool_code FROM community_tool_property WHERE tool_code=%s AND property_key=%s",
			$db->qstr($tool_code),
			$db->qstr('common.header_html')
		));
		
		if(!empty($logo_url) && empty($header_property)) {
			// Build the custom header HTML
			$html = sprintf("<div align=\"center\"><img src=\"%s\" alt=\"Logo\" border=\"0\"></div>",
				$logo_url
			);
			
			// Insert a property
			$db->Execute(sprintf("INSERT INTO community_tool_property (tool_code, property_key, property_value) VALUES (%s,%s,%s)",
				$db->qstr($tool_code),
				$db->qstr('common.header_html'),
				$db->qstr($html)
			));
		}

		// Drop logo prop
		$db->Execute(sprintf("DELETE FROM community_tool_property WHERE property_key = 'common.logo_url' AND tool_code='%s'", 
			$tool_code
		));
		
		// Next
		$rs->MoveNext();
	}
	
}

return TRUE;