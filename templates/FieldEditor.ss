<div class="FieldEditor <% if isReadonly %>readonly<% end_if %>" id="Fields" name="$Name.Attr">
	<ul class="TopMenu Menu">
		<li><% _t('ADD', 'Add') %>:</li>
		<li>
			<a href="#" title="<% _t('TEXTTITLE', 'Add text field') %>" id="TextField"><% _t('TEXT', 'Text') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('CHECKBOXTITLE', 'Add checkbox') %>" id="Checkbox"><% _t('CHECKBOX', 'Checkbox') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('DROPDOWNTITLE', 'Add dropdown') %>" id="Dropdown"><% _t('DROPDOWN', 'Dropdown') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('RADIOSETTITLE', 'Add radio button set') %>" id="RadioField"><% _t('RADIOSET', 'Radio') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('EMAILTITLE', 'Add email field') %>" id="EmailField"><% _t('EMAIL', 'Email') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('FORMHEADINGTITLE', 'Add form heading') %>" id="FormHeading"><% _t('FORMHEADING', 'Heading') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('DATETITLE', 'Add date heading') %>" id="DateField"><% _t('DATE', 'Date') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('FILETITLE', 'Add file upload field') %>" id="FileField"><% _t('FILE', 'File') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('CHECKBOXGROUPTITLE', 'Add checkbox group field') %>" id="CheckboxGroupField"><% _t('CHECKBOXGROUP', 'Checkboxes') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('MEMBERTITLE', 'Add member list field') %>" id="MemberListField"><% _t('MEMBER', 'Member List') %></a>
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
		<li><% _t('ADD', 'Add') %>:</li>
		<li>
			<a href="#" title="<% _t('TEXTTITLE', 'Add text field') %>" id="TextField"><% _t('TEXT', 'Text') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('CHECKBOXTITLE', 'Add checkbox') %>" id="Checkbox"><% _t('CHECKBOX', 'Checkbox') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('DROPDOWNTITLE', 'Add dropdown') %>" id="Dropdown"><% _t('DROPDOWN', 'Dropdown') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('RADIOSETTITLE', 'Add radio button set') %>" id="RadioField"><% _t('RADIOSET', 'Radio') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('EMAILTITLE', 'Add email field') %>" id="EmailField"><% _t('EMAIL', 'Email') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('FORMHEADINGTITLE', 'Add form heading') %>" id="FormHeading"><% _t('FORMHEADING', 'Heading') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('DATETITLE', 'Add date heading') %>" id="DateField"><% _t('DATE', 'Date') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('FILETITLE', 'Add file upload field') %>" id="FileField"><% _t('FILE', 'File') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('CHECKBOXGROUPTITLE', 'Add checkbox group field') %>" id="CheckboxGroupField"><% _t('CHECKBOXGROUP', 'Checkboxes') %></a>
		</li>
		<li>
			<a href="#" title="<% _t('MEMBERTITLE', 'Add member list field') %>" id="MemberListField"><% _t('MEMBER', 'Member List') %></a>
		</li>
	</ul>
	<div class="FormOptions">
		<% control FormOptions %>
			$FieldHolder
		<% end_control %>
	</div></div>