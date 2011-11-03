<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{$members = $group->getMembers()}

<fieldset>
	<div style="float:left;">
	{*
	<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d=mm" border="0" style="margin:0px 5px 5px 0px;">
	*}
	<h1 style="color:rgb(0,120,0);font-weight:bold;font-size:150%;">{$group->name}</h1>
	{*[[ charts for open vs waiting vs closed tickets ]]<br>*}
	</div>
	{*
	{if $active_worker->is_superuser}
	<div style="float:right;">
		<button type="button" id="btnProfileGroupEdit"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
	</div>
	{/if}
	*}
</fieldset>

<div id="profileTabs">
	<ul>
		{$tabs = [links]}
		{$point = "cerberusweb.profiles.group.{$group->id}"}
		
		<li><a href="#members">Members</a></li>
		
		{*
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.worker&point={$point}&id={$worker->id}{/devblocks_url}">{'Assignments'|devblocks_translate}</a></li>
		*}

		{*
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=preferences&a=showTab&ext_id={$tab_manifest->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		*}
		
		{* [TODO] Group managers can add, any member can see 
		{if $active_worker->hasPriv('core.home.workspaces')}
			{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker)}
			{foreach from=$enabled_workspaces item=enabled_workspace}
				{$tabs[] = 'w_'|cat:$enabled_workspace->id}
				<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&id={$enabled_workspace->id}&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
			{/foreach}
			
			{$tabs[] = "+"}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
		{/if}
		*}
	</ul>
	
	<div id="members">
		{foreach from=$members item=member}
		{if isset($workers.{$member->id})}
			{$worker = $workers.{$member->id}}
			<fieldset>
				<div style="float:left;">
					<img src="{if $is_ssl}https://secure.{else}http://www.{/if}gravatar.com/avatar/{$worker->email|trim|lower|md5}?s=64&d=mm" border="0" style="margin:0px 5px 5px 0px;">
				</div>
				<div style="float:left;">
					<a href="{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}" style="color:rgb(0,120,0);font-weight:bold;font-size:150%;margin:0px;">{$worker->getName()}</a><br>
					{if !empty($worker->title)}{$worker->title}<br>{/if}
					{if !empty($worker->email)}{$worker->email}<br>{/if}

					{if $member->is_manager}
					<ul class="bubbles">
						<li style="font-weight:bold;">Manager</li>
					</ul>
					{/if}
				</div>
				{*
				{if $active_worker->is_superuser}
				<div style="float:right;">
					<button type="button" id="btnProfileWorkerEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
				</div>
				{/if}
				*}
			</fieldset>
		{/if}
		{/foreach}		
	</div>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	var tabs = $("#profileTabs").tabs( { selected:{$selected_tab_idx} } );

	{*
	{if $active_worker->is_superuser}
	$('#btnProfileGroupEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=config&a=handleSectionAction&section=workers&action=showWorkerPeek&id={$worker->id}',null,false,'550');
		$popup.one('group_save', function(event) {
			event.stopPropagation();
			document.location.href = '{devblocks_url}c=profiles&k=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}{/devblocks_url}';
		});
	});
	{/if}
	*}
});
</script>
