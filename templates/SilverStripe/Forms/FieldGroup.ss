<div class="<% if $extraClass %>$extraClass<% else %>fieldgroup<% end_if %><% if $Zebra %> fieldgroup-zebra<% end_if %>" <% if $ID %>id="$ID"<% end_if %>>
	<% loop $FieldList %>
		<div class="fieldgroup-field $FirstLast $EvenOdd">
			$SmallFieldHolder
		</div>
	<% end_loop %>
</div>
