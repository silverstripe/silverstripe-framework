<% if Results %>
	$Form
<% else %>
	<p><% sprintf(_t('ModelAdmin.NORESULTS', 'No results'), $ModelPluralName) %></p>
<% end_if %>