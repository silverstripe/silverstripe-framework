<ul>
	<% loop $Forms %>
	<li>
		<a href="#{$FormName}_Tab">$AuthenticatorName</a>
	</li>
	<% end_loop %>
</ul>
<% loop $Forms %>
<div class="form-tab" id="{$FormName}_Tab">
	<h3>$AuthenticatorName</h3>
	$forTemplate
</div>
<% end_loop %>
