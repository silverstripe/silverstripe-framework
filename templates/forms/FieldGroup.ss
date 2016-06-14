<div class="<% if $extraClass %>$extraClass<% else %>form__fieldgroup<% end_if %><% if $Zebra %> form__fieldgroup-zebra<% end_if %>" <% if $ID %>id="$ID"<% end_if %>>
	<% loop $FieldList %>
		<div class="form__fieldgroup-item $FirstLast $EvenOdd">
			$SmallFieldHolder
		</div>
	<% end_loop %>
</div>
