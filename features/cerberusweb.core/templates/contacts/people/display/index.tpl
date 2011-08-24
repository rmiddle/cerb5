{$page_context = CerberusContexts::CONTEXT_CONTACT_PERSON}
{$page_context_id = $person->id}

<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=people{/devblocks_url}">{$translate->_('addy_book.tab.people')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

<h2>Contact</h2>

{$primary_email = $person->getPrimaryAddress()}
{$person_addresses = $person->getAddresses()}

<fieldset class="properties">
	<legend>{$primary_email->getName()} &lt;{$primary_email->email}&gt;</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">

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
		<div style="margin-top:5px;">
			{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($person->id), CerberusContexts::CONTEXT_WORKER)}
			{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$person->id full=true}
			
			<button type="button" id="btnDisplayContactEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
		</div>

	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$person->id}

<div style="clear:both;" id="contactPersonTabs">
	<ul>
		{$tabs = [activity,notes,links,addresses,mail]}
		{$point = 'cerberusweb.contact_person.tab'}

		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$person->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeopleAddresses&id={$person->id}{/devblocks_url}">{'Email Addresses'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabMailHistory&point={$point}&address_ids={foreach from=$person_addresses item=v key=k name=addys}{$v->id}{if !$smarty.foreach.addys.last},{/if}{/foreach}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>

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
		
		$('#btnDisplayContactEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=contacts&a=showContactPeek&id={$page_context_id}',null,false,'550');
			$popup.one('contact_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=contacts&a=people&id={$page_context_id}{/devblocks_url}';
			});
		});
		
	});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(document).keypress(function(event) {
	if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
		return;
	
	if($(event.target).is(':input'))
		return;

	hotkey_activated = true;
	
	switch(event.which) {
		case 49:  // (1) tab cycle
		case 50:  // (2) tab cycle
		case 51:  // (3) tab cycle
		case 52:  // (4) tab cycle
		case 53:  // (5) tab cycle
		case 54:  // (6) tab cycle
		case 55:  // (7) tab cycle
		case 56:  // (8) tab cycle
		case 57:  // (9) tab cycle
		case 58:  // (0) tab cycle
			try {
				idx = event.which-49;
				$tabs = $("#contactPersonTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayContactEdit').click();
			} catch(ex) { } 
			break;
		default:
			// We didn't find any obvious keys, try other codes
			hotkey_activated = false;
			break;
	}
	
	if(hotkey_activated)
		event.preventDefault();
});
{/if}
</script>