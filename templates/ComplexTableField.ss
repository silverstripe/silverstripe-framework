<div id="$id" class="$Classes">
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">
					<% if IsSortable %>
						<span class="sortTitle">
							<a href="$SortLink">$Title</a>
						</span>
						<span class="sortLink <% if SortBy %><% else %>sortLinkHidden<% end_if %>">
							<a href="$SortLink"">
								<% if SortDirection = desc %>
								<img src="cms/images/bullet_arrow_up.png" alt="Sort ascending" />
								<% else %>
								<img src="cms/images/bullet_arrow_down.png" alt="Sort descending" />
								<% end_if %>
							</a>
							&nbsp;
						</span>
					<% else %>
						$Title
					<% end_if %>
				</th>
				<% end_control %>
				<% if Can(show) %><th width="18">&nbsp;</th><% end_if %>
				<% if Can(edit) %><th width="18">&nbsp;</th><% end_if %>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tfoot>
			<% if HasSummary %>
			<tr class="summary">
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<td><i>$SummaryTitle</i></td>
				<% control SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>&nbsp;</td>
				<% end_control %>
				<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
			<% end_if %>
			<% if Can(add) %>
			<tr>
				<% if Markable %><td width="18">&nbsp;</td><% end_if %>
				<td colspan="$ItemCount">
					<a class="popuplink addlink" href="$AddLink" alt="add"><img src="cms/images/add.gif" alt="add" />Add $Title</a>
				</td>
				<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
			<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% control Items %>
				<tr id="record-$Parent.id-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
					<% if Markable %><td width="18" class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<% if Can(show) %>
						<td width="18"><a class="popuplink showlink" href="$ShowLink" target="_blank"><img src="cms/images/show.png" alt="show" /></a></td>
					<% end_if %>
					<% if Can(edit) %>
						<td width="18"><a class="popuplink editlink" href="$EditLink" target="_blank"><img src="cms/images/edit.gif" alt="edit" /></a></td>
					<% end_if %>
					<% if Can(delete) %>
						<td width="18"><a class="deletelink" href="$DeleteLink" title="Delete this row"><img src="cms/images/delete.gif" alt="delete" /></a></td>
					<% end_if %>
				</tr>
			<% end_control %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i>No items found</i></td>
					<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	$ExtraData
	<div class="utility">
		<% if Can(export) %>
			$ExportButton
		<% end_if %>
	</div>
</div>