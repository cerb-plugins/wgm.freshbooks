<form id="frmOrgFreshbooksClient" action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="wgm.freshbooks">
<input type="hidden" name="a" value="doOrgAddClient">
<input type="hidden" name="org_id" value="{$org->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{$org->name} wasn't found in Freshbooks...</legend>
	
	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td><b>{'common.organization'|devblocks_translate|capitalize}:</b></td>
			<td><input type="text" name="name" value="{$org->name}" size="45"></td>
		</tr>
		<tr>
			<td valign="top"><b>{'common.email'|devblocks_translate|capitalize}:</b></td>
			<td>
				{if !empty($addresses)}
				<ul style="margin:0px;list-style:none;padding:0px;">
					{foreach from=$addresses item=address}
					<li style="margin:2px;">
						<div class="badge badge-lightgray">
							{* [TODO] Fix references to ->first_name and ->last_name on $addy here *}
							<a href="javascript:;" onclick="$this=$(this);$form=$this.closest('form');$form.find('input[name=email]').val('{$address->email}');$form.find('input[name=first_name]').val('{$address->first_name}');$form.find('input[name=last_name]').val('{$address->last_name}');$this.closest('ul').fadeOut();" style="text-decoration:none;">use</a>
						</div>
						{$name = $address->getName()}
						{if !empty($name)}{$name}{/if} 
						&lt;{$address->email}&gt;
						({$address->num_nonspam} messages)
					</li>
					{/foreach}
				{/if}
				</ul>
				<input type="text" name="email" value="" size="45">
			</td>
		</tr>
		<tr>
			<td><b>{'common.name.first'|devblocks_translate|capitalize}</b>:</td>
			<td>
				<input type="text" name="first_name" value="" size="45">
			</td>
		</tr>
		<tr>
			<td><b>{'common.name.last'|devblocks_translate|capitalize}</b>:</td>
			<td>
				<input type="text" name="last_name" value="" size="45">
			</td>
		</tr>
	</table>
	
	<button type="submit"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span> {'common.add'|devblocks_translate|capitalize}</button>
</fieldset>

</form>

<script type="text/javascript">
$(function() {
	ajax.emailAutoComplete('#frmOrgFreshbooksClient input[name=email]');
});
</script>