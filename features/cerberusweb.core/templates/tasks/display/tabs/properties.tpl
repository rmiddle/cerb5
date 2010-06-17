<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formTaskTabProps" name="formTaskTabProps">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="saveTasksPropertiesTab">
<input type="hidden" name="id" value="{$task->id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'task.title'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="title" style="width:98%;" value="{$task->title|escape}">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'task.due_date'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" id="taskDueDate" name="due_date" size="45" value="{if !empty($task->due_date)}{$task->due_date|devblocks_date}{/if}">
			<script type="text/javascript" language="JavaScript1.2">
				devblocksAjaxDateChooser('#taskDueDate');
			</script>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'common.worker'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<select name="worker_id">
				<option value="0">- {'common.anybody'|devblocks_translate|lower} -</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
				{if $worker_id==$active_worker->id}{assign var=active_worker_sel_id value=$smarty.foreach.workers.iteration}{/if}
				<option value="{$worker_id}" {if $worker_id==$task->worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>{if !empty($active_worker_sel_id)}<button type="button" onclick="this.form.worker_id.selectedIndex = {$active_worker_sel_id};">{'common.me'|devblocks_translate|lower}</button>{/if}
		</td>
	</tr>
	{if empty($task->id)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'task.content'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<textarea name="content" style="width:98%;height:100px;"></textarea>
		</td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><label for="checkTaskCompleted">{'task.is_completed'|devblocks_translate|capitalize}:</label> </td>
		<td width="100%">
			<input id="checkTaskCompleted" type="checkbox" name="completed" value="1" {if $task->is_completed}checked{/if}>
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}

<br>

{if ($active_worker->hasPriv('core.tasks.actions.create') && (empty($task) || $active_worker->id==$task->worker_id))
	|| ($active_worker->hasPriv('core.tasks.actions.update_nobody') && empty($task->worker_id)) 
	|| $active_worker->hasPriv('core.tasks.actions.update_all')}
	<button type="button" onclick="this.form.submit();"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this task?')){literal}{{/literal}this.form.do_delete.value='1';this.form.submit();{literal}}{/literal}"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}
<br>
</form>
