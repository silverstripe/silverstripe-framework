<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<% base_tag %>

		$MetaTags
	</head>
	<body>
		<h1><% if $Title %>$Title<% else %>Welcome to SilverStripe<% end_if %></h1>
		<% if $Content %>$Content<% else %>
		<p>To get started with the SilverStripe framework:</p>
		<ol>
			<li>Create a <code>Controller</code> subclass (<a href="http://doc.silverstripe.org/sapphire/en/topics/controller">doc.silverstripe.org/sapphire/en/topics/controller</a>)</li>
			<li>Setup the routes to your <code>Controller</code>.</li>
			<li>Create a template for your <code>Controller</code> (<a href="http://doc.silverstripe.org/sapphire/en/trunk/reference/templates">doc.silverstripe.org/sapphire/en/trunk/reference/templates</a>)</li>
		</ol>
		<% end_if %>
		<p><em>Generated with the default Controller.ss template.</em></p>
	</body>
</html>
