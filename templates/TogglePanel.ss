<h2 id="$id" style="cursor: pointer;" class="TogglePanelHeader$ClosedClass">
	<img id="{$id}_toggle_closed" src="sapphire/images/toggle-closed.gif" alt="+" title="Show" />
	<img id="{$id}_toggle_open" src="sapphire/images/toggle-open.gif" alt="-" style="display:none;" title="Hide" /> 
	$Title
</h2>
<div id="panel_$id" $ClosedStyle>
<% control FieldSet %>
$FieldHolder
<% end_control %>
</div>