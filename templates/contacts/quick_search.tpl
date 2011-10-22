<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="wgm.freshbooks.contacts_tab">
<input type="hidden" name="action" value="doQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="account_name">{$translate->_('common.name')|capitalize}</option>
	<option value="email">{$translate->_('address.email')|capitalize}</option>
	<option value="org">{$translate->_('contact_org.name')|capitalize}</option>
	<option value="client_id">{$translate->_('dao.wgm_freshbooks_client.client_id')|capitalize}</option>

</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
