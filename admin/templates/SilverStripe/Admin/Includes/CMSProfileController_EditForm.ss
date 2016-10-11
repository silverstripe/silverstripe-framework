<form $FormAttributes data-layout-type="border">

	<div class="panel panel--padded panel--scrollable flexbox-area-grow cms-content-fields">
		<% if $Message %>
		<p id="{$FormName}_error" class="message $MessageType">$Message</p>
		<% else %>
		<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
		<% end_if %>

		<fieldset>
			<% if $Legend %><legend>$Legend</legend><% end_if %>
			<% loop $Fields %>
				$FieldHolder
			<% end_loop %>
			<div class="clear"><!-- --></div>
		</fieldset>
	</div>

	<div class="toolbar toolbar--south cms-content-actions cms-content-controls south">
		<% if $Actions %>
		<div class="btn-toolbar">
			<% loop $Actions %>
				$Field
			<% end_loop %>
		</div>
		<% end_if %>
	</div>
</form>
