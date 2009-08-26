<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>
	<% if Message %>
	<p id="{$FormName}_error" class="message $MessageType">$Message</p>
	<% else %>
	<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>
	
	<fieldset>
		<% if Legend %><legend>$Legend</legend><% end_if %> 
		<% control Fields %>
			$FieldHolder
		<% end_control %>
		<div class="clear"><!-- --></div>
	</fieldset>

	<% if Actions %>
	<div class="Actions">
		<% control Actions %>
			$Field
		<% end_control %>
	</div>
	<% end_if %>
<% if IncludeFormTag %>
</form>
<% end_if %>
