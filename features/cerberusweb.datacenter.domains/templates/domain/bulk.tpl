<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="datacenter.domains">
<input type="hidden" name="a" value="doDomainBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>

<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	{*
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'crm.opportunity.closed_date'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<input type="text" name="closed_date" size=35 value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.closed_date,'#dateOppBulkClosed');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
			<div id="dateOppBulkClosed"></div>
      	</td>
	</tr>
	*}
	
	{*if $active_worker->hasPriv('crm.opp.view.actions.broadcast')*}
	<tr>
		<td width="0%" nowrap="nowrap" align="right"><label for="chkMassReply">Broadcast:</label></td>
		<td width="100%">
			<input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkDatacenterDomainBroadcast').toggle();">
		</td>
	</tr>
	{*/if*}
</table>

{*if $active_worker->hasPriv('crm.opp.view.actions.broadcast')*}
<blockquote id="bulkDatacenterDomainBroadcast" style="display:none;margin:10px;">
	<b>From:</b> <br>
	<select name="broadcast_group_id">
		{foreach from=$groups item=group key=group_id}
		{if $active_worker_memberships.$group_id}
		<option value="{$group->id}|escape">{$group->name}</option>
		{/if}
		{/foreach}
	</select>
	<br>
	<b>Subject:</b> <br>
	<input type="text" name="broadcast_subject" value="" style="width:100%;border:1px solid rgb(180,180,180);padding:2px;"><br>
	<b>Compose:</b> {*[<a href="#">syntax</a>]*}<br>
	<textarea name="broadcast_message" style="width:100%;height:200px;border:1px solid rgb(180,180,180);padding:2px;"></textarea>
	<br>
	<button type="button" onclick="genericAjaxPost('formBatchUpdate','bulkDatacenterDomainBroadcastTest','c=datacenter.domains&a=doBulkUpdateBroadcastTest');"><span class="cerb-sprite sprite-gear"></span> Test</button><!--
	--><select onchange="insertAtCursor(this.form.broadcast_message,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.broadcast_message.focus();">
		<option value="">-- insert at cursor --</option>
		{foreach from=$token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v|escape}</option>
		{/foreach}
	</select>
	<br>
	<div id="bulkDatacenterDomainBroadcastTest"></div>
	<b>{$translate->_('common.options')|capitalize}:</b> 
	<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
	<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
	<br>
	<b>{$translate->_('common.status')|capitalize}:</b> 
	<label><input type="radio" name="broadcast_next_is_closed" value="0"> {$translate->_('status.open')|capitalize}</label>
	<label><input type="radio" name="broadcast_next_is_closed" value="2" checked="checked"> {$translate->_('status.waiting')|capitalize}</label>
	<label><input type="radio" name="broadcast_next_is_closed" value="1"> {$translate->_('status.closed')|capitalize}</label>
</blockquote>
{*/if*}

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}

<br>

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	} );
</script>
