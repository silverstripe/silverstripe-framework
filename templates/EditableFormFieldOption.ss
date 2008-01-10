<li class="EditableFormFieldOption" id="$ID">
	<% if isReadonly %>
	<img class="handle" src="sapphire/images/drag_readonly.gif" alt="<% _t('LOCKED', 'These fields cannot be modified') %>" />
	$DefaultSelect
	<input class="text" type="text" name="$Name.Attr[Title]" value="$Title.Attr" disabled="disabled" />
	<input type="hidden" name="$Name.Attr[Sort]" value="$ID" />
	<img src="cms/images/locked.gif" alt="<% _t('LOCKED', 'These fields cannot be modified') %>" />	
	<% else %>
	<img class="handle" src="sapphire/images/drag.gif" alt="<% _t('DRAG', 'Drag to rearrange order of fields') %>" />
	$DefaultSelect
	<input class="text" type="text" name="$Name.Attr[Title]" value="$Title.Attr" />
	<input type="hidden" name="$Name.Attr[Sort]" value="$ID" />
	<a href="#"><img src="cms/images/delete.gif" alt="<% _t('DELETE', 'Remove this option') %>" /></a>
	<% end_if %>
</li>