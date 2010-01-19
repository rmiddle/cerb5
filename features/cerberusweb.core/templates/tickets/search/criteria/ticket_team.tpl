<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{$translate->_('search.oper.in_list')}</option>
		<option value="not in">{$translate->_('search.oper.in_list.not')}</option>
	</select>
</blockquote>

<b>{$translate->_('common.groups')|capitalize}:</b><br>
{foreach from=$teams item=team key=team_id}
{if isset($active_worker_memberships.$team_id)}{*censor*}
	<label><input name="team_id[]" type="checkbox" value="{$team_id}" onclick="toggleDiv('searchGroup{$id}{$team_id}',(this.checked)?'block':'none');"><span style="font-weight:bold;color:rgb(0,120,0);">{$team->name}</span></label><br>
	<blockquote style="margin:0px;margin-left:10px;display:none;" id="searchGroup{$id}{$team_id}">
		<label><input name="bucket_id[]" type="checkbox" value="0"><span style="font-size:90%;">Inbox</span></label><br>
		{if isset($team_categories.$team_id)}
		{foreach from=$team_categories.$team_id item=cat}
			<label><input name="bucket_id[]" type="checkbox" value="{$cat->id}"><span style="font-size:90%;">{$cat->name}</span></label><br>
		{/foreach}
		{/if}
	</blockquote>
{/if}
{/foreach}
