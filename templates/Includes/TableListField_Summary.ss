				<% if Markable %><th width="16">&nbsp;</th><% end_if %>
				<th><i>$SummaryTitle</i></th>
				<% control SummaryFields %>
					<th class="field-$Name.HTMLATT<% if Function %> $Function<% end_if %>">$SummaryValue</th>
				<% end_control %>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>