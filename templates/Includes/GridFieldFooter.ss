<tr>
	<td class="bottom-all" colspan="$Colspan">
		<span class="pagination-records-number">
			$FirstShownRecord - 
			$LastShownRecord
			<% _t('TableListField_PageControls.ss.OF', 'of', 'Example: View 1 of 2') %>
			$NumRecords
		</span>

		<% if Message %>
		<div class="datagrid-footer-message">$Message</div>
		<% end_if %>
	</td>
</tr>