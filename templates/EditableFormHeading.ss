<div class="EditableFormHeading EditableFormField" id="$Name.Attr">
	<div class="FieldInfo">
		<img class="handle" src="sapphire/images/drag.gif" alt="Drag to rearrange order of fields" />
		<img class="icon" src="sapphire/images/fe_icons/heading.png" alt="Heading field" />
		$TitleField
		<a class="toggler" href="#" title="More options"><img src="cms/images/edit.gif" alt="More options" /></a>
		<a class="delete" href="#" title="Delete this field"><img src="cms/images/delete.gif" alt="Delete this field" /></a>
	</div>
	<div class="ExtraOptions" id="$Name.Attr-extraOptions">
		<% control ExtraOptions %>
		$FieldHolder
		<% end_control %>
	</div>
    <input type="hidden" name="$Name.Attr[CustomParameter]" value="$CustomParameter" />
    <input type="hidden" name="$Name.Attr[Type]" value="EditableFormHeading" />
	<input type="hidden" name="$Name.Attr[Sort]" value="-1" />
</div>
