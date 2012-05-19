<form id="frmDecisionSwitch{$id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

<fieldset>
	<legend>Determine an outcome based on multiple choices</legend>
	
	A decision will evaluate multiple choices and choose the first outcome that satisfies all conditions. 
	Each outcome may use different conditions.  For example, you can use a decision to choose from a list: language, 
	time of day, day of week, service level, etc.
</fieldset>
</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this decision?</legend>
	<p>Are you sure you want to permanently delete this decision and its children?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionSwitch{$id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_switch{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	<button type="button" onclick="genericAjaxPost('frmDecisionSwitch{$id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_switch{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('node_switch{$id}');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Decision");
		$(this).find('input:text').first().focus();
	});
</script>
