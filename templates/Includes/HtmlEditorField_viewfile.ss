<div class="ss-htmleditorfield-file $appCategory" data-id="$File.ID" data-url="$URL">
	<div class="overview">
		<span class="thumbnail">$Preview</span>
		<span class="title">$Name</span>
		<a href="#" class="action-delete ui-state-default">
			<span class="ui-button-icon-primary ui-icon btn-icon-cross-circle"></span>
		</a>
	</div>
	<div class="details">
		<fieldset>
			<% loop Fields %>
				$FieldHolder
			<% end_loop %>
		</fieldset>
	</div>
</div>
