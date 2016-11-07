<div class="fill-height cms-menu cms-panel cms-panel-layout" id="cms-menu" data-layout-type="border">
	<div class="cms-logo-header">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuLogo %>
		<% include SilverStripe\\Admin\\LeftAndMain_MenuStatus %>
	</div>

	<div class="flexbox-area-grow panel--scrollable panel--triple-toolbar cms-panel-content">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuList %>
	</div>

	<div class="toolbar toolbar--south cms-panel-toggle">
		<% include SilverStripe\\Admin\\LeftAndMain_MenuToggle %>
	</div>
</div>
