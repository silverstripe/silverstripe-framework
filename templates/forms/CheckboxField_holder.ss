<div id="$HolderID" class="form-group field<% if extraClass %> $extraClass<% end_if %>">

    <div class="form__field-holder">
        <label>
            <!-- TODO: remove `.checkbox` class in the `$Field`'s `<input ...>` tag-->
            $Field
            $Title
        </label>

    	<!-- TODO: refactor so it renders the below using a method, instead of template conditional -->
        <% if $Message || $Description %>
            <% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>
            <% if $Description %><p class="form__field-description">$Description</p><% end_if %>
        <% end_if %>
    </div>
</div>
