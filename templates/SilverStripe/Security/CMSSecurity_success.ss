<!DOCTYPE html>
<html>
	<head>
		<% base_tag %>
		<title><%t SilverStripe\\Security\\CMSSecurity.SUCCESS_TITLE 'Login successful' %></title>
	</head>
	<body class="cms cms-security">
		<div class="cms-security__container container fill-height">
            <div class="row">
                <h1>
                    <span class="icon font-icon-back-in-time"></span>
                    <%t SilverStripe\\Security\\CMSSecurity.SUCCESS_TITLE 'Login successful' %>
                </h1>
            </div>
            <% if $Content %>
                <div class="row">
                    <div class="Content">$Content</div>
                </div>
            <% end_if %>
            <div class="row">
                <div class="cms-security__container__form">
                    $Form
                </div>
            </div>
        </div>
		<script type="text/javascript">
			// Ensure top level section is updated
			jQuery(function () {
				var origin = window.location.origin ||
					[
					  window.location.protocol,
						"//",
						window.location.hostname,
						(window.location.port ? ':' + window.location.port : '')
					].join('');
				var securityID = '{$SecurityID.JS}';
				var memberToken = '{$CurrentMember.TempIDHash.JS}';

				window.top.postMessage(
					JSON.stringify({
						type: 'callback',
						callback: 'reauthenticate',
						target: '.leftandmain__login-dialog',
						data: {
							'SecurityID': securityID,
							'TempID': memberToken
						}
					}),
					origin
				);
			});
		</script>
	</body>

</html>
