<% if $FailTest %>
	<% require css(framework/tests/forms/RequirementsTest_a.css) %>
	<% require javascript(framework/tests/forms/RequirementsTest_b.js) %>
	<% require javascript(framework/tests/forms/RequirementsTest_c.js) %>
<% else %>
	<% require javascript(framework/tests/forms/RequirementsTest_a.js) %>
	<% require css(framework/tests/forms/RequirementsTest_b.css) %>
	<% require css(framework/tests/forms/RequirementsTest_c.css) %>
<% end_if %>
