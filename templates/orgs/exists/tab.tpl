<fieldset>
	<legend>Freshbooks Client</legend>
	
	<b>{'common.id'|devblocks_translate}:</b> 
	{$client->id}
	<br>
	
	<b>{'common.name'|devblocks_translate}:</b>
	{$client->account_name}
	<br>

	<b>{'common.email'|devblocks_translate}:</b>
	{$client->data.email}
	<br>
	
	<b>{'common.updated'|devblocks_translate|capitalize}:</b>
	{$client->updated|devblocks_date} ({$client->updated|devblocks_prettytime})
	<br>
	
	<b>{'dao.wgm_freshbooks_client.synchronized'|devblocks_translate|capitalize}:</b>
	{$client->synchronized|devblocks_date} ({$client->synchronized|devblocks_prettytime})
	<br>
	
	<br>

	<a href="{$client->data.auth_url}" target="_blank">View client in Freshbooks</a>
	{if !empty($client->data.username)}
		 | 
		<a href="{$client->data.url}" target="_blank">Log into Freshbooks as {$client->data.username}</a>
	{/if}

	<br>

	{*
	{foreach from=$client->data key=key item=value}
		{if !is_array(value)}
			<b>{$key}:</b> {$value}<br>
		{/if}
	{/foreach}
	*}
	
</fieldset>