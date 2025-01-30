<div class="view-controls">
    <% if not $IsFiltered %>
        <button type="button" name="showFilter" aria-controls="$HTMLID"
            class="btn btn-secondary icon-button font-icon-search btn--icon-large btn--no-text"
            title="<%t SilverStripe\ORM\Search\SearchContextForm.OpenFilter 'Open search and filter' %>"
            aria-label="<%t SilverStripe\ORM\Search\SearchContextForm.OpenFilter 'Open search and filter' %>"
        ></button>
    <% end_if %>
</div>
