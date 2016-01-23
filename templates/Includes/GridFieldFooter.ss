<tr>
	<td class="bottom-all" colspan="$Colspan">
		<% if $ShowRecordCount %>
			<span class="pagination-records-number">
				$FirstShownRecord -
				$LastShownRecord
				<%t TableListField_PageControls_ss.OF 'of' is 'Example: View 1 of 2' %>
				$NumRecords
			</span>
		<% end_if %>
		<% if $Message %>
		<div class="datagrid-footer-message">$Message</div>
		<% end_if %>
	</td>
</tr>
