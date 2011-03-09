<div id="$id" class="$CSSClasses field">
  <div class="middleColumn">
	<% include TableListField_PageControls %>
	<% if Message %>
	<p id="{$id}_error" class="message $MessageType">$Message</p>
	<% else %>
	<p id="{$id}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>
	<table class="data">
		<thead>
			<tr>
				<% control Headings %>
					<th class="$Name $Class" scope="col">$Title</th>
				<% end_control %>
				<th style="display: none"></th>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tfoot>
		<% if HasSummary %>
			<tr class="summary">
				<td><i>$SummaryTitle</i></td>
				<% control SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>$SummaryValue</td>
				<% end_control %>
				<th style="display: none"></th>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
		<% end_if %>
		<% if Can(add) %>
			<tr>
				<td colspan="$ItemCount">
					<a href="#" class="addrow" title="<% _t('ADD', 'Add a new row') %>"><img src="cms/images/add.gif" alt="<% _t('ADD','Add a new row') %>" />
						<% sprintf(_t('ADDITEM','Add %s'),$Title) %>
					</a>
				</td>
				<td style="display: none"></td>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
		<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% control Items %>
				<tr id="record-$Parent.id-$ID" class="row<% if HighlightClasses %> $HighlightClasses<% end_if %>">
					<% control Fields %>
						<td class="$FieldClass $extraClass $ClassName $Title tablecolumn">$Field</td>
					<% end_control %>
					<td style="display: none">$ExtraData</td>
					<% if Can(delete) %><td width="18"><a class="deletelink" href="$DeleteLink" title="<% _t('DELETEROW') %>"><img src="cms/images/delete.gif" alt="<% _t('DELETE') %>" /></a></td><% end_if %>
				</tr>
			<% end_control %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% _t('NOITEMSFOUND') %></i></td>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	<div class="utility">
		<% if Can(export) %>
			<a href="$ExportLink" target="_blank"><% _t('CSVEXPORT', 'Export to CSV' ) %></a>
		<% end_if %>
	</div>
	<% if Message %>
	<span class="message $MessageType">$Message</span>
	<% end_if %>
	</div>
</div>