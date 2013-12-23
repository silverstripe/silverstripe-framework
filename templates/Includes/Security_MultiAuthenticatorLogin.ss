<ul>
	<% loop Forms %>
	<li>
		<a href="#{$FormName}">$AuthenticatorName</a>
	</li>
	<% end_loop %>
</ul>
<% loop Forms %>
<div class="form-tab" id="{$FormName}">
	<h3>$AuthenticatorName</h3>
	$forTemplate
</div>
<% end_loop %>
