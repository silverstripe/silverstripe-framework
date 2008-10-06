<div class="typography">
		<div id="Content">
			
		<h2>$Title</h2>
	
		<% if SearchForm %>
		<h3><% _t('SEARCH','Search') %></h3>
		$SearchForm
		<% end_if %>
	
		<% if Results %>
		<ul class="records">
		<% control Results %>
			<li>
				<% if Top.canDetailView %>
					<a href="{$Top.Link}/record/$ID/view">$Title</a>
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
			<% if Results.NotLastPage %>
				<a class="next" href="$Results.NextLink"><% _t('NEXT','Next') %></a>
			<% end_if %>
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
			</div>
		<% end_if %>

		</div>
</div>