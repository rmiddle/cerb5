{include file="devblocks:cerberusweb.datacenter::datacenter/servers/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1 style="margin-bottom:5px;">{$server->name}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;">
		{*
		<b>{'task.is_completed'|devblocks_translate|capitalize}:</b> {if $task->is_completed}{'common.yes'|devblocks_translate|capitalize}{else}{'common.no'|devblocks_translate|capitalize}{/if} &nbsp;
		{if !empty($task->updated_date)}
		<b>{'task.updated_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->updated_date|devblocks_date}">{$task->updated_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}
		{if !empty($task->due_date)}
		<b>{'task.due_date'|devblocks_translate|capitalize}:</b> <abbr title="{$task->due_date|devblocks_date}">{$task->due_date|devblocks_prettytime}</abbr> &nbsp;
		{/if}
		{assign var=task_worker_id value=$task->worker_id}
		{if !empty($task_worker_id) && isset($workers.$task_worker_id)}
			<b>{'common.worker'|devblocks_translate|capitalize}:</b> {$workers.$task_worker_id->getName()} &nbsp;
		{/if}
		<br>
		*}
		
		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.datacenter.server', array($server->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.datacenter.server' context_id=$server->id full=true}
		</span>		
		
		<button type="button" id="btnDatacenterServerEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		{*
		{$toolbar_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.toolbaritem',true)}
		{foreach from=$toolbar_extensions item=toolbar_extension}
			{$toolbar_extension->render($task)}
		{/foreach}
		*}
		
		</form>
	</td>
	<td align="right" valign="top">
		{*
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
		*}
	</td>
</tr>
</table>

<div id="datacenterServerTabs">
	<ul>
		{$point = Extension_ServerTab::POINT}
		{$tabs = [activity, comments, links]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context=cerberusweb.contexts.datacenter.server&context_id={$server->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>   
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.datacenter.server&point={$point}&id={$server->id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.datacenter.server&point={$point}&id={$server->id}{/devblocks_url}">{'common.links'|devblocks_translate}</a></li>
		
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=datacenter&a=showServerTab&ext_id={$tab_manifest->id}&point={$point}&server_id={$server->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#datacenterServerTabs").tabs( { selected:{$selected_tab_idx} } );
		
		$('#btnDatacenterServerEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=datacenter&a=showServerPeek&id={$server->id}',null,false,'550');
			$popup.one('datacenter_server', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=datacenter&a=server&id={$server->id}{/devblocks_url}';
			});
		})
	});
</script>
