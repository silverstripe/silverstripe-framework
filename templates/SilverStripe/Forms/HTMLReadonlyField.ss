<span class="readonly typography" id="$ID">
	<% if $Value %>$Value<% else %><i>(not set)</i><% end_if %>
</span>
<% if $IncludeHiddenField %>
	<input type="hidden" name="$Name.ATT" value="$ValueEntities.RAW" />
<% end_if %>
