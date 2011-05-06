{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" class="subtotals">subtotals</a>
			 | <a href="javascript:;" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.home.workspaces')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			{if $active_worker->hasPriv('timetracking.view.actions.export')} | <a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.export')|lower}</a>{/if}
			 | <a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
			 | <input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="cerberusweb.contexts.timetracking">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="1" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		<th style="text-align:center;width:75px;">
			<a href="javascript:;">{'common.watchers'|devblocks_translate|capitalize}</a>
		</th>
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
	{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TIMETRACKING, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
	{assign var=worker_id value=$result.tt_worker_id}
	{assign var=activity_id value=$result.tt_activity_id}
	
	{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}
	{if isset($workers.$worker_id)}
		{assign var=worker_name value=$workers.$worker_id->getName()}
	{else}
		{assign var=worker_name value=$generic_worker}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
				{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TIMETRACKING context_id=$result.tt_id}
			</td>
			<td colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.tt_id}" style="display:none;">
				{if $result.tt_is_closed}<span class="cerb-sprite2 sprite-tick-circle-frame-gray" title=""></span>{/if}
				<a href="{devblocks_url}c=timetracking&a=display&id={$result.tt_id}{/devblocks_url}" class="subject">{if isset($activities.$activity_id->name)}{'timetracking.ui.tracked_desc'|devblocks_translate:$worker_name:$result.tt_time_actual_mins:$activities.$activity_id->name}{else}{'%s tracked %s mins'|devblocks_translate:$worker_name:$result.tt_time_actual_mins}{/if}</a>
				<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="genericAjaxPopup('peek','c=timetracking&a=showEntry&id={$result.tt_id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
			</td>
		</tr>
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="tt_id"}
			<td>{$result.tt_id}&nbsp;</td>
			{elseif $column=="tt_log_date"}
			<td title="{$result.tt_log_date|devblocks_date}">{$result.tt_log_date|devblocks_prettytime}&nbsp;</td>
			{elseif $column=="tt_worker_id"}
				<td>{if isset($workers.$worker_id)}{$workers.$worker_id->getName()}{/if}&nbsp;</td>
			{elseif $column=="tt_activity_id"}
				<td>{if isset($activities.$activity_id)}{$activities.$activity_id->name}{if $activities.$activity_id->rate > 0} ($){/if}{/if}&nbsp;</td>
			{elseif $column=="tt_source_extension_id"}
				{assign var=source_ext_id value=$result.tt_source_extension_id}
				{assign var=source_id value=$result.tt_source_id}
				<td>{if isset($sources.$source_ext_id)}
					{assign var=source value=$sources.$source_ext_id}
					<a href="{$source->getLink($source_id)}">{$source->getLinkText($source_id)}</a>{/if}&nbsp;
				</td>
			{else}
			<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			<button id="btnExplore{$view->id}" type="button" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewTimeExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
			<button type="button" onclick="genericAjaxPopup('peek','c=timetracking&a=showBulkPanel&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>
		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
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
		</td>
	</tr>
</table>
</form>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}