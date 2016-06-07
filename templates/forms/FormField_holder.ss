<div id="$HolderID" class="form-group field<% if $extraClass %> $extraClass<% end_if %>">

	<% if $Title %><label class="form__field-label" for="$ID">$Title</label><% end_if %>
    <div class="form__field-holder <% if not $Title %> col-sm-push-2<% end_if %>">
        $Field

        <%-- TODO: render the below with a method, instead of template conditional --%>
        <% if $RightTitle || $Message || $Description %>

            <% if $RightTitle %><p class="text-muted">$RightTitle</p><% end_if %>

            <%-- TODO: change $MessageType to match Bootstrap's alert types, e.g. alert-info, alert-danger etc --%>
            <% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>

            <% if $Description %><p class="description">$Description</p><% end_if %>
        <% end_if %>
    </div>
</div>
