	<% if SelectOptions %>
		<ul class="selectOptions">
			<li><% _t('TableListField.SELECT', 'Select:') %></li>
		<% loop SelectOptions %>
			<li><a rel="$Key" href="#" title="$Key">$Value</a></li>
		<% end_loop %>
		</ul>
	<% end_if %>
