{$view_fields = $view->getColumnsAvailable()}
{assign var=results value=$view->getData()}
{assign var=total value=$results[1]}
{assign var=data value=$results[0]}
<table cellpadding="0" cellspacing="0" border="0" class="worklist" width="100%">
	<tr>
		<td nowrap="nowrap"><span class="title">{$view->name}</span></td>
		<td nowrap="nowrap" align="right">
			<a href="javascript:;" title="{'common.customize'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('customize{$view->id}','c=internal&a=viewCustomize&id={$view->id}');toggleDiv('customize{$view->id}','block');"><span class="cerb-sprite2 sprite-gear"></span></a>
			{*<a href="javascript:;" title="Subtotals" class="subtotals minimal"><span class="cerb-sprite2 sprite-application-sidebar-list"></span></a>*}
			<a href="javascript:;" title="{$translate->_('common.export')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowExport&id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-application-export"></span></a>
			<a href="javascript:;" title="{$translate->_('common.copy')|capitalize}" onclick="genericAjaxGet('{$view->id}_tips','c=internal&a=viewShowCopy&view_id={$view->id}');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite2 sprite-applications"></span></a>
			<a href="javascript:;" title="{'common.refresh'|devblocks_translate|capitalize}" class="minimal" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');"><span class="cerb-sprite2 sprite-arrow-circle-135-left"></span></a>
			<input type="checkbox" onclick="checkAll('view{$view->id}',this.checked);this.blur();$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');if($(this).is(':checked')) { $rows.addClass('selected'); } else { $rows.removeClass('selected'); }">
		</td>
	</tr>
</table>

<div id="{$view->id}_tips" class="block" style="display:none;margin:10px;padding:5px;">Loading...</div>
<form id="customize{$view->id}" name="customize{$view->id}" action="#" onsubmit="return false;" style="display:none;"></form>
<form id="viewForm{$view->id}" name="viewForm{$view->id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="view_id" value="{$view->id}">
{*<input type="hidden" name="context_id" value="{Context_ExampleObject::ID}">*}
<input type="hidden" name="c" value="wgm.freshbooks">
<input type="hidden" name="a" value="">
<input type="hidden" name="explore_from" value="0">
<table cellpadding="3" cellspacing="0" border="0" width="100%" class="worklistBody">

	{* Column Headers *}
	<tr>
		{foreach from=$view->view_columns item=header name=headers}
			{* start table header, insert column title and link *}
			<th nowrap="nowrap">
			<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewSortBy&id={$view->id}&sortBy={$header}');">{$view_fields.$header->db_label|capitalize}</a>
			
			{* add arrow if sorting by this column, finish table header tag *}
			{if $header==$view->renderSortBy}
				{if $view->renderSortAsc}
					<span class="cerb-sprite sprite-sort_ascending"></span>
				{else}
					<span class="cerb-sprite sprite-sort_descending"></span>
				{/if}
			{/if}
			</th>
		{/foreach}
	</tr>

	{* Column Data *}
	{foreach from=$data item=result key=idx name=results}

	{if $smarty.foreach.results.iteration % 2}
		{assign var=tableRowClass value="even"}
	{else}
		{assign var=tableRowClass value="odd"}
	{/if}
	<tbody style="cursor:pointer;">
		<tr class="{$tableRowClass}">
			<td colspan="{$smarty.foreach.headers.total}">
				<input type="checkbox" name="row_id[]" value="{$result.w_id}" style="display:none;">
				<b class="subject">{$result.w_account_name}</b>
				{*<a href="{devblocks_url}c=example.objects&p=profile&id={$result.w_id}{/devblocks_url}" class="subject">{$result.w_name}</a>*} 
				{*<a href="javascript:;" onclick="genericAjaxPopup('peek','c=example.objects&a=showEntryPopup&id={$result.w_id}&view_id={$view->id}',null,false,'500');"><span class="ui-icon ui-icon-newwin" style="display:inline-block;vertical-align:middle;" title="{$translate->_('views.peek')}"></span></a>*}
			</td>
		</tr>
		
		<tr class="{$tableRowClass}">
		{foreach from=$view->view_columns item=column name=columns}
			{if substr($column,0,3)=="cf_"}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/view/cell_renderer.tpl"}
			{elseif $column=="w_updated" || $column=="w_synchronized"}
				<td title="{$result.$column|devblocks_date}">
					{if !empty($result.$column)}
						{$result.$column|devblocks_prettytime}&nbsp;
					{/if}
				</td>
			{elseif $column=="a_email"}
				<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$result.w_email_id}&view_id={$view->id}',null,false,'550');">{$result.$column}</a></td>
			{elseif $column=="o_name"}
				<td>
					{if empty($result.$column)}
						{if !empty($result.a_email_contact_org_id)}
							{$org = DAO_ContactOrg::get($result.a_email_contact_org_id)}
						{else}
							{$org = null}
						{/if}
						<span style="background-color:rgb(242,222,105);">set org:</span>
						<input type="hidden" name="client_id[]" value="{$result.w_id}">
						<input type="text" name="org_lookup[]" size="32" value="{if !empty($org)}{$org->name}{/if}" class="autocomplete_org input_search">
						<div class="badge badge-lightgray"><a href="javascript:;" style="text-decoration:none;" onclick="$(this).parent().prev('INPUT.autocomplete_org').val('{$result.w_account_name|replace:"'":"\\'"}');">Add new organization</a></div>
					{else}
						<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ORG}&context_id={$result.w_org_id}&view_id={$view->id}',null,false,'600');">{$result.$column}</a>
					{/if}
				</td>
			{else}
				<td>{$result.$column}</td>
			{/if}
		{/foreach}
		</tr>
	</tbody>
	{/foreach}
