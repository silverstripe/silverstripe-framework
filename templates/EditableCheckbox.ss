<div class="EditableCheckbox EditableFormField" id="$Name.Attr">
	<div class="FieldInfo">
		<% if isReadonly %>
		<img class="handle" src="sapphire/images/drag-readonly.gif" alt="<% _t('LOCKED', 'This field cannot be modified') %>" />
		<% else  %>
		<img class="handle" src="sapphire/images/drag.gif" alt="<% _t('DRAG', 'Drag to rearrange order of fields') %>" />
		<% end_if %>
		<img class="icon" src="sapphire/images/fe_icons/checkbox.png" alt="<% _t('CHECKBOX', 'Checkbox field') %>" />
		$TitleField
		<a class="toggler" href="#" title="<% _t('MORE', 'More options') %>"><img src="cms/images/edit.gif" alt="<% _t('MORE', 'More options') %>" /></a>
		<a class="delete" href="#" title="<% _t('DELETE', 'Delete this field') %>"><img src="cms/images/delete.gif" alt="<% _t('DELETE', 'Delete this field') %>" /></a>
	</div>
	<div class="ExtraOptions" id="$Name.Attr-extraOptions">
		<div class="FieldDefault">
			<label>
				$CheckboxField
			</label>
		</div>
		<% control ExtraOptions %>
		$FieldHolder
		<% end_control %>
	</div>
  <input type="hidden" name="$Name.Attr[CustomParameter]" value="$CustomParameter" />
  <input type="hidden" name="$Name.Attr[Type]" value="EditableCheckbox" />
	<input type="hidden" name="$Name.Attr[Sort]" value="-1" />
</div>