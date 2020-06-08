$Header.RAW
$Info.RAW

<div class="task">
    <div class="task__panel">
        <h2>Tasks</h2>
        <p>These tasks can be run immediately.</p>
        <% if $Tasks.Count > 0 %>
            <div class="task__list">
                <% loop $Tasks %>
                    <div class="task__item">
                        <div>
                            <h3>$Title</h3>
                            <p class="description">$Description</p>
                        </div>
                        <div>
                            <a href="{$TaskLink.ATT}" class="task__button task__button--warning">Run immediately</a>
                        </div>
                    </div>
                <% end_loop %>
            </div>
        <% end_if %>
    </div>
</div>

$Footer.RAW