</table>

<table cellpadding="2" cellspacing="0" border="0" width="100%" id="{$view->id}_actions">
	{if $total}
	<tr>
		<td colspan="2" valign="top">
			<button id="btnLinkOrgs{$view->id}" type="button" style="display:none;"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> Update Organizations</button>
			{*
			<button id="btnExplore{$view->id}" type="button" onclick="this.form.explore_from.value=$(this).closest('form').find('tbody input:checkbox:checked:first').val();this.form.a.value='viewExplore';this.form.submit();"><span class="cerb-sprite sprite-media_play_green"></span> {'common.explore'|devblocks_translate|lower}</button>
			{if 1||$active_worker->hasPriv('example.actions.update_all')}<button type="button" onclick="genericAjaxPopup('peek','c=example.objects&a=showBulkUpdatePopup&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}','row_id[]'),null,false,'500');"><span class="cerb-sprite2 sprite-folder-gear"></span> {'common.bulk_update'|devblocks_translate|lower}</button>{/if}
			*}
		</td>
	</tr>
	{/if}
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			{math assign=fromRow equation="(x*y)+1" x=$view->renderPage y=$view->renderLimit}
			{math assign=toRow equation="(x-1)+y" x=$fromRow y=$view->renderLimit}
			{math assign=nextPage equation="x+1" x=$view->renderPage}
			{math assign=prevPage equation="x-1" x=$view->renderPage}
			{math assign=lastPage equation="ceil(x/y)-1" x=$total y=$view->renderLimit}
			
			{* Sanity checks *}
			{if $toRow > $total}{assign var=toRow value=$total}{/if}
			{if $fromRow > $toRow}{assign var=fromRow value=$toRow}{/if}
			
			{if $view->renderPage > 0}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page=0');">&lt;&lt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$prevPage}');">&lt;{$translate->_('common.previous_short')|capitalize}</a>
			{/if}
			({'views.showing_from_to'|devblocks_translate:$fromRow:$toRow:$total})
			{if $toRow < $total}
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$nextPage}');">{$translate->_('common.next')|capitalize}&gt;</a>
				<a href="javascript:;" onclick="genericAjaxGet('view{$view->id}','c=internal&a=viewPage&id={$view->id}&page={$lastPage}');">&gt;&gt;</a>
			{/if}
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
	if($('#view{$view->id} INPUT.autocomplete_org').length > 0) {
		ajax.orgAutoComplete('#view{$view->id} INPUT.autocomplete_org');
		$('#btnLinkOrgs{$view->id}')
			.click(function() {
				genericAjaxPost('viewForm{$view->id}','','c=wgm.freshbooks&a=viewSetOrgs',function() {
					genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');
				});
			})
			.show()
			;
	}
</script>

{include file="devblocks:cerberusweb.core::internal/views/view_common_jquery_ui.tpl"}