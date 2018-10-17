<tr>
	<td class="grid-field__paginator bottom-all" colspan="$Colspan">
		<% if $OnlyOnePage %>
		<% else %>
			<div class="grid-field__paginator__controls datagrid-pagination">
				$FirstPage $PreviousPage
				<span class="pagination-page-number">
					<%t SilverStripe\\Forms\\GridField\\GridFieldPaginator.Page 'Page' %>
					<input class="text no-change-track" title="Current page" value="$CurrentPageNum" data-skip-autofocus="true" />
					<%t SilverStripe\\Forms\\GridField\\GridFieldPaginator.OF 'of' is 'Example: View 1 of 2' %>
					$NumPages
				</span>
				$NextPage $LastPage
			</div>
		<% end_if %>
		<span class="grid-field__paginator_numbers pagination-records-number">
			<%t SilverStripe\\Forms\\GridField\\GridFieldPaginator.View 'View' is 'Verb. Example: View 1 of 2' %>
			{$FirstShownRecord}&ndash;{$LastShownRecord}
			<%t SilverStripe\\Forms\\GridField\\GridFieldPaginator.OF 'of' is 'Example: View 1 of 2' %>
			$NumRecords
		</span>
	</td>
</tr>
