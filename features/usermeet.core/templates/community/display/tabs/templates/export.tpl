<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="saveExportTemplatesPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="portal" value="{$portal}">

<h1>{'common.export'|devblocks_translate|capitalize}</h1>

<b>Filename:</b> (.xml)<br>
<input type="text" name="filename" size="45" value="cerb5_portal_templates_{$smarty.const.APP_BUILD}.xml"><br>
<br>

<b>Author:</b><br>
<input type="text" name="author" size="45" value=""><br>
<br>

<b>Author E-mail:</b><br>
<input type="text" name="email" size="45" value=""><br>
<br>

<button type="button" onclick="genericPanel.dialog('close');this.form.submit();"><span class="cerb-sprite sprite-export"></span> {'common.export'|devblocks_translate|capitalize}</button>
<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

</form>