<div<% if ID %> id="{$ID}_holder"<% end_if %> class="<% if extraClass %>$extraClass<% else %>fieldgroup<% end_if %><% if Zebra %> fieldgroup-zebra<% end_if %>">
	<% loop FieldList %>
		<div class="fieldgroup-field $FirstLast $EvenOdd">
			$FieldHolder
		</div>
	<% end_loop %>
</div>
