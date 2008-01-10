<div class="EditableRadioField EditableMultiOptionFormField EditableFormField" id="$Name.Attr">
	<div class="FieldInfo">
		<% if isReadonly %>
		<img class="handle" src="sapphire/images/drag_readonly.gif" alt="<% _t('LOCKED', 'These fields cannot be modified') %>" />
		<% else %>
		<img class="handle" src="sapphire/images/drag.gif" alt="<% _t('DRAG', 'Drag to rearrange order of fields') %>" />
		<% end_if %>
		<img class="handle" src="sapphire/images/fe_icons/radio.png" alt="<% _t('SET', 'Radio button set') %>" title="<% _t('SET', 'Radio button set') %>" />
		$TitleField
		<input type="hidden" name="hiddenDefaultOption" value="$DefaultOption" />
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
	<div class="hidden">
		$TemplateOption
	</div>
	<div class="ExtraOptions" id="$Name.Attr-extraOptions">
		<div class="EditableDropdownBox FieldDefault">
			<ul class="EditableDropdownOptions" id="$Name.Attr-list">
				<% if isReadonly %>
					<% control Options %>
						$ReadonlyOption
					<% end_control %>			
				<% else %>
				<% control Options %>
					$Option
				<% end_control %>	
				<li class="AddDropdownOption">
					<input class="text" type="text" name="$Name.Attr[NewOption]" value="" />
					<a href="#" title="<% _t('ADD', 'Add option to field') %>"><img src="cms/images/add.gif" alt="<% _t('ADD', 'Add new option') %>" /></a>
				</li>
				<% end_if %>
			</ul>
		</div>
		<% control ExtraOptions %>
			$FieldHolder
		<% end_control %>
	</div>
	<input type="hidden" name="$Name.Attr[Deleted]" value="" />
  <input type="hidden" name="$Name.Attr[CustomParameter]" value="$CustomParameter" />
  <input type="hidden" name="$Name.Attr[Type]" value="EditableRadioField" />
	<input type="hidden" name="$Name.Attr[Sort]" value="-1" />
</div>