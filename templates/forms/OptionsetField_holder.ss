<div id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %>">
  	<div class="row form-group">
		<% if $Title %><label class="col-sm-2 form-control-label">$Title</label><% end_if %>
		<div class="col-sm-10<% if not $Title %> col-sm-push-2<% end_if %>">
			$Field
		</div>
	</div>

  	<!-- TODO: refactor so it renders the below using a method, instead of template conditional -->
	<% if $RightTitle || $Message || $Description %>
		<% if $RightTitle %><p class="text-muted">$RightTitle</p><% end_if %>

		<!-- TODO: use Bootstrap's alert classes in $MessageType -->
		<% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>

		<% if $Description %><p class="description">$Description</p><% end_if %>
	<% end_if %>
</div>
