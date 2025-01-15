<div class="fieldholder-small field htmleditor">
    <% if $Title %><label class="form-label fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$Title</label><% end_if %>
    $Field
    <% if $RightTitle %><label class="form-label right fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$RightTitle</label><% end_if %>
</div>
