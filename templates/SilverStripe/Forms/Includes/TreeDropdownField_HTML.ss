<% if $depth == '0' && not $isSubTree %>
    <ul class="tree">
<% else_if $depth > 0 %>
    <% if $limited || $children %>
        <ul>
    <% end_if %>
<% end_if %>

<% if $limited %>
    <li><%t SilverStripe\\ORM\\Hierarchy.LIMITED_TITLE 'Too many children ({count})' count=$count %></li>
<% else_if $children %>
    <% loop $children %>
        <li id="selector-{$name}-{$id}" data-id="{$id}"
            class="class-{$node.ClassName} {$markingClasses} <% if $disabled %>disabled<% end_if %>"
        >
            <a rel="$node.ID">{$treetitle}</a>
            $SubTree
        </li>
    <% end_loop %>
<% end_if %>

<% if $depth == '0' && not $isSubTree %>
    </ul>
<% else_if $depth > 0 %>
    <% if $limited || $children %>
        </ul>
    <% end_if %>
<% end_if %>
