<div id="$id" class="$CSSClasses $extraClass field" href="$CurrentLink">
	<div class="MemberFilter filterBox">
		$SearchForm
	</div>
	<div id="MemberList"> 
		<% if Markable %>
			<% include TableListField_SelectOptions %>
		<% end_if %>
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
				<% if Can(inlineadd) %>
					<tr class="addtogrouprow">
						<% if Markable %><td width="18">&nbsp;</dh><% end_if %>
						$AddRecordForm.CellFields
						<td class="actions" colspan="3">$AddRecordForm.CellActions</td>
					</tr>
				<% end_if %>
				<tr style="display: none;">
					<% if Markable %><td width="18">&nbsp;</td><% end_if %>
					<td colspan="$ItemCount">
						<input type="hidden" id="{$id}_PopupHeight" value="$PopupHeight" disabled="disabled"/>
						<input type="hidden" id="{$id}_PopupWidth" value="$PopupWidth" disabled="disabled"/>
						<a class="popuplink addlink" href="$AddLink" alt="add"><img src="sapphire/images/add.gif" alt="add" /></a><a class="popuplink addlink" href="$AddLink" alt="add"><% _t('ADDNEW','Add new',50,'Followed by a member type') %> $Title</a>
					</td>
					<% if Can(show) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(edit) %><td width="18">&nbsp;</td><% end_if %>
					<% if Can(delete) %><td width="18">&nbsp;</td><% end_if %>
				</tr>
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
			<% control Utility %>
				<span class="item"><a href="$Link" target="_blank">$Title</a></span>
			<% end_control %>
		</div>
	</div>
</div>