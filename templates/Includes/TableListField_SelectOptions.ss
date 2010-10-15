	<% if SelectOptions %>
		<ul class="selectOptions">
			<li><% _t('TableListField.SELECT', 'Select:') %></li>
		<% control SelectOptions %>
			<li><a rel="$Key" href="#" title="$Key">$Value</a></li>
		<% end_control %>
		</ul>
	<% end_if %>