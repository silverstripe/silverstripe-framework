<div id="$Name" class="form-group field<% if $extraClass %> $extraClass<% end_if %>">

    <% if $Title %>
        <label
            class="form__field-label"
            <% if $Message || $RightTitle || $Description %>
                aria-describedby="<% if $Message %>message-$ID <% else_if $RightTitle %>extra-label-$ID <% else_if $Description %>describes-$ID <% end_if %>"
            <% end_if %>
        >
            $Title
        </label>
    <% end_if %>

    <div class="form__field-holder <% if not $Title %> form__field-holder--no-label<% end_if %>">
		$Field

        <%-- TODO: refactor so it renders the below using a method, instead of template conditional --%>
    	<% if $RightTitle || $Message || $Description %>
    		<% if $RightTitle %><p class="text-muted">$RightTitle</p><% end_if %>

    		<%-- TODO: use Bootstraps alert classes in $MessageType --%>
    		<% if $Message %><div class="alert $MessageType" role="alert" id="message-$ID">$Message</div><% end_if %>

    		<% if $Description %><p class="description" id="describes-$ID">$Description</p><% end_if %>
    	<% end_if %>
	</div>
    <% if $RightTitle %><p class="form__field-extra-label" id="extra-label-$ID">$RightTitle</p><% end_if %>
</div>
