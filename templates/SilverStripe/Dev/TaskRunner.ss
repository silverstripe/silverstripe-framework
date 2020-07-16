$Header.RAW
$Info.RAW

<div class="task">
    <div class="task__panel">
        <% if $Tasks.Count > 0 %>
            <div class="task__list">
                <% loop $Tasks %>
                    <div class="task__item">
                        <div>
                            <h3 class="task__title">$Title</h3>
                            <p class="task__description">$Description</p>
                        </div>
                        <div>
                            <a href="{$TaskLink.ATT}" class="task__button">Run task</a>
                        </div>
                    </div>
                <% end_loop %>
            </div>
        <% end_if %>
    </div>
</div>

$Footer.RAW
