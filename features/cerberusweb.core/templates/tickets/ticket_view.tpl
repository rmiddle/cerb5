{$view_fields = $view->getColumnsAvailable()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<div id="{$view->id}_output_container">
	{include file="devblocks:cerberusweb.core::tickets/rpc/ticket_view_output.tpl"}
</div>

<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span> {if $view->id == 'search'}<a href="#{$view->id}_actions">{$translate->_('views.jump_to_actions')}</a>{/if}</td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" class="subtotals minimal">subtotals</a>
			<a href="javascript:;" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');">{$translate->_('common.customize')|lower}</a>
			{if $active_worker->hasPriv('core.ticket.view.actions.pile_sort')}<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('mail.piles')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.mail.search')}<a href="{devblocks_url}c=tickets&a=searchview&id={$view->id}{/devblocks_url}">{$translate->_('common.search')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.home.workspaces')}<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.copy')|lower}</a>{/if}
			{if $active_worker->hasPriv('core.ticket.view.actions.export')}<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');">{$translate->_('common.export')|lower}</a>{/if}
			<a href="javascript:;" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite sprite-refresh"></span></a>
			{if $active_worker->hasPriv('core.rss')}<a href="javascript:;" onclick="genericAjaxGet('{$view->id}_tips','c=tickets&a=showViewRss&view_id={$view->id}&source=core.rss.source.ticket');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite sprite-rss"></span></a>{/if}
			<input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
<form id="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
<input type="hidden" name="context_id" value="cerberusweb.contexts.ticket">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="worklistBody">
	{* Column Headers *}
	<thead>
	<tr>
		<th style="text-align:center;width:75px;">
			<a href="javascript:;">{'common.watchers'|devblocks_translate|capitalize}</a>
		</th>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th>
			{if !empty($view_fields.$header->db_column)}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			{else}
				<a href="javascript:;" style="text-decoration:none;">{$view_fields.$header->db_label|capitalize}</a>
			{/if}
			
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
	</thead>

	{* Column Data *}
	{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array_keys($data), CerberusContexts::CONTEXT_WORKER)}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	
	{assign var=ticket_group_id value=$result.t_group_id}
	{if !isset($active_worker_memberships.$ticket_group_id)}{*censor*}
	<tbody>
	<tr class="{$tableRowClass}">
		<td>&nbsp;</td>
		<td rowspan="2" colspan="{$smarty.foreach.headers.total}" style="color:rgb(140,140,140);font-size:10px;text-align:left;vertical-align:middle;">[Access Denied: {$groups.$ticket_group_id->name} #{$result.t_mask}]</td>
	</tr>
	<tr class="{$tableRowClass}">
		<td>&nbsp;</td>
	</tr>
	</tbody>
	
	{else}
	<tbody style="cursor:pointer;">
	<tr class="{$tableRowClass}">
		<td align="center" rowspan="2" nowrap="nowrap" style="padding:5px;">
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$result.t_id}
		</td>
		<td colspan="{$smarty.foreach.headers.total}">
			<input type="checkbox" name="ticket_id[]" value="{$result.t_id}" style="display:none;">
			{if $result.t_is_deleted}<span class="cerb-sprite2 sprite-cross-circle-frame-gray"></span> {elseif $result.t_is_closed}<span class="cerb-sprite2 sprite-tick-circle-frame-gray" title="{$translate->_('status.closed')}"></span> {elseif $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span>{/if}
			<a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}" class="subject">{$result.t_subject}</a> 
			<button type="button" class="peek" style="visibility:hidden;padding:1px;margin:0px 5px;" onclick="genericAjaxPopup('peek','c=tickets&a=showPreview&view_id={$view->id}&tid={$result.t_id}', null, false, '650');"><span class="cerb-sprite2 sprite-document-search-result" style="margin-left:2px" title="{$translate->_('views.peek')}"></span></button>
		</td>
	</tr>
	<tr class="{$tableRowClass}">
	{foreach from=$view->view_columns item=column name=columns}
		{if substr($column,0,3)=="cf_"}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
		{elseif $column=="t_id"}
		<td><a href="{devblocks_url}c=display&id={$result.t_id}{/devblocks_url}">{$result.t_id}</a></td>
		{elseif $column=="t_mask"}
		<td><a href="{devblocks_url}c=display&id={$result.t_mask}{/devblocks_url}">{$result.t_mask}</a></td>
		{elseif $column=="t_subject"}
		<td title="{$result.t_subject}">{$result.t_subject}</td>
		{elseif $column=="t_is_waiting"}
		<td>{if $result.t_is_waiting}<span class="cerb-sprite sprite-clock"></span>{else}{/if}</td>
		{elseif $column=="t_is_closed"}
		<td>{if $result.t_is_closed}<span class="cerb-sprite2 sprite-tick-circle-frame-gray" title="{$translate->_('status.closed')}"></span>{else}{/if}</td>
		{elseif $column=="t_is_deleted"}
		<td>{if $result.t_is_deleted}<span class="cerb-sprite2 sprite-cross-circle-frame-gray"></span>{else}{/if}</td>
		{elseif $column=="t_last_wrote"}
		<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_last_wrote}">{$result.t_last_wrote|truncate:45:'...':true:true}</a></td>
		{elseif $column=="t_first_wrote"}
		<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_first_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$result.t_first_wrote}">{$result.t_first_wrote|truncate:45:'...':true:true}</a></td>
		{elseif $column=="t_created_date" || $column=="t_updated_date" || $column=="t_due_date"}
		<td><abbr title="{$result.$column|devblocks_date}">{$result.$column|devblocks_prettytime}</abbr></td>
		{elseif $column=="t_owner_id"}
		<td>
			{if isset($workers.{$result.t_owner_id})}
				{$workers.{$result.t_owner_id}->getName()}
			{else}
			{/if}
		</td>
		{elseif $column=="o_name"}
		<td>
			<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id={$result.t_org_id}&view_id={$view->id}',null,false,'500');">{$result.o_name}</a>
		</td>
		{elseif $column=="t_group_id"}
		<td>
			{assign var=ticket_group_id value=$result.t_group_id}
			{$groups.$ticket_group_id->name}
		</td>
		{elseif $column=="t_bucket_id"}
			{assign var=ticket_bucket_id value=$result.t_bucket_id}
			<td>
				{if 0 == $ticket_bucket_id}
					{'common.inbox'|devblocks_translate|capitalize}
				{else}
					{$buckets.$ticket_bucket_id->name}
				{/if}
			</td>
		{elseif $column=="t_last_action_code"}
		<td>
			{if $result.t_last_action_code=='O'}
				<span title="{$result.t_first_wrote}">New from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
			{elseif $result.t_last_action_code=='R'}
				<span title="{$result.t_last_wrote}">{'mail.received'|devblocks_translate} from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
			{elseif $result.t_last_action_code=='W'}
				<span title="{$result.t_last_wrote}">{'mail.sent'|devblocks_translate} from <a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$result.t_last_wrote|escape:'url'}&view_id={$view->id}',null,false,'500');">{$result.t_last_wrote|truncate:45:'...':true:true}</a></span>
			{/if}
		</td>
		{elseif $column=="t_first_wrote_spam"}
		<td>{$result.t_first_wrote_spam}</td>
		{elseif $column=="t_first_wrote_nonspam"}
		<td>{$result.t_first_wrote_nonspam}</td>
		{elseif $column=="t_spam_score" || $column=="t_spam_training"}
		<td>
			{math assign=score equation="x*100" format="%0.2f%%" x=$result.t_spam_score}
			{if empty($result.t_spam_training)}
			{if $active_worker->hasPriv('core.ticket.actions.spam')}<a href="javascript:;" onclick="$(this).closest('tbody').remove();genericAjaxGet('{$view->id}_output_container','c=tickets&a=reportSpam&id={$result.t_id}&viewId={$view->id}');">{/if}
			<span class="cerb-sprite sprite-{if $result.t_spam_score >= 0.90}warning{else}warning_gray{/if}" title="Report Spam ({$score})"></span>
			{if $active_worker->hasPriv('core.ticket.actions.spam')}</a>{/if}
			{/if}
		</td>
		{else}
		<td>{if $result.$column}{$result.$column}{/if}</td>
		{/if}
	{/foreach}
	</tr>
	</tbody>
	{/if}{*!censor*}
	
