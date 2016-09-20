<div class="cms-menu cms-panel cms-panel-layout west" id="cms-menu" data-layout-type="border">
	<div class="cms-logo-header north">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuLogo %>
		<% include SilverStripe\\Admin\\LeftAndMain_MenuStatus %>
	</div>

	<div class="panel panel--scrollable panel--triple-toolbar cms-panel-content">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuList %>
	</div>

	<div class="toolbar toolbar--south cms-panel-toggle">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuToggle %>
	</div>
</div>
