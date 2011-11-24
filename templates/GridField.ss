<% require css(sapphire/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css) %>
<% require css(sapphire/css/GridField.css) %>

<div class="ss-gridfield ui-state-default" id="$Name">
	<table>
		<thead>
			<tr>
				<% control Headings %>
				<th class="<% if FirstLast %>ss-gridfield-{$FirstLast} <% if IsSortable %> ss-gridfield-sortable<% end_if %> <% if IsSorted %>ss-gridfield-sorted ss-gridfield-{$SortedDirection}">
					$Title <span class="ui-icon"></span></th>
				<% end_control %>
			</tr>
		</thead>
		
		<tbody>
			<% control Items %>
				<% include GridField_Item %>
			<% end_control %>
		</tbody>
		
		<tfoot>

		</tfoot>
	</table>
</div>