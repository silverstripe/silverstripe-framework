<div class="FieldEditor <% if isReadonly %>readonly<% end_if %>" id="Fields" name="$Name.Attr">
	<ul class="TopMenu Menu">
		<li>Add:</li>
		<li>
			<a href="#" title="Add text field" id="TextField">Text</a>
		</li>
		<li>
			<a href="#" title="Add checkbox" id="Checkbox">Checkbox</a>
		</li>
		<li>
			<a href="#" title="Add dropdown" id="Dropdown">Dropdown</a>
		</li>
		<li>
			<a href="#" title="Add radio button set" id="RadioField">Radio</a>
		</li>
		<li>
			<a href="#" title="Add email field" id="EmailField">Email</a>
		</li>
		<li>
			<a href="#" title="Add form heading" id="FormHeading">Heading</a>
		</li>
		<li>
			<a href="#" title="Add date heading" id="DateField">Date</a>
		</li>
		<li>
			<a href="#" title="Add file upload field" id="FileField">File</a>
		</li>
		<li>
			<a href="#" title="Add checkbox group field" id="CheckboxGroupField">Checkboxes</a>
		</li>
		<li>
			<a href="#" title="Add member list field" id="MemberListField">Member List</a>
		</li>
	</ul>
	<div class="FieldList" id="Fields_fields">
	<% control Fields %>
		<% if isReadonly %>
			$ReadonlyEditSegment	
		<% else %>
			$EditSegment
		<% end_if %>
	<% end_control %>
	</div>
	<ul class="BottomMenu Menu">
		<li>Add:</li>
		<li>
			<a href="#" title="Add text field" id="TextField">Text</a>
		</li>
		<li>
			<a href="#" title="Add checkbox" id="Checkbox">Checkbox</a>
		</li>
		<li>
			<a href="#" title="Add dropdown" id="Dropdown">Dropdown</a>
		</li>
		<li>
			<a href="#" title="Add radio button set" id="RadioField">Radio</a>
		</li>
		<li>
			<a href="#" title="Add email field" id="EmailField">Email</a>
		</li>
		<li>
			<a href="#" title="Add form heading" id="FormHeading">Heading</a>
		</li>
		<li>
			<a href="#" title="Add date heading" id="DateField">Date</a>
		</li>
		<li>
			<a href="#" title="Add file upload field" id="FileField">File</a>
		</li>
		<li>
			<a href="#" title="Add checkbox group field" id="CheckboxGroupField">Checkboxes</a>
		</li>
		<li>
			<a href="#" title="Add member list field" id="MemberListField">Member List</a>
		</li>
	</ul>
	<div class="FormOptions">
		<% control FormOptions %>
			$FieldHolder
		<% end_control %>
	</div></div>