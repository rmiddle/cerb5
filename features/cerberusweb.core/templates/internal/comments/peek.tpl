<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalCommentPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="commentSavePopup">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

<b>Author:</b> {$active_worker->getName()}
<div>
	<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea>
</div>
<div>
	<button type="button" onclick="ajax.chooserSnippet('snippets',$('#internalCommentPopup textarea[name=comment]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
</div>
<br>

<b>Attachments:</b><br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_file"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	</ul>
</div>

<b>{'common.notify_watchers_and'|devblocks_translate}</b>:<br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;"></ul>
</div>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	var $frm = $('#internalCommentPopup');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Comment');
		
		$frm.find('button.submit').click(function() {
			$popup = genericAjaxPopupFetch('peek');
			genericAjaxPost('internalCommentPopup','','', null, { async: false } );
			$popup.trigger('comment_save');
			genericAjaxPopupClose('peek');
		});
	
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// [TODO] This shouldn't catch an 'o'.
		$frm.find('textarea').focus();
	});
</script>
