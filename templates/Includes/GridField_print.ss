<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//MI" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="mi" lang="mi">
	<head>
		
	</head>
	<body>
		<% if $Title %><h3>$Title</h3><% end_if %>
		<table>
			<thead>
				<tr><% control Header %><td>$CellString</td><% end_control %></tr>
			</thead>
			<tbody>
				<% control ItemRows %>
					<tr><% control ItemRow %><td>$CellString</td><% end_control %></tr>
				<% end_control %>
			</tbody>
		</table>
		<p>
			Printed at $Datetime.Nice
			<br />
			Printed by $Member.Name
		</p>
	</body>
</html>