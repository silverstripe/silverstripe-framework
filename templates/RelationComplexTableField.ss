<div id="$id" class="$CSSClasses" href="$CurrentLink">
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% control Headings %>
				<th class="$Name">$Title</th>
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
					<a class="popuplink addlink" href="$AddLink" alt="<% _t('ADD', 'Add') %>"><img src="cms/images/add.gif" alt="<% _t('ADD', 'Add') %>" /><% _t('ADD', 'Add') %> $Title</a>
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
				<tr id="record-$Parent.Name-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
					<% if Markable %><td width="18" class="markingcheckbox">$MarkingCheckbox</td><% end_if %>
					<% control Fields %>
					<td>$Value</td>
					<% end_control %>
					<% if Can(show) %>
						<td width="18"><a class="popuplink showlink" href="$ShowLink" target="_blank"><img src="cms/images/show.png" alt="<% _t('SHOW', 'show') %>" /></a></td>
					<% end_if %>
					<% if Can(edit) %>
						<td width="18"><a class="popuplink editlink" href="$EditLink" target="_blank"><img src="cms/images/edit.gif" alt="<% _t('EDIT', 'edit') %>" /></a></td>
					<% end_if %>
					<% if Can(delete) %>
						<td width="18"><a class="deletelink" href="$DeleteLink" title="Delete this row"><img src="cms/images/delete.gif" alt="<% _t('DELETE', 'delete') %>" /></a></td>
					<% end_if %>
				</tr>
			<% end_control %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% _t('NOTFOUND', 'No items found') %></i></td>
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
			<a href="$ExportLink" target="_blank"><% _t('CSVEXPORT', 'Export to CSV' ) %></a>
		<% end_if %>
	</div>
</div>
