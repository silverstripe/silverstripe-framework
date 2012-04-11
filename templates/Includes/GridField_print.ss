<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<% if $Title %><title>$Title</title><% end_if %>
	</head>
	<body onload="window.print();">
		<% if $Title %><h3>$Title</h3><% end_if %>
		<table>
			<thead>
				<tr><% control Header %><th>$CellString</th><% end_control %></tr>
			</thead>
			<tbody>
				<% control ItemRows %>
					<tr><% control ItemRow %><td>$CellString</td><% end_control %></tr>
				<% end_control %>
			</tbody>
		</table>
		<p>
			<% _t('GridField.PRINTEDAT', 'Printed at') %> $Datetime.Time, $Datetime.Date
			<br />
			<% _t('GridField.PRINTEDBY', 'Printed by') %> $Member.Name
		</p>
	</body>

</html>