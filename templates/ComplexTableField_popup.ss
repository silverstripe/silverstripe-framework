<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<% base_tag %>
	</head>
	<body class="cms" style="overflow: auto;">
		<div class="right $PopupClasses">
			$DetailForm
		</div>

		<% if ShowPagination %>
			<table id="ComplexTableField_Pagination">
				<tr>
					<% if Paginator.PrevLink %>
						<td id="ComplexTableField_Pagination_Previous">
							<a href="$Paginator.PrevLink"><img src="$ModulePath(framework)/images/pagination/record-prev.png" /><% _t('ComplexTableField_popup.ss.PREVIOUS', 'Previous') %></a>
						</td>
					<% end_if %>
					<% if xdsfdsf %>
					<% else %>
						<td>
							<% loop Paginator.Pages %>
								<% if active %>
									<a href="$link">$number</a>
								<% else %>
									<span>$number</span>
								<% end_if %>
							<% end_loop %>
						</td>
					<% end_if %>
					<% if Paginator.NextLink %>
						<td id="ComplexTableField_Pagination_Next">
							<a href="$Paginator.NextLink"><% _t('ComplexTableField_popup.ss.NEXT', 'Next') %><img src="$ModulePath(framework)/images/pagination/record-next.png" /></a>
						</td>
					<% end_if %>
				</tr>
			</table>
		<% end_if %>
	</body>
</html>
