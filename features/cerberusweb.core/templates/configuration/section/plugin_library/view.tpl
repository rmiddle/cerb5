{$app_version = DevblocksPlatform::strVersionToInt($smarty.const.APP_VERSION)}
{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="cerb-sprite2 sprite-gear"></span></a>
			<a href="javascript:;" title="Subtotals" class="subtotals minimal"><span class="cerb-sprite2 sprite-application-sidebar-list"></span></a>
			<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-application-export"></span></a>
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-applications"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite2 sprite-arrow-circle-135-left"></span></a>
			<input type="checkbox" class="select-all">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="">
<input type="hidden" name="a" value="">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="width:106px;"></th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}
	{$plugin = $plugins.{$result.p_plugin_id}}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td rowspan="4" align="center">
				<div style="margin:0px 5px 5px 5px;">
					{if !empty($plugin) && isset($plugin->manifest_cache.plugin_image) && !empty($plugin->manifest_cache.plugin_image)}
						<img src="{devblocks_url}c=resource&p={$plugin->id}&f={$plugin->manifest_cache.plugin_image}{/devblocks_url}" width="100" height="100" style="border:1px solid rgb(200,200,200);">
					{elseif !empty($result.p_icon_url)}
						<img src="{$result.p_icon_url}" width="100" height="100" style="border:1px solid rgb(200,200,200);">
					{else}
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/plugin_code_gray.gif{/devblocks_url}" width="100" height="100" style="border:1px solid rgb(200,200,200);">
					{/if}
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}">
				<div style="padding-bottom:2px;">
					<input type="checkbox" name="row_id[]" value="{$result.p_id}" style="display:none;">
					<b class="subject">{$result.p_name}</b>
				</div>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
		
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="p_updated"}
				<td><abbr title="{$result.$column|devblocks_date}">{$result.p_updated|devblocks_prettytime}</abbr>&nbsp;</td>
			{elseif $column=="p_latest_version"}
				<td>
					{if isset($plugin->version) && $plugin->version < $result.p_latest_version}
						<div class="badge" style="font-weight:bold;">{DevblocksPlatform::intVersionToStr($result.$column)}</div>
					{else}
						{DevblocksPlatform::intVersionToStr($result.$column)}
					{/if}
				</td>
			{elseif $column=="p_link"}
				<td>
					<a href="{$result.$column}" target="_blank">{$result.$column}</a>
				</td>
			{elseif $column=="p_author"}
				<td>
					{if !empty($column.p_link)}
						<a href="{$result.p_link}" target="_blank">{$result.$column}</a>
					{else}
						{$result.$column}
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}" valign="top">
				<div style="padding:5px;">
					{$result.p_description}
				</div>
				<div style="margin:5px;">
					{if !empty($plugin)}
						{if $plugin->version < $result.p_latest_version}
							<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=plugin_library&action=showDownloadPopup&plugin_id={$result.p_id}&view_id={$view->id}',null,true,'550');" style="color:rgb(0,150,0);text-decoration:none;font-weight:bold;">Installed; Upgrade to {DevblocksPlatform::intVersionToStr($result.p_latest_version)} &#x25be;</a></div>
						{else}
							<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" style="color:rgb(0,0,0);text-decoration:none;font-weight:bold;">Installed</a></div>
						{/if}
						
					{else}
						{*
						{$requirements = json_decode($result.p_requirements_json, true)}
						{$result.p_requirements_json}
						{if isset($requirements.app_version.min) && $app_version < $requirements.app_version.min}
							<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" style="color:rgb(120,0,0);font-weight:bold;text-decoration:none;">This plugin requires at least Cerb5 ({DevblocksPlatform::intVersionToStr($requirements.app_version.min)}) and you are running ({$smarty.const.APP_VERSION})</a></div>
						{elseif isset($requirements.app_version.max) && $app_version > $requirements.app_version.max}
							<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" style="color:rgb(120,0,0);font-weight:bold;text-decoration:none;">This plugin was only tested through Cerb5 ({DevblocksPlatform::intVersionToStr($requirements.app_version.max)}) and you are running ({$smarty.const.APP_VERSION})</a></div>
						{else}
							<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=plugin_library&action=showDownloadPopup&plugin_id={$result.p_id}&view_id={$view->id}',null,true,'550');" style="color:rgb(0,150,0);text-decoration:none;font-weight:bold;">Download and install &#x25be;</a></div>
						{/if}
						*}
						<div class="badge badge-lightgray" style="padding:3px;"><a href="javascript:;" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=plugin_library&action=showDownloadPopup&plugin_id={$result.p_id}&view_id={$view->id}',null,true,'550');" style="color:rgb(0,150,0);text-decoration:none;font-weight:bold;">Download and install &#x25be;</a></div>
					{/if}
				</div>
			</td>
		</tr>
	</tbody>
	{/foreach}
</table>

<div style="padding-top:5px;">
	<div style="float:right;">
		{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
		{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
		{math assign=nextPage equation="x+1" x=$view->renderPage}
		{math assign=prevPage equation="x-1" x=$view->renderPage}
		{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
		
		{* Sanity checks *}
		{if $toRow > $total}{assign var=toRow value=$total}{/if}
		{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
		
		{if $view->renderPage > 0}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
		{/if}
		({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
		{if $toRow < $total}
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
		{/if}
	</div>
	
	{if $total}
	<div style="float:left;" id="{$view->id}_actions">
	</div>
	{/if}
</div>

<div style="clear:both;"></div>

</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}

<script type="text/javascript">
$frm = $('#viewForm{$view->id}');

{if $pref_keyboard_shortcuts}
$frm.bind('keyboard_shortcut',function(event) {
	//console.log("{$view->id} received " + (indirect ? 'indirect' : 'direct') + " keyboard event for: " + event.keypress_event.which);
	
	$view_actions = $('#{$view->id}_actions');
	
	hotkey_activated = true;

	switch(event.keypress_event.which) {
		default:
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>