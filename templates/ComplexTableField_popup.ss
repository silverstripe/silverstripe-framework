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
				<table id="ComplexTableField_Pagination">
					<tr>
						<% if PopupPrevLink %>
							<td id="ComplexTableField_Pagination_Previous">
								<a href="$PopupPrevLink"><img src="cms/images/pagination/record-prev.png" /></a>
								<a href="$PopupPrevLink"><div>Previous</div></a>
							</td>
						<% end_if %>
						<% if TotalCount == 1 %>
						<% else %>
							<td>
								<% control pagination %>
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
								<a href="$PopupNextLink"><img src="cms/images/pagination/record-next.png" /></a>
								<a href="$PopupNextLink"><div>Next</div></a>
							</td>
						<% end_if %>
					</td>
				<% end_if %>
			<% end_if %>
	</body>
</html>