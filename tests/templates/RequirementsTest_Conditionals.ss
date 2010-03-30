<% if FailTest %>
	<% require css(sapphire/tests/forms/RequirementsTest_a.css) %>
	<% require javascript(sapphire/tests/forms/RequirementsTest_b.js) %>
	<% require javascript(sapphire/tests/forms/RequirementsTest_c.js) %>
<% else %>
	<% require javascript(sapphire/tests/forms/RequirementsTest_a.js) %>
	<% require css(sapphire/tests/forms/RequirementsTest_b.css) %>
	<% require css(sapphire/tests/forms/RequirementsTest_c.css) %>
<% end_if %>
