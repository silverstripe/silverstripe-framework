<p><% _t('HELLO', 'Hi') %> $FirstName,</p>

<p>
	<% _t('CHANGEPASSWORDTEXT1', 'You changed your password for', 'for a url') %> $AbsoluteBaseURL.<br />
	<% _t('CHANGEPASSWORDTEXT2', 'You can now use the following credentials to log in:') %>
</p>

<p>
	<% _t('EMAIL', 'Email') %>: $Email<br />
	<% _t('PASSWORD', 'Password') %>: $CleartextPassword
</p>