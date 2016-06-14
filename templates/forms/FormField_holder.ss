<div id="$HolderID" class="field<% if $extraClass %> $extraClass<% end_if %>">

  	<div class="row form-group">
		<% if $Title %><label class="col-sm-2 form-control-label" for="$ID">$Title</label><% end_if %>
		<div class="col-sm-10<% if not $Title %> col-sm-push-2<% end_if %>">
			<%-- TODO: add `.form-control` to `<input ...>` --%>
			$Field
		</div>
	</div>

	<%-- TODO: render the below with a method, instead of template conditional --%>
	<% if $RightTitle || $Message || $Description %>
		<div class="row">
			<div class="col-sm-10 col-sm-push-2">
				<% if $RightTitle %><p class="text-muted">$RightTitle</p><% end_if %>

				<%-- TODO: change $MessageType to match Bootstrap's alert types, e.g. alert-info, alert-danger etc --%>
				<% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>

				<% if $Description %><p class="description">$Description</p><% end_if %>
			</div>
		</div>
	<% end_if %>

</div>
