<div class="ss-htmleditorfield-file $appCategory" data-id="$File.ID" data-url="$URL">
	<div class="overview">
		<span class="thumbnail">$Preview</span>
		<span class="title">$Name</span>
		<a href="#" class="action-delete"><% _t('HtmlEditorField.DeleteItem', 'delete') %></a>
	</div>
	<div class="details">
		<fieldset>
			<% control Fields %>
				$FieldHolder
			<% end_control %>
		</fieldset>
	</div>
</div>