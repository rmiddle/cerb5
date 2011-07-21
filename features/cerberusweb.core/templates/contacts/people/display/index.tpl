<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=people{/devblocks_url}">{$translate->_('addy_book.tab.people')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

<h2>Contact</h2>

{$primary_email = $person->getPrimaryAddress()}

<fieldset class="properties">
	<legend>{$primary_email->getName()} &lt;{$primary_email->email}&gt;</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'primary_email'}
					<b>{$v.label|capitalize}:</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showAddressPeek&email={$v.address->email}',null,false,'500');">{$v.address->email}</a>
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">

		<!-- Toolbar -->
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CONTACT_PERSON, array($person->id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CONTACT_PERSON context_id=$person->id full=true}
		</span>		

	</form>
</fieldset>

<div style="clear:both;" id="contactPersonTabs">
	<ul>
		{$tabs = [activity,notes,links,history]}
		{$point = ''}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_CONTACT_PERSON}&context_id={$person->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeopleAddresses&id={$person->id}{/devblocks_url}">{'Email Addresses'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeopleHistory&id={$person->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$person->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactPersonTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
