<% if $isReadonly %>
	<p id="$ID" tabIndex="0" class="form-control-static<% if $extraClass %> $extraClass<% end_if %>">
		$Value
	</p>
<% else %>
	<input $AttributesHTML />
<% end_if %>
