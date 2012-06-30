<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<% base_tag %>

		$MetaTags

		<% require css('framework/css/debug.css') %>
	</head>
	<body>
		<div class="info">
			<h1><% if $Title %>$Title<% else %>Welcome to SilverStripe<% end_if %></h1>
			<h3>Generated with the default Controller.ss template</h3>
		</div>

		<div class="options">
			<% if $Content %>$Content<% else %>
			<h3>Getting Started</h3>

			<p>To get started with the SilverStripe framework:</p>
			<ol>
				<li>Create a <code>Controller</code> subclass (<a href="http://doc.silverstripe.org/sapphire/en/topics/controller">doc.silverstripe.org/sapphire/en/topics/controller</a>)</li>
				<li>Setup the routes.yml f to your <code>Controller</code> (<a href="http://doc.silverstripe.org/framework/en/reference/director#routing">doc.silverstripe.org/framework/en/reference/director#routing</a>).</li>
				<li>Create a template for your <code>Controller</code> (<a href="http://doc.silverstripe.org/sapphire/en/reference/templates">doc.silverstripe.org/sapphire/en/reference/templates</a>)</li>
			</ol>
			<% end_if %>

			<h3>Community resources</h3>

			<ul>
				<li>
					<p><a href="http://silverstripe.org/forum">silverstripe.org/forum</a> Discussion forums for the development community.</p>
				</li>
				<li><p><a href="http://silverstripe.org/irc">silverstripe.org/irc</a> IRC channel for realtime support and discussions.</p></li>

				<li><p><a href="http://doc.silverstripe.org">doc.silverstripe.org</a> Searchable developer documentation, how-tos, tutorials, and reference.</p></li>

				<li><p><a href="http://api.silverstripe.org">api.silverstripe.org</a> API documentation for PHP classes, methods and properties.</p></li>
			<ul>
		</div>
	</body>
</html>
