{if $active_worker->hasPriv('core.tasks.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {'tasks.add'|devblocks_translate}</button>
</form>
{/if}

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}