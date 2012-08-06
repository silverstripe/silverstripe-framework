<div id="$id" class="$CSSClasses $extraClass field nolabel">
	<% if Print %><% else %>
		<% if Markable %>
			<% include TableListField_SelectOptions %>
		<% end_if %>
		<% include TableListField_PageControls %>
	<% end_if %>
	<table class="data">
		<thead>
			<tr>
			<% if Markable %><th width="16"><% if MarkableTitle %>$MarkableTitle<% else %>&nbsp;<% end_if %></th><% end_if %>
			<% if Print %>
				<% loop Headings %>
				<th class="$Name">
					$Title
				</th>
				<% end_loop %>
			<% else %>
			<% loop Headings %>
				<th class="$Name">
				<% if IsSortable %>
					<span class="sortTitle">
						<a href="$SortLink">$Title</a>
					</span>
					<span class="sortLink <% if SortBy %><% else %>sortLinkHidden<% end_if %>">
					<% if SortDirection = desc %>
						<a href="$SortLink"><img src="$ModulePath(framework)/images/bullet_arrow_up.png" alt="<% _t('TableListField.ss.SORTDESC', 'Sort in descending order') %>" /></a>
					<% else %>
						<a href="$SortLink"><img src="$ModulePath(framework)/images/bullet_arrow_down.png" alt="<% _t('TableListField.ss.SORTASC', 'Sort in ascending order') %>" /></a>
					<% end_if %>
						</a>
						&nbsp;
					</span>
				<% else %>
					<span>$Title</span>
				<% end_if %>
				</th>
			<% end_loop %>
			<% end_if %>
			<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		
		<% if HasSummary %>
		<tfoot>
			<tr class="summary">
				<% include TableListField_Summary %>
			</tr>
		</tfoot>
		<% end_if %>
		
		<tbody>
			<% if HasGroupedItems %>
				<% loop GroupedItems %>
					<% loop Items %>
						<% include TableListField_Item %>
					<% end_loop %>
					<tr class="summary partialSummary">
						<% include TableListField_Summary %>
					</tr>
				<% end_loop %>
			<% else %>
				<% if Items %>
					<% loop Items %>
						<% include TableListField_Item %>
					<% end_loop %>
				<% else %>
					<tr class="notfound">
						<% if Markable %><th width="18">&nbsp;</th><% end_if %>
						<td colspan="$Headings.Count"><i><% _t('TableListField.ss.NOITEMSFOUND','No items found') %></i></td>
						<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
					</tr>
				<% end_if %>
				<% if Can(add) %>
					$AddRecordAsTableRow
				<% end_if %>
			<% end_if %>
		</tbody>
	</table>
	<% if Print %><% else %><div class="utility">
		<% loop Utility %>
			<span class="item"><a href="$Link">$Title</a></span>
		<% end_loop %>
	</div><% end_if %>
</div>
