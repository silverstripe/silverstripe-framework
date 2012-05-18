<tr>
	<td class="bottom-all" colspan="$Colspan">
		<% if $OnlyOnePage %>
		<% else %>
			<div class="datagrid-pagination">
				$FirstPage $PreviousPage <span class="pagination-page-number">Page <input class="text" value="$CurrentPageNum" data-skip-autofocus="true" /> of $NumPages</span> $NextPage $LastPage 
			</div>
		<% end_if %>
		<span class="pagination-records-number">View $FirstShownRecord - $LastShownRecord of $NumRecords</span>
	</td>
</tr>