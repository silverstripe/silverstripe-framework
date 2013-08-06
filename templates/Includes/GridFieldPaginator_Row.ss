<tr>
	<td class="bottom-all" colspan="$Colspan">
		<% if $OnlyOnePage %>
		<% else %>
			<div class="datagrid-pagination">
				$FirstPage $PreviousPage 
				<span class="pagination-page-number">
					<% _t('Pagination.Page', 'Page') %> 
					<input class="text" value="$CurrentPageNum" data-skip-autofocus="true" /> 
					<% _t('TableListField_PageControls_ss.OF', 'of', 'Example: View 1 of 2') %> 
					$NumPages
				</span> 
				$NextPage $LastPage 
			</div>
		<% end_if %>
		<span class="pagination-records-number">
			<% _t('Pagination.View', 'View', 'Verb. Example: View 1 of 2') %>
			{$FirstShownRecord}&ndash;{$LastShownRecord}
			<% _t('TableListField_PageControls_ss.OF', 'of', 'Example: View 1 of 2') %> 
			$NumRecords
		</span>
	</td>
</tr>