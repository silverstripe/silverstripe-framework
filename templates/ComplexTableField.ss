<div id="$id" class="$CSSClasses field" href="$CurrentLink">
  <div class="middleColumn">
		<% if Markable %>
			<% include TableListField_SelectOptions %>
		<% end_if %>
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
							<a href="$SortLink">
								<% if SortDirection = desc %>
								<img src="cms/images/bullet_arrow_up.png" alt="<% _t('SORTASC', 'Sort ascending') %>" />
								<% else %>
								<img src="cms/images/bullet_arrow_down.png" alt="<% _t('SORTDESC', 'Sort descending') %>" />
								<% end_if %>
							</a>
							&nbsp;
						</span>
					<% else %>
						$Title
					<% end_if %>
				</th>
				<% end_control %>
				<% control Actions %><th width="18">&nbsp;</th><% end_control %>
			</tr>
		</thead>
		<tfoot>
			<% if HasSummary %>
			<tr class="summary">
				<% if Markable %><th width="18">&nbsp;</th><% end_if %>
				<td><i>$SummaryTitle</i></td>
				<% control SummaryFields %>
					<td<% if Function %> class="$Function"<% end_if %>>$SummaryValue</td>
				<% end_control %>
				<% control Actions %><td width="18">&nbsp;</td><% end_control %>
			</tr>
			<% end_if %>
			<% if Can(add) %>
			<tr>
				<% if Markable %><td width="18">&nbsp;</td><% end_if %>
				<td colspan="$ItemCount">
					<input type="hidden" id="{$id}_PopupHeight" value="$PopupHeight" disabled="disabled">
					<input type="hidden" id="{$id}_PopupWidth" value="$PopupWidth" disabled="disabled">
					<a class="popuplink addlink" href="$AddLink" alt="add"><img src="cms/images/add.gif" alt="<% _t('ADDITEM', 'add') %>" />
						<% sprintf(_t('ADDITEM', 'Add %s', PR_MEDIUM, 'Add [name]'),$Title) %>
					</a>
				</td>
				<% control Actions %><td width="18">&nbsp;</td><% end_control %>
			</tr>
			<% end_if %>
		</tfoot>
		<tbody>
			<% if Items %>
			<% control Items %>
				<% include TableListField_Item %>
			<% end_control %>
			<% else %>
				<tr class="notfound">
					<% if Markable %><th width="18">&nbsp;</th><% end_if %>
					<td colspan="$Headings.Count"><i><% _t('NOITEMSFOUND', 'No items found') %></i></td>
					<% control Actions %><td width="18">&nbsp;</td><% end_control %>
				</tr>
			<% end_if %>
		</tbody>
	</table>
	<div class="utility">
		<% if Can(export) %>
			<a href="$ExportLink" target="_blank"><% _t('CSVEXPORT', 'Export to CSV' ) %></a>
		<% end_if %>
	</div>
	</div>
</div>
