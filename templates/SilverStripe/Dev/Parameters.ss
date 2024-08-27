<dl class="params">
    <% loop $Parameters %>
        <div class="param">
            <dt class="param__name">$Name</dt>
            <dd class="param__description">$Description<% if $Default %> [default: $Default]<% end_if %></dd>
        </div>
    <% end_loop %>
</dl>
