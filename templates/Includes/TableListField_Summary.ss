				<% if Markable %><th width="16">&nbsp;</th><% end_if %>
				<th><i>$SummaryTitle</i></th>
				<% control SummaryFields %>
					<th<% if Function %> class="$Function"<% end_if %>>$SummaryValue</th>
				<% end_control %>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>