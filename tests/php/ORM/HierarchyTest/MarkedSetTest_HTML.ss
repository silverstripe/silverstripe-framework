<% if $children || $limited %>
<ul>
    <% if $limited %>
        <li data-id="{$node.ID}"><span class="exceeded">Exceeded!</span></li>
    <% else_if $children %>
        <% loop $children %><li data-id="{$node.ID}" class="$markingClasses">$node.Title.XML $SubTree</li><% end_loop %>
    <% end_if %>
</ul>
<% end_if %>
