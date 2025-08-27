<div class="view-controls">
    <% if not $IsFiltered %>
        <button type="button" name="showFilter" aria-controls="$HTMLID"
            class="btn btn-secondary icon-button btn--icon-large btn--no-text"
            title="<%t SilverStripe\ORM\Search\SearchContextForm.OpenFilter 'Open search and filter' %>"
            aria-label="<%t SilverStripe\ORM\Search\SearchContextForm.OpenFilter 'Open search and filter' %>"
        >
            <span class="font-icon-search" aria-hidden="true"></span>
        </button>
    <% end_if %>
</div>
