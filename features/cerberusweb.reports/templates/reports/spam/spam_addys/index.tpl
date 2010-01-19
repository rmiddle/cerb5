<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.spam.senders')}</h2>
<br>

<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td width="50%" align="center" valign="top">
			<h3>{$translate->_('reports.ui.spam.senders.top_spam')}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{$translate->_('common.email')|capitalize}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_spam')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_nonspam')}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_spam_addys key=email item=counts}
				<tr>
					<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$email|escape:'url'}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" title="{$email|escape}">{$email|truncate:45|escape}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(s/(s+n))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
		<td width="50%" align="center" style="padding-left:30px;" valign="top">
			<h3>{$translate->_('reports.ui.spam.senders.top_nonspam')}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{$translate->_('common.email')|capitalize}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_nonspam')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_spam')}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_nonspam_addys key=email item=counts}
				<tr>
					<td><a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$email|escape:'url'}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" title="{$email|escape}">{$email|truncate:45|escape}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(n/(n+s))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>

