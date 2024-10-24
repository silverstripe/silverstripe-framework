<% if $FailTest %>
	<% require themedCss(RequirementsTest_a) %>
	<% require themedJavascript(RequirementsTest_b) %>
	<% require themedJavascript(RequirementsTest_c) %>
<% else %>
	<% require themedJavascript(RequirementsTest_a) %>
	<% require themedCss(RequirementsTest_b) %>
	<% require themedCss(RequirementsTest_c) %>
<% end_if %>
