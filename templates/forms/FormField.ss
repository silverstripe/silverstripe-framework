<% if $isReadonly %>
	<p id="$ID" class="form-control-static<% if $extraClass %> $extraClass<% end_if %>">
		$Value
	</p>
<% else %>
	<input $AttributesHTML />
<% end_if %>
