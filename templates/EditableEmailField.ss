<div class="EditableEmailField EditableFormField" id="$Name.Attr">
	<div class="FieldInfo">
		<img class="handle" src="sapphire/images/drag.gif" alt="Drag to rearrange order of fields" />
		<img class="icon" src="sapphire/images/fe_icons/text-email.png" alt="Email address field" />
		$TitleField
		<a class="toggler" href="#" title="More options"><img src="cms/images/edit.gif" alt="More options" /></a>
		<% if CanDelete %>
    <a class="delete" href="#" title="Delete this field"><img src="cms/images/delete.gif" alt="Delete this field" /></a>
	  <% else %>
    <img src="cms/images/locked.gif" alt="This field is required for this form and cannot be deleted" />
    <% end_if %>
  </div>
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
    <input type="hidden" name="$Name.Attr[Type]" value="EditableEmailField" />   
	<input type="hidden" name="$Name.Attr[Sort]" value="-1" />
</div>