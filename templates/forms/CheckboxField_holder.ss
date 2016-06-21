<div id="$HolderID" class="form-group field<% if extraClass %> $extraClass<% end_if %>">

    <div class="form__field-holder">
        <label>
            $Field
            $Title
        </label>

        <% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>
        <% if $Description %><p class="form__field-description">$Description</p><% end_if %>
    </div>
</div>
