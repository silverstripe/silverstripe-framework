<% if $CountryField %>
	<span class="phonenumber-field__deco">+</span> $CountryField.Field
<% end_if %>
<% if $AreaField %>
	<span class="phonenumber-field__deco">(</span> $AreaField.Field <span class="phonenumber-field__deco">)</span>
<% end_if %>
$NumberField.Field
<% if $ExtensionField %>
	<span class="phonenumber-field__deco">ext</span> $ExtensionField.Field
<% end_if %>
