<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$ticket->id}">
	
	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap">
				{if $active_worker->hasPriv('core.tasks.actions.create')}
					<button type="button" onclick="genericAjaxPanel('c=tasks&a=showTaskPeek&id=0&view_id={$view->id}&link_namespace=cerberusweb.tasks.ticket&link_object_id={$ticket->id}',this,false,'500px',{literal}function(o){document.getElementById('formTaskPeek').title.focus();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear_add.gif{/devblocks_url}" align="top"> {'tasks.add'|devblocks_translate}</button>
				{/if}
			</td>
			<td width="98%"></td>
			<td width="1%" nowrap="nowrap" align="right">
				<b>Task status filter:</b> 
				<label title="{'tasks.incomplete'|devblocks_translate|capitalize}">
				<input type="radio" name="scope" value="incomplete" onclick="this.form.a.value='doTicketTasksScope';this.form.submit();" 
				{if empty($scope) || 'incomplete'==$scope}checked="checked"{/if}> {'tasks.incomplete'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="scope" value="complete" onclick="this.form.a.value='doTicketTasksScope';this.form.submit();" {if 'complete'==$scope}checked="checked"{/if}> {'tasks.complete'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="scope" value="all" onclick="this.form.a.value='doTicketTasksScope';this.form.submit();" {if 'all'==$scope}checked="checked"{/if}> {'common.all'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
	</table>
</form>
{/if}

<div id="viewticket_tasks">{$view->render()}</div>
