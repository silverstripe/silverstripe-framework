<div class="modal fade grid-field-import" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content"><div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>

        <div class="modal-body">
            <% if $URL %>
                <iframe src="$URL" id="MemberImportFormIframe" width="100%%" height="400px" frameBorder="0"></iframe>
            <% else %>
                $ImportForm
            <% end_if %>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary-outline font-icon-check-mark" data-dismiss="modal"><%t GridField.DONE 'Done' %></button>
        </div>
    </div>
</div>
