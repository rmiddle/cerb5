{if empty($ticket)}
We did not find a ticket to match the supplied ID / mask.
{else}
<h2 style="color: rgb(102,102,102);">Ticket #{$ticket_id}</h2>
{foreach from=$ticket->getMessages() item=message name=messages}
{assign var=headers value=$message->getHeaders()}
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
        {if isset($headers.from)}<b>From:</b> {$headers.from|escape|nl2br}<br>{/if}
        {if isset($headers.to)}<b>To:</b> {$headers.to|escape|nl2br}<br>{/if}
        {if isset($headers.subject)}<b>Subject:</b> {$headers.subject|escape|nl2br}<br>{/if}
        {if isset($headers.date)}<b>Date:</b> {$headers.date|escape|nl2br}<br>{/if}
      
      	<br>
      	{$message->getContent()|trim|nl2br}
      	<br>

      	[ <a href="{devblocks_url}c=mobile&a=display&id={$ticket_id}&m_id={$message->id}{/devblocks_url}?page_type=reply">Reply</a> 
      	/ <a href="{devblocks_url}c=mobile&a=display&id={$ticket_id}&m_id={$message->id}{/devblocks_url}?page_type=forward">Forward</a> 
      	/ <a href="{devblocks_url}c=mobile&a=display&id={$ticket_id}&m_id={$message->id}{/devblocks_url}?page_type=comment">Comment</a> ] 
      	<br>
      </td>
    </tr>
  </tbody>
</table>
{if !$smarty.foreach.messages.last}<hr>{/if}
{/foreach}
{/if}{*end of if empty($ticket)*}