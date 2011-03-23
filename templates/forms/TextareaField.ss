<% if isReadonly %>
	<span id="$ID" class="textarea readonly$extraClass"><% if Value %>$Value<% else %><em>(<% _t('NONE', 'none') %>)</em><% end_if %></span>
<% else %>
	<textarea id="$ID" class="textarea$extraClass" name="$Name" rows="$Rows" cols="$Cols"<% if isDisabled %> disabled<% end_if %>></textarea>
<% end_if %>