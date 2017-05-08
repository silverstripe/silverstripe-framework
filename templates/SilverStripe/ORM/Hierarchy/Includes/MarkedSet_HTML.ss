<% if $children || $limited %>
<ul>
    <% if $limited %>
        <li><%t SilverStripe\\ORM\\Hierarchy\\Hierarchy.LIMITED_TITLE 'Too many children ({count})' count=$count %></li>
    <% else_if $children %>
        <% loop $children %><li>$node.Title.XML $SubTree</li><% end_loop %>
    <% end_if %>
</ul>
<% end_if %>
