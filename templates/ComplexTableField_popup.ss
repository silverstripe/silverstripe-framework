<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<% base_tag %>
	</head>
	<body>
		<div class="right $PopupClasses">
			$DetailForm
		</div>

		<% if ShowPagination %>
			<table id="ComplexTableField_Pagination">
				<tr>
					<% if Paginator.PrevLink %>
						<td id="ComplexTableField_Pagination_Previous">
							<a href="$Paginator.PrevLink"><img src="cms/images/pagination/record-prev.png" /><% _t('PREVIOUS', 'Previous') %></a>
						</td>
					<% end_if %>
					<% if xdsfdsf %>
					<% else %>
						<td>
							<% control Paginator.Pages %>
								<% if active %>
									<a href="$link">$number</a>
								<% else %>
									<span>$number</span>
								<% end_if %>
							<% end_control %>
						</td>
					<% end_if %>
					<% if Paginator.NextLink %>
						<td id="ComplexTableField_Pagination_Next">
							<a href="$Paginator.NextLink"><% _t('NEXT', 'Next') %><img src="cms/images/pagination/record-next.png" /></a>
						</td>
					<% end_if %>
				</tr>
			</table>
		<% end_if %>
	</body>
</html>
