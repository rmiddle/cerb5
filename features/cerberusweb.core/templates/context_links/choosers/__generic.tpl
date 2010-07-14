<form action="#" method="POST" id="filters{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">
{$view->render()}
</div>

<form action="#" method="POST" id="chooser{$view->id}">
<b>Selected:</b>
<div class="buffer"></div>
<br>
<button type="button" class="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooser{$view->id}');
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name} Chooser');
		
		$('#viewCustomFilters{$view->id}').bind('view_refresh', function(event) {
			if(event.target == event.currentTarget)
				genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
		});
		
		$('#view{$view->id}').delegate('button.devblocks-chooser-add-selected', 'click', function(event) {
			event.stopPropagation();
			$view = $('#viewForm{$view->id}');
			$buffer = $('form#chooser{$view->id} DIV.buffer');
			
			$view.find('input:checkbox:checked').each(function(index) {
				$label = $(this).attr('title');
				$value = $(this).val();
				
				if($label.length > 0 && $value.length > 0) {
					if(0==$buffer.find('input:hidden[value='+$value+']').length) {
						$html = $('<div>' + $label + '</div>');
						$html.prepend(' <button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> ');
						$html.append('<input type="hidden" name="to_context_id[]" title="' + $label + '" value="' + $value + '">');
						$('form#chooser{$view->id} DIV.buffer').append($html);
					}
				}
					
				$(this).removeAttr('checked');
			});
		});
		
		$("form#chooser{$view->id} button.submit").click(function(event) {
			event.stopPropagation();
			$popup = genericAjaxPopupFind('form#chooser{$view->id}');
			//$buffer = $('form#chooser{$view->id} DIV.buffer input:hidden');
			$buffer = $($popup).find('DIV.buffer input:hidden');
			$labels = [];
			$values = [];
			
			$buffer.each(function() {
				$labels.push($(this).attr('title')); 
				$values.push($(this).val()); 
			});
		
			// Trigger event
			event = jQuery.Event('chooser_save');
			event.labels = $labels;
			event.values = $values;
			$popup.trigger(event);
			
			genericAjaxPopupClose('{$layer}');
		});		
	} );
</script>