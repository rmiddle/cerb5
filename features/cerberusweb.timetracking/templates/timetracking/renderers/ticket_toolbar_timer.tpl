{if $active_worker->hasPriv('timetracking.actions.create')}
<button type="button" onclick="timeTrackingTimer.play('timetracking.source.ticket','{$ticket->id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.timetracking&f=images/16x16/stopwatch.png{/devblocks_url}" align="top"> {$translate->_('timetracking.ui.button.track')|capitalize}</button>
{/if}