{/foreach}
	
</table>
<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2">
			{assign var=show_more value=0}
			<button id="btnExplore{$view->id}" type="button" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewTicketsExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
			{if $active_worker->hasPriv('core.ticket.view.actions.bulk_update')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}BulkUpdate" onclick="ajax.showBatchPanel('{$view->id}',null);"><span class="cerb-sprite2 sprite-folder-gear"></span> {$translate->_('common.bulk_update')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.close')}{assign var=show_more value=1}<button type="button" id="btn{$view->id}Close" onclick="ajax.viewCloseTickets('{$view->id}',0);"><span class="cerb-sprite2 sprite-folder-tick-circle"></span> {$translate->_('common.close')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.spam')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Spam" onclick="ajax.viewCloseTickets('{$view->id}',1);"><span class="cerb-sprite sprite-spam"></span> {$translate->_('common.spam')|lower}</button>{/if}
			{if $active_worker->hasPriv('core.ticket.actions.delete')}{assign var=show_more value=1}<button type="button"  id="btn{$view->id}Delete" onclick="ajax.viewCloseTickets('{$view->id}',2);"><span class="cerb-sprite2 sprite-folder-cross-circle"></span> {$translate->_('common.delete')|lower}</button>{/if}
			
			{if $active_worker->hasPriv('core.ticket.actions.move')}
			{assign var=show_more value=1}
			<button type="button" id="btn{$view->id}Move"><span class="cerb-sprite2 sprite"></span> {'common.move'|devblocks_translate|lower} &#x25be;</button>
			<ul class="cerb-popupmenu cerb-float" style="">
				<li style="background:none;">
					<input type="text" size="16" class="input_search filter">
				</li>
				
				{foreach from=$groups item=group name=groups}
				<li group_id="{$group->id}" bucket_id="0">
					<div class="item">
						<b>{$group->name}</b><br>
						<div style="margin-left:10px;"><a href="javascript:;" style="font-weight:normal;">{'common.inbox'|devblocks_translate|capitalize}</a></div>
					</div>
				</li>
				
				{if isset($active_worker_memberships.{$group->id})}
				{foreach from=$group_buckets.{$group->id} item=bucket}
					<li group_id="{$group->id}" bucket_id="{$bucket->id}">
						<div class="item">
							<b>{$group->name}</b><br>
							<div style="margin-left:10px;"><a href="javascript:;" style="font-weight:normal;">{$bucket->name}</a></div>
						</div>
					</li>
				{/foreach}
				{/if}
				
				{/foreach}
			</ul>
			{/if}
			
			{if $show_more}
			<button type="button" onclick="toggleDiv('view{$view->id}_more');">{$translate->_('common.more')|lower} &raquo;</button><br>
			{/if}

			<div id="view{$view->id}_more" style="display:{if $show_more}none{else}block{/if};padding-top:5px;padding-bottom:5px;">
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_spam');">{$translate->_('common.notspam')|lower}</button>
				{if $active_worker->hasPriv('core.ticket.view.actions.merge')}<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','merge');">{$translate->_('mail.merge')|lower}</button>{/if}
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','waiting');">{$translate->_('mail.waiting')|lower}</button>
				<button type="button" onclick="ajax.viewTicketsAction('{$view->id}','not_waiting');">{$translate->_('mail.not_waiting')|lower}</button>
			</div>

			{if $pref_keyboard_shortcuts}
			{if $view->id=='mail_workflow' || $view->id=='search'}{*Only on Workflow/Search*}
				{$translate->_('common.keyboard')|lower}: 
					(<b>a</b>) {$translate->_('common.all')|lower} 
					(<b>e</b>) {$translate->_('common.explore')|lower} 
					{if $active_worker->hasPriv('core.ticket.view.actions.bulk_update')}(<b>b</b>) {$translate->_('common.bulk_update')|lower}{/if} 
					{if $active_worker->hasPriv('core.ticket.actions.close')}(<b>c</b>) {$translate->_('common.close')|lower}{/if} 
					{if $active_worker->hasPriv('core.ticket.actions.spam')}(<b>s</b>) {$translate->_('common.spam')|lower}{/if} 
					{if $active_worker->hasPriv('core.ticket.actions.delete')}(<b>x</b>) {$translate->_('common.delete')|lower}{/if}
					{if $active_worker->hasPriv('core.ticket.actions.move')}(<b>m</b>) {'common.move'|devblocks_translate|lower}{/if}
					<div style="margin-left:25px;">
						workflow: 
						(<b>-</b>) undo last filter 
						(<b>*</b>) reset filters
						(<b>~</b>) change subtotals
						(<b>`</b>) focus subtotals
					</div>
			{/if}
			{/if}
		</td>
	</tr>
	{/if}
	<tr>
		<td align="left" valign="top">
		</td>
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

<script type="text/javascript">
// Quick move menu
$menu_trigger = $('#btn{$view->id}Move');
$menu = $menu_trigger.next('ul.cerb-popupmenu');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			$menu = $(this).data('menu');

			if($menu.is(':visible')) {
				$menu.hide();
				return;
			}

			$menu
				.css('position','absolute')
				//.css('top',($(this).offset().top+20)+'px')
				.css('left',$(this).offset().left+'px')
				.show()
				.find('> li input:text')
				.focus()
				.select()
				;
		}
	)
;

$menu.find('> li > input.filter').keypress(
	function(e) {
		code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			e.preventDefault();
			e.stopPropagation();
			$(this).select().focus();
			return false;
		}
	}
);
	
$menu.find('> li > input.filter').keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $(this).closest('ul.cerb-popupmenu');
		$menu.find('> li > div.item').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).parent().show();
			} else {
				$(this).parent().hide();
			}
		});
	}
);

$menu.find('> li').click(function(e) {
	e.stopPropagation();
	if($(e.target).is('a'))
		return;

	$(this).find('a').trigger('click');
});

$menu.find('> li > div.item a').click(function() {
	$li = $(this).closest('li');
	$frm = $(this).closest('form');
	
	group_id = $li.attr('group_id');
	bucket_id = $li.attr('bucket_id');

	if(group_id.length > 0) {
		genericAjaxPost('viewForm{$view->id}', 'view{$view->id}', 'c=tickets&a=viewMoveTickets&view_id={$view->id}&group_id=' + group_id + '&bucket_id=' + bucket_id);
	}
	
	$menu.hide();
});	
</script>