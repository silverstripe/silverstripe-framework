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
				<% loop Headings %>
					<th class="$Name $Class" scope="col">$Title</th>
				<% end_loop %>
				<th style="display: none"></th>
				<% if Can(delete) %><th width="18">&nbsp;</th><% end_if %>
			</tr>
		</thead>
		<tfoot>
		<% if HasSummary %>
			<tr class="summary">
				<td><i>$SummaryTitle</i></td>
				<% loop SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>$SummaryValue</td>
				<% end_loop %>
				<th style="display: none"></th>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
		<% end_if %>
		<% if Can(add) %>
			<tr>
				<td colspan="$ItemCount">
					<a href="#" class="addrow" title="<% _t('TableField.ss.ADD', 'Add a new row') %>"><img src="$ModulePath(framework)/images/add.gif" alt="<% _t('TableField.ss.ADD','Add a new row') %>" />
						<% sprintf(_t('TableField.ss.ADDITEM','Add %s'),$Title) %>
					</a>
				</td>
				<td style="display: none"></td>
				<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
			</tr>
		<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% loop Items %>
				<tr id="record-$Parent.id-$ID" class="row<% if HighlightClasses %> $HighlightClasses<% end_if %>">
					<% loop Fields %>
						<td class="$FieldClass $extraClass $ClassName $Title tablecolumn">$Field</td>
					<% end_loop %>
					<td style="display: none">$ExtraData</td>
					<% if Can(delete) %><td width="18"><a class="deletelink" href="$DeleteLink" title="<% _t('TableField.ss.DELETEROW') %>"><img src="$ModulePath(framework)/images/delete.gif" alt="<% _t('TableField.ss.DELETE') %>" /></a></td><% end_if %>
				</tr>
			<% end_loop %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% _t('TableField.ss.NOITEMSFOUND') %></i></td>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	<% if Print %><% else %><div class="utility">
		<% loop Utility %>
			<span class="item"><a href="$Link">$Title</a></span>
		<% end_loop %>
	</div><% end_if %>
	<% if Message %>
	<span class="message $MessageType">$Message</span>
	<% end_if %>
	</div>
</div>
