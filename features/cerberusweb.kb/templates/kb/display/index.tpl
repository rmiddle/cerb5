<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<div style="float:left;">
	<h2>Knowledgebase Article</h2>
</div>

<div style="float:right;">
	<form action="{devblocks_url}{/devblocks_url}" method="post">
	<input type="hidden" name="c" value="kb.ajax">
	<input type="hidden" name="a" value="doArticleQuickSearch">
	<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
		<option value="articles_all">Articles (all words)</option>
		<option value="articles_phrase">Articles (phrase)</option>
	</select><input type="text" name="query" class="input_search" size="24"><button type="submit">go!</button>
	</form>
</div>

<br clear="all">

<fieldset class="properties">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<form action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
		{foreach from=$properties item=v key=k name=props}
			<div class="property">
				{if $k == '...'}
					<b>{$translate->_('...')|capitalize}:</b>
					...
				{else}
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				{/if}
			</div>
			{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
				<br clear="all">
			{/if}
		{/foreach}
		<br clear="all">
		
		{if !empty($breadcrumbs)}
		<b>Filed under:</b> 
		{foreach from=$breadcrumbs item=trail name=trail}
			{foreach from=$trail item=step key=cat_id name=cats}
			<a href="{devblocks_url}c=kb&a=category&id={$cat_id}-{$categories.{$cat_id}->name|devblocks_permalink}{/devblocks_url}">{$categories.{$cat_id}->name}</a>
			{if !$smarty.foreach.cats.last} &raquo; {/if}
			{/foreach}
			{if !$smarty.foreach.trail.last}; {/if}
		{/foreach}
		<br clear="all">
		{/if}
		
		<div style="margin-top:5px;">
			<!-- Macros -->
			{devblocks_url assign=return_url full=true}c=kb&tab=article&id={$article->id}-{$article->title|devblocks_permalink}{/devblocks_url}
			{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=CerberusContexts::CONTEXT_KB_ARTICLE context_id=$article->id macros=$macros return_url=$return_url}		

			<!-- Edit -->					
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button id="btnDisplayKbEdit" type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id={$article->id}&return_uri={"kb/article/{$article->id}"}',null,false,'725');"><span class="cerb-sprite sprite-document_edit"></span> {$translate->_('common.edit')|capitalize}</button>{/if}
		</div>
	</form>
	
	{if $pref_keyboard_shortcuts}
	<small>
		{$translate->_('common.keyboard')|lower}:
		{if $active_worker->hasPriv('core.kb.articles.modify')}(<b>e</b>) {'common.edit'|devblocks_translate|lower}{/if}
		{if !empty($macros)}(<b>m</b>) {'common.macros'|devblocks_translate|lower} {/if}
	</small> 
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=CerberusContexts::CONTEXT_KB_ARTICLE context_id=$article->id}

<br>

<div id="kbArticleContent">
	<h1 class="title"><b>{$article->title}</b></h1>

	{$article->getContent() nofilter}
</div>

{include file="devblocks:cerberusweb.core::internal/attachments/list.tpl" context={CerberusContexts::CONTEXT_KB_ARTICLE} context_id={$article->id}}

<script type="text/javascript">
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
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
		{if $active_worker->hasPriv('core.kb.articles.modify')}
		case 101:  // (E) edit
			try {
				$('#btnDisplayKbEdit').click();
			} catch(ex) { } 
			break;
		{/if}
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