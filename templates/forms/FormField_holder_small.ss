<% if $Title %>
<label
    class="form__fieldgroup-label"
    <% if $ID %>
        aria-describedby="<% if $RightTitle %>extra-label-$ID<% end_if %>"
        for="$ID"
    <% end_if %>
>$Title</label>
<% end_if %>

$Field

<% if $RightTitle %>
	<p class="form__field-extra-label" <% if $ID %>id="extra-label-$ID"<% end_if %>>$RightTitle</p>
<% end_if %>
