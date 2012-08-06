<p><% _t('ChangePasswordEmail.ss.HELLO', 'Hi') %> $FirstName,</p>

<p>
	<% _t('ChangePasswordEmail.ss.CHANGEPASSWORDTEXT1', 'You changed your password for', 'for a url') %> $AbsoluteBaseURL.<br />
	<% _t('ChangePasswordEmail.ss.CHANGEPASSWORDTEXT2', 'You can now use the following credentials to log in:') %>
</p>

<p>
	<% _t('ChangePasswordEmail.ss.EMAIL', 'Email') %>: $Email<br />
	<% _t('ChangePasswordEmail.ss.PASSWORD', 'Password') %>: $CleartextPassword
</p>