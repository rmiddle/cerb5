<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskPeek" name="formTaskPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="saveTaskPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$task->id}">
{if empty($id) && !empty($context)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.title'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="title" style="width:98%;" value="{$task->title}">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<input type="text" name="due_date" size="45" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}"><button type="button" onclick="devblocksAjaxDateChooser(this.form.due_date,'#dateTaskDue');">&nbsp;<span class="cerb-sprite sprite-calendar"></span>&nbsp;</button>
				<div id="dateTaskDue"></div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top"><label for="checkTaskCompleted">{'task.is_completed'|devblocks_translate|capitalize}:</label> </td>
			<td width="100%">
				<input id="checkTaskCompleted" type="checkbox" name="completed" value="1" {if $task->is_completed}checked{/if}>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
				<ul class="chooser-container bubbles" style="display:block;">
				{if !empty($context_watchers)}
					{foreach from=$context_watchers item=context_worker}
					<li>{$context_worker->getName()}<input type="hidden" name="worker_id[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
					{/foreach}
				{/if}
				</ul>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset>
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea><br>
	<b>{'common.notify_workers'|devblocks_translate}:</b>
	<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
		{if !empty($context_watchers)}
			{foreach from=$context_watchers item=context_worker}
			{if $context_worker->id != $active_worker->id}
				<li>{$context_worker->getName()}<input type="hidden" name="notify_worker_ids[]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
			{/if}
			{/foreach}
		{/if}
	</ul>
</fieldset>

{if $active_worker->hasPriv('core.tasks.actions.create')}
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','formTaskPeek','{$view_id}',false,'task_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
	{if !empty($task)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this task?')) { $('#formTaskPeek input[name=do_delete]').val('1'); genericAjaxPost('formTaskPeek', 'view{$view_id}'); genericAjaxPopupClose('peek'); } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}
{if !empty($task)}
<div style="float:right;">
	<a href="{devblocks_url}c=tasks&a=display&id={$task->id}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Tasks');
		$('#formTaskPeek :input:text:first').focus().select();
	});
	$('#formTaskPeek button.chooser_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	});
	$('#formTaskPeek button.chooser_notify_worker').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
	});
</script>
