<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<% base_tag %>
	</head>
	<body>
		<div class="right $PopupClasses">
			$DetailForm
		</div>
		<% if IsAddMode %>
		<% else %>
			<% if ShowPagination %>
				<table id="ComplexTableField_Pagination">
					<tr>
						<% if PopupPrevLink %>
							<td id="ComplexTableField_Pagination_Previous">
								<a href="$PopupPrevLink"><img src="cms/images/pagination/record-prev.png" /><% _t('PREVIOUS', 'Previous') %></a>
							</td>
						<% end_if %>
						<% if TotalCount == 1 %>
						<% else %>
							<td>
								<% control Pagination %>
									<% if active %>
										<a href="$link">$number</a>
									<% else %>
										<span>$number</span>
									<% end_if %>
								<% end_control %>
							</td>
						<% end_if %>
						<% if PopupNextLink %>
							<td id="ComplexTableField_Pagination_Next">
								<a href="$PopupNextLink"><% _t('NEXT', 'Next') %><img src="cms/images/pagination/record-next.png" /></a>
							</td>
						<% end_if %>
					</td>
				<% end_if %>
			<% end_if %>
	</body>
</html>
