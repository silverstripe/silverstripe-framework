<div id="$id" class="$CSSClasses $extraClass field nolabel" href="$CurrentLink">
  <div class="middleColumn">
		<% if Markable %>
			<% include TableListField_SelectOptions %>
		<% end_if %>
	<% include TableListField_PageControls %>
	<table class="data">
		<thead>
			<tr>
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<% loop Headings %>
				<th class="$Name">
					<% if IsSortable %>
						<span class="sortTitle">
							<a href="$SortLink">$Title</a>
						</span>
						<span class="sortLink <% if SortBy %><% else %>sortLinkHidden<% end_if %>">
							<a href="$SortLink">
								<% if SortDirection = desc %>
								<img src="$ModulePath(framework)/images/bullet_arrow_up.png" alt="<% _t('ComplexTableField.ss.SORTASC', 'Sort ascending') %>" />
								<% else %>
								<img src="$ModulePath(framework)/images/bullet_arrow_down.png" alt="<% _t('ComplexTableField.ss.SORTDESC', 'Sort descending') %>" />
								<% end_if %>
							</a>
							&nbsp;
						</span>
					<% else %>
						$Title
					<% end_if %>
				</th>
				<% end_loop %>
				<% loop Actions %><th width="18">&nbsp;</th><% end_loop %>
			</tr>
		</thead>
		<tfoot>
			<% if HasSummary %>
			<tr class="summary">
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<td><i>$SummaryTitle</i></td>
				<% loop SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>$SummaryValue</td>
				<% end_loop %>
				<% loop Actions %><td width="18">&nbsp;</td><% end_loop %>
			</tr>
			<% end_if %>
			<% if Can(add) %>
			<tr>
				<% if Markable %><td width="18">&nbsp;</td><% end_if %>
				<td colspan="$ItemCount">
					<input type="hidden" id="{$id}_PopupHeight" value="$PopupHeight" disabled="disabled">
					<input type="hidden" id="{$id}_PopupWidth" value="$PopupWidth" disabled="disabled">
					<a class="popuplink addlink" href="$AddLink" alt="add"><img src="$ModulePath(framework)/images/add.gif" alt="<% _t('ComplexTableField.ss.ADDITEM', 'add') %>" />
						<% sprintf(_t('ADDITEM', 'Add %s', 'Add [name]'),$Title) %>
					</a>
				</td>
				<% loop Actions %><td width="18">&nbsp;</td><% end_loop %>
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
					<td colspan="$Headings.Count"><i><% _t('ComplexTableField.ss.NOITEMSFOUND', 'No items found') %></i></td>
					<% loop Actions %><td width="18">&nbsp;</td><% end_loop %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	<% if Utility %>
		<div class="utility">
			<% loop Utility %>
				<span class="item"><a href="$Link" target="_blank">$Title</a></span>
			<% end_loop %>
		</div>
	<% end_if %>
	</div>
</div>
