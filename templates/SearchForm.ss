<form $FormAttributes>
	<fieldset>
		<% loop Fields %>
			$FieldHolder
		<% end_loop %>
		<% loop Actions %>
			$Field
		<% end_loop %>
	</fieldset>
</form>
