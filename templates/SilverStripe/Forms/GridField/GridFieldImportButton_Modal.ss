<div id="$ImportModalID.ATT" class="modal fade grid-field-import" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <% if $ImportModalTitle %>
                    <h2 class="modal-title">$ImportModalTitle</h2>
                <% end_if %>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <% if $ImportIframe %>
                    <iframe src="$ImportIframe.ATT" width="100%%" height="400px" frameBorder="0"></iframe>
                <% else_if $ImportForm %>
                    $ImportForm
                <% end_if %>
            </div>
        </div>
    </div>
</div>
