<div class="reportfilter">
	$FilterForm
</div>
<div class="reports" id="FormSubmissions">
<% control Submissions %>
	<div class="report">
		<span class="submitted"><% _t('SUBMITTED', 'Submitted at') %> $Created.Nice <% if Recipient %>to $Recipient<% end_if %></span>
		<table>
			<% control FieldValues %>
				<tr>
					<td class="field">$Title</td>
					<td class="value">$Value</td>
				</tr>
			<% end_control %>	
		</table>
	</div>
<% end_control %>
</div>