<p><%t ChangePasswordEmail_ss.HELLO 'Hi' %> $FirstName,</p>

<p>
	<%t ChangePasswordEmail_ss.CHANGEPASSWORDTEXT1 'You changed your password for' is 'for a url' %> $AbsoluteBaseURL.<br />
	<%t ChangePasswordEmail_ss.CHANGEPASSWORDTEXT2 'You can now use the following credentials to log in:' %>
</p>

<p>
	<%t ChangePasswordEmail_ss.EMAIL 'Email' %>: $Email
	<% if $CleartextPassword %>
		<br />
		<%t ChangePasswordEmail_ss.PASSWORD 'Password' %>: $CleartextPassword
	<% end_if %>
</p>
