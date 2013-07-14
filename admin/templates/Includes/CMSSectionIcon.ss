<% if $ToplevelController %>
	<span class="section-icon icon icon-16 icon-{$ToplevelController.MenuCurrentItem.Code.LowerCase}"></span>
<% else_if $Controller %>
	<span class="section-icon icon icon-16 icon-{$Controller.MenuCurrentItem.Code.LowerCase}"></span>
<% else %>
	<span class="section-icon icon icon-16 icon-{$MenuCurrentItem.Code.LowerCase}"></span>
<% end_if %>
