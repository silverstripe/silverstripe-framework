<!DOCTYPE html>
<html>
	<head>
		<% base_tag %>
		<title><%t SilverStripe\\Security\\CMSSecurity.LOGIN_TITLE 'Return to where you left off by logging back in' %></title>
	</head>
	<body class="cms cms-security fill-height">
        <% with $Form %>
            <% if $Message %>
                <div class="cms-security__container__error alert $AlertType">
                    <p id="{$FormName}_error">$Message</p>
                </div>
            <% end_if %>
        <% end_with %>
        <div class="cms-security__container container fill-height">
            <div class="row">
                <h1>
                    <span class="icon font-icon-back-in-time"></span>
                    <%t SilverStripe\\Security\\CMSSecurity.LOGIN_TITLE 'Return to where you left off by logging back in' %>
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
	</body>
</html>
