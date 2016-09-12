<% if $FailTest %>
	<% require themedCss(forms/RequirementsTest_a) %>
	<% require themedJavascript(forms/RequirementsTest_b) %>
	<% require themedJavascript(forms/RequirementsTest_c) %>
<% else %>
	<% require themedJavascript(forms/RequirementsTest_a) %>
	<% require themedCss(forms/RequirementsTest_b) %>
	<% require themedCss(forms/RequirementsTest_c) %>
<% end_if %>
