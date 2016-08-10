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
		<script type="text/javascript">
			// Ensure top level section is updated
			(function($) {
				$(function() {
					var securityID = '{$SecurityID.JS}',
						memberToken = '{$CurrentMember.TempIDHash.JS}',
						domain = $.path.parseUrl(window.location.href).domain;

					window.top.postMessage(
						JSON.stringify({
							type: 'callback',
							callback: 'reauthenticate',
							target: '.leftandmain-logindialog',
							data: {
								'SecurityID': securityID,
								'TempID': memberToken
							}
						}),
						domain
					);
				});
			})(jQuery);
		</script>
	</body>

</html>
