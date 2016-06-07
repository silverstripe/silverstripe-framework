<div id="$HolderID" class="form-group field<% if $extraClass %> $extraClass<% end_if %>">

	<% if $Title %><label class="form__field-label" aria-describedby="<% if $Message %>message-$ID <% else_if $RightTitle %>extra-label-$ID <% else_if $Description %>discribes-$ID <% end_if %>" for="$ID">$Title</label><% end_if %>
    <div class="form__field-holder <% if not $Title %> col-sm-push-2<% end_if %>">
        $Field

        <%-- TODO: render the below with a method, instead of template conditional --%>
        <% if $Message || $Description %>

            <%-- TODO: change $MessageType to match Bootstrap's alert types, e.g. alert-info, alert-danger etc --%>
            <% if $Message %><p class="alert $MessageType" role="alert" id="massage-$ID">$Message</p><% end_if %>

            <% if $Description %><p class="form__field-description" id="discribes-$ID">$Description</p><% end_if %>
        <% end_if %>
    </div>
    <% if $RightTitle %><p class="form__field-extra-label" id="extra-label-$ID">$RightTitle</p><% end_if %>
</div>
