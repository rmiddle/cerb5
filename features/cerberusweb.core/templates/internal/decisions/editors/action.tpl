<form id="frmDecisionNodeAction" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionPopup">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

<div class="actions">

{$seq = null}
{if isset($model->params.actions) && is_array($model->params.actions)}
{foreach from=$model->params.actions item=params key=seq}
<fieldset id="action{$seq}">
	<legend>
		<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a>
		{$actions.{$params.action}.label}
	</legend>

	<input type="hidden" name="actions[]" value="{$seq}">
	<input type="hidden" name="action{$seq}[action]" value="{$params.action}">
	
	{$event->renderAction({$params.action},$trigger,$params,$seq)}
</fieldset>
{/foreach}
{/if}

</div>

</form>

<form id="frmDecisionNodeActionAdd">
<input type="hidden" name="seq" value="{if !is_null($seq)}{$seq+1}{else}0{/if}">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<fieldset>
	<legend>Add Action</legend>

	<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
	<select name="action">
		<option value=""></option>
		{foreach from=$actions item=action key=token}
		<option value="{$token}">{$action.label}</option>
		{/foreach}
	</select>
</fieldset>
</form>

<form>
	<button type="button" onclick="genericAjaxPost('frmDecisionNodeAction','','',function() { window.location.reload(); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Actions");

		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
		$popup.find('BUTTON.chooser_notify_workers.unbound').each(function() {
			seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[notify_worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		$popup.find('#frmDecisionNodeActionAdd SELECT').first().change(function() {
			$select = $(this);
			$val=$select.val();
	
			if(''==$val) {
				return;
			}
	
			genericAjaxPost('frmDecisionNodeActionAdd','','c=internal&a=doDecisionAddAction',function(html) {
				$ul = $('#frmDecisionNodeAction DIV.actions');
				
				seq = parseInt($('#frmDecisionNodeActionAdd').find('input[name=seq]').val());
				if(null == seq)
					seq = 0;
	
				$container = $('<fieldset id="action' + seq + '"></fieldset>');
				$container.prepend('<legend><a href="javascript:;" onclick="$(this).closest(\'fieldset\').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a> ' + $select.find('option:selected').text() + '</legend>');
				$container.append('<input type="hidden" name="actions[]" value="' + seq + '">');
				$container.append('<input type="hidden" name="action'+seq+'[action]" value="' + $select.val() + '">');
				$ul.append($container);
	
				$html = $('<div>' + html + '</div>');
				$container.append($html);
				
				$html.find('BUTTON.chooser_worker.unbound').each(function() {
					ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
					$(this).removeClass('unbound');
				});
				$html.find('BUTTON.chooser_notify_workers.unbound').each(function() {
					ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[notify_worker_id]', { autocomplete:true });
					$(this).removeClass('unbound');
				});
				
				$select.val(0);
	
				$('#frmDecisionNodeActionAdd').find('input[name=seq]').val(1+seq);
			});
		});

	}); // popup_open
</script>
