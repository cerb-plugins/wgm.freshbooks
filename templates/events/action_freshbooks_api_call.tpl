<div class="freshbooks-api-xml">
	<b>Request XML:</b>
	<div style="margin-left:10px;margin-bottom:10px;">
		<textarea rows="3" cols="60" name="{$namePrefix}[xml]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.xml}</textarea>
	</div>
</div>

<b>Save response to a variable named:</b><br>
<div style="margin-left:15px;margin-bottom:5px;">
	<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_freshbooks_response"}" size="45" style="width:100%;" placeholder="e.g. _freshbooks_response">
</div>

<b>Send live API requests in simulator mode:</b><br>
<div style="margin-left:15px;margin-bottom:5px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if empty($params.run_in_simulator)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	$action.find('textarea').elastic();
});
</script>
