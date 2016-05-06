<div id="$HolderID" class="field<% if $extraClass %> $extraClass<% end_if %>">
	<div class="row form-group">
		<div class="col-sm-10 col-sm-push-2 checkbox">
			<label>
				$Field
				$Title
			</label>
		</div>
	</div>

	<% if $Message || $Description %>
		<div class="row">
			<div class="col-sm-10 col-sm-push-2">
				<% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>
				<% if $Description %><p class="description">$Description</p><% end_if %>
			</div>
		</div>
	<% end_if %>
</div>
