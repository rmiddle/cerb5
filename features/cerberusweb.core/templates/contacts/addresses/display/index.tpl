{$page_context = CerberusContexts::CONTEXT_ADDRESS}
{$page_context_id = $address->id}

<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=addresses{/devblocks_url}">{$translate->_('addy_book.tab.addresses')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

<div style="float:left;">
	<h2>{'address.address'|devblocks_translate|capitalize}</h2>
</div>

<div style="float:right;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="email">{$translate->_('address.email')|capitalize}</option>
	<option value="org">{$translate->_('contact_org.name')|capitalize}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
</div>

<br clear="all">

<fieldset class="properties">
	{$addy_name = $address->getName()} 
	<legend>
		{if !empty($addy_name)}
			{$addy_name} &lt;{$address->email}&gt;
		{else}
			{$address->email}
		{/if}
	</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
	<input type="hidden" name="c" value="tasks">
	<input type="hidden" name="a" value="">
	<input type="hidden" name="id" value="{$task->id}">

		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == 'org'}
					<b>{$v.label|capitalize}:</b>
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id={$v.org_id}',null,false,'600');">{$v.org->name}</a>
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
	
		<span>
		{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
		{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full=true}
		</span>		
	
		<!-- Macros -->
		{devblocks_url assign=return_url full=true}c=contacts&tab=addresses&m=display&id={$page_context_id}-{$address->email|devblocks_permalink}{/devblocks_url}
		{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}		
	
		<!-- Toolbar -->
		<button type="button" id="btnDisplayAddyEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		(<b>e</b>) {'common.edit'|devblocks_translate|lower}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
		(<b>1-9</b>) change tab
	</small> 
	{/if}
</fieldset>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div style="clear:both;" id="contactTabs">
	<ul>
		{$tabs = [activity,notes,links,mail]}
		{$point = 'cerberusweb.address.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$page_context}&id={$page_context_id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabMailHistory&point={$point}&address_ids={$page_context_id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactTabs").tabs( { selected:{$tab_selected_idx} } );
	
		$('#btnDisplayAddyEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$page_context_id}',null,false,'550');
			$popup.one('address_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=contacts&a=addresses&m=display&id={$page_context_id}-{$address->email|devblocks_permalink}{/devblocks_url}';
			});
		});
		
		{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
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
				$tabs = $("#contactTabs").tabs();
				$tabs.tabs('select', idx);
			} catch(ex) { } 
			break;
		case 101:  // (E) edit
			try {
				$('#btnDisplayAddyEdit').click();
			} catch(ex) { } 
			break;
		case 109:  // (M) macros
			try {
				$('#btnDisplayMacros').click();
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