<div
    <% loop $Attributes %> {$Name}="{$Value}"<% end_loop %>
>
    {$Content}
    <% if $Arguments.caption %>
        <p class="caption">{$Arguments.caption}</p>
    <% end_if %>
</div>
