<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTimeEntry">
<input type="hidden" name="c" value="timetracking">
<input type="hidden" name="a" value="saveEntry">

<h2>{$translate->_('timetracking.ui.trial.limit_exceeded')}</h2>
{$translate->_('timetracking.ui.trial.limit_exceeded.desc')}<br>
<br>

<a href="http://www.cerberusweb.com/buy" target="_blank">{$translate->_('timetracking.ui.trial.purchase')}</a>
<br>
<br>

<button type="button" onclick="timeTrackingTimer.finish();"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/media_stop_red.png{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

</form>