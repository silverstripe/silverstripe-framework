<div id="$id" class="$CSSClasses $extraClass field" href="$CurrentLink">
	<% if Markable %>
		<% include TableListField_SelectOptions %>
	<% end_if %>
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% loop Headings %>
				<th class="$Name">$Title</th>
				<% end_loop %>
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
				<% loop SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>&nbsp;</td>
				<% end_loop %>
				<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
			<% end_if %>
			<% if Can(add) %>
			<tr>
				<% if Markable %><td width="18">&nbsp;</td><% end_if %>
				<td colspan="$ItemCount">
					<input type="hidden" id="{$id}_PopupHeight" value="$PopupHeight" disabled="disabled">
					<input type="hidden" id="{$id}_PopupWidth" value="$PopupWidth" disabled="disabled">
					<a class="popuplink addlink" href="$AddLink" alt="<% _t('RelationComplexTableField.ss.ADD', 'Add') %>"><img src="$ModulePath(framework)/images/add.gif" alt="<% _t('ADD', 'Add') %>" /><% _t('RelationComplexTableField.ss.ADD', 'Add') %> $Title</a>
				</td>
				<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
			<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% loop Items %>
				<% include TableListField_Item %>
			<% end_loop %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% _t('RelationComplexTableField.ss.NOTFOUND', 'No items found') %></i></td>
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
			<a href="$ExportLink" target="_blank"><% _t('RelationComplexTableField.ss.CSVEXPORT', 'Export to CSV' ) %></a>
		<% end_if %>
	</div>
</div>
