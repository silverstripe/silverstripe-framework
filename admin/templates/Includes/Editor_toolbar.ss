<% with $EditorToolbar %>
<div class="mceToolbarExternal" id="mce_editor_toolbar">
<table width="100%" border="0">
<tbody>
<tr>
<td>

<% loop $Buttons %>
	<% if $Type = button %>
	<a href="#$Command">
	<img width="20" height="20" class="mceButtonNormal" title="$Title" alt="$Title" src="$Icon" id="mce_editor_$IDSegment" />
	</a>
	<% else_if $Type = dropdown %>
	<select name="$Command" class="mceSelectList" id="mce_editor_$IDSegment">$Options</select>
	<% else_if $Type = separator %>
	<img width="1" height="15" class="mceSeparatorLine" src="{$MceRoot}themes/advanced/images/separator.gif" alt="|" />
	<% else_if $Type = break %>
	<br />
	<% end_if %>
<% end_loop %>

<br />
</td>
</tr>
</tbody>
</table>
</div>

<% end_with %>
