<!DOCTYPE html>
<html>
	<head>
		<% base_tag %>
		<title>$Title</title>
	</head>
	<body class="cms cms-security">
		<h1>$Title</h1>
		<% if $Content %>
			<div class="Content">$Content</div>
		<% end_if %>
		<div class="Form">
			$Form
		</div>
	</body>

</html>
