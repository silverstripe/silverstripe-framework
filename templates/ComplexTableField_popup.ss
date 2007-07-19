<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<% base_tag %>
</head>
<body>
	<div class="right $PopupClasses">
		$DetailForm
		<img src="cms/images/network-save.gif" class="ajaxloader" style="display: none" />
	</div>
	<% if IsAddMode %>
	<% else %>
		<% if ShowPagination %>
		<table class="PageControls">
			<tr>
				<td class="Left">
					<% if PopupFirstLink %><a href="$PopupFirstLink" title="View first $NameSingular"><img src="cms/images/pagination/record-first.png" alt="View first $NameSingular" /></a>
					<% else %><img src="cms/images/pagination/record-first-g.png" alt="View first $NameSingular" /><% end_if %>
					<% if PopupPrevLink %><a href="$PopupPrevLink" title="View previous $NameSingular"><img src="cms/images/pagination/record-prev.png" alt="View previous $NameSingular" /></a>
					<% else %><img src="cms/images/pagination/record-prev-g.png" alt="View previous $NameSingular" /><% end_if %>
				</td>
				<td class="Count">
					Displaying $PopupCurrentItem of $TotalCount
				</td>
				<td class="Right">
					<% if PopupNextLink %><a href="$PopupNextLink" title="View next $NameSingular"><img src="cms/images/pagination/record-next.png" alt="View next $NameSingular" /></a>
					<% else %><img src="cms/images/pagination/record-next-g.png" alt="View next $NameSingular" /><% end_if %>
					<% if PopupLastLink %><a href="$PopupLastLink" title="View last $NameSingular"><img src="cms/images/pagination/record-last.png" alt="View last $NameSingular" /></a>
					<% else %><img src="cms/images/pagination/record-last-g.png" alt="View last $NameSingular" /><% end_if %>
				</td>
			</tr>
		</table>
		<% end_if %>
	<% end_if %>
</body>
</html>