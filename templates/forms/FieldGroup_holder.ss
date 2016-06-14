<div <% if $Name %>id="$Name"<% end_if %> class="form-group field <% if $extraClass %>$extraClass<% end_if %>">
    <% if $Title %>
    <label
        class="form__field-label"
        <% if $Message || $RightTitle || $Description %>
            aria-describedby="<% if $Message %>message-$ID <% else_if $RightTitle %>extra-label-$ID <% else_if $Description %>describes-$ID <% end_if %>"
        <% end_if %>
        <% if $Name %>for="control-$Name"<% end_if %>
    >
        $Title
    </label>
    <% end_if %>

    <div <% if $Name %>id="control-$Name"<% end_if %> role="group" class="form__field-holder fieldgroup<% if $Zebra %> fieldgroup-$Zebra<% end_if %>">
        <% loop $FieldList %>
            <div class="fieldgroup-field $FirstLast $EvenOdd">
                $SmallFieldHolder
            </div>
        <% end_loop %>
    </div>
    <% if $RightTitle %><label class="right">$RightTitle</label><% end_if %>
    <% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
    <% if $Description %><span class="description">$Description</span><% end_if %>
</div>
