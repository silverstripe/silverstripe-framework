<% if $SearchForm %>
    <div id="cms-content-tools-ModelAdmin" class="cms-content-filters">
        <% if $SearchForm %>
            <h3 class="cms-panel-header"><%t ModelAdmin_Tools_ss.FILTER 'Filter' %></h3>
            $SearchForm
        <% end_if %>
    </div>
<% end_if %>
