{if $active_worker->hasPriv('crm.opp.actions.create')}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=crm&a=showOppPanel&id=0&context={CerberusContexts::CONTEXT_TICKET}&context_id={$ticket_id}&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'crm.opp.add'|devblocks_translate|capitalize}</button>
</form>
{/if}

{if !empty($view)}
<div id="view{$view->id}">
	{$view->render()}
</div>
{/if}