<div class="$ClassName EditableFormField" id="$Name.Attr">
	<div class="FieldInfo">
		<% if isReadonly %>
		<img class="handle" src="sapphire/images/drag_readonly.gif" alt="<% _t('LOCKED', 'These fields cannot be modified') %>" />
		<% else %>
		<img class="handle" src="sapphire/images/drag.gif" alt="<% _t('DRAG', 'Drag to rearrange order of fields') %>" />
		<% end_if %>
		<img class="icon" src="sapphire/images/fe_icons/{$ClassName.LowerCase}.png" alt="$ClassName" title="$singular_name" />
		$TitleField
		<a class="toggler" href="#" title="<% _t('MORE', 'More options') %>"><img src="cms/images/edit.gif" alt="<% _t('MORE', 'More options') %>" /></a>
		<% if isReadonly %>
		<img src="cms/images/locked.gif" alt="<% _t('LOCKED', 'These fields cannot be modified') %>" />
		<% else %>
		<% if CanDelete %>
    <a class="delete" href="#" title="<% _t('DELETE', 'Delete this field') %>"><img src="cms/images/delete.gif" alt="<% _t('DELETE', 'Delete this field') %>" /></a>
	  <% else %>
    <img src="cms/images/locked.gif" alt="<% _t('REQUIRED', 'This field is required for this form and cannot be deleted') %>" />
    <% end_if %>
    <% end_if %>
  </div>
  <% if Options %>
  <div class="hidden">
		<% control Options %>
			<% if isReadonly %>
				$ReadonlyOption
			<% else %>
				$Option
			<% end_if %>
		<% end_control %>
	</div>
  <% end_if %>
	<div class="ExtraOptions" id="$Name.Attr-extraOptions">
		<div class="FieldDefault">
			$DefaultField
		</div>
		<% control ExtraOptions %>
		$FieldHolder
		<% end_control %>
	</div>
  <input type="hidden" name="$Name.Attr[CanDelete]" value="$CanDelete" />
  <input type="hidden" name="$Name.Attr[CustomParameter]" value="$CustomParameter" />
  <input type="hidden" name="$Name.Attr[Type]" value="$ClassName" />   
	<input type="hidden" name="$Name.Attr[Sort]" value="-1" />
</div>