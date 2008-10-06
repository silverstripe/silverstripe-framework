		<% if Results %>
		<h3><% _t('RESULTS','Results') %></h3>
		<ul class="records">
		<% control Results %>
			<li>
				<% if Top.canDetailView %>
					<a href="{$Top.Link}/$ID/view">$Title</a>
				<% else %>
					$Title
				<% end_if %>
			</li>
		<% end_control %>
		</ul>
		<% else %>
		<p class="message"><% _t('NORESULTSFOUND','No records found') %></p>
		<% end_if %>

		<% if Results.MoreThanOnePage %>
			<div id="PageNumbers">
			<% if Results.NotFirstPage %>
				<a class="prev" href="$Results.PrevLink"><% _t('PREV','Prev') %></a>
			<% end_if %>
			<span>
			<% control Results.Pages %>
				<% if CurrentBool %>
					$PageNum
				<% else %>
					<a href="$Link">$PageNum</a>
				<% end_if %>
			<% end_control %>
			</span>
			<% if Results.NotLastPage %>
				<a class="next" href="$Results.NextLink"><% _t('NEXT','Next') %></a>
			<% end_if %>
			</div>
		<% end_if %>