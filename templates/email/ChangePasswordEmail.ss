<p><% _t('ChangePasswordEmail_ss.HELLO', 'Hi') %> $FirstName,</p>

<p>
	<% _t('ChangePasswordEmail_ss.CHANGEPASSWORDTEXT1', 'You changed your password for', 'for a url') %> $AbsoluteBaseURL.<br />
	<% _t('ChangePasswordEmail_ss.CHANGEPASSWORDTEXT2', 'You can now use the following credentials to log in:') %>
</p>

<p>
	<% _t('ChangePasswordEmail_ss.EMAIL', 'Email') %>: $Email<br />
	<% _t('ChangePasswordEmail_ss.PASSWORD', 'Password') %>: $CleartextPassword
</p>