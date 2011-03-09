<% if ShowPagination %>
	<% if TotalCount %>
	<div class="PageControls">
		<% if FirstLink %><a class="First" href="$FirstLink" title="<% _t('VIEWFIRST', 'View first') %> $PageSize"><img src="cms/images/pagination/record-first.png" alt="<% _t('VIEWFIRST', 'View first') %> $PageSize" /></a>
		<% else %><span class="First"><img  src="cms/images/pagination/record-first-g.png" alt="<% _t('VIEWFIRST', 'View first') %> $PageSize" /></span><% end_if %>
		<% if PrevLink %><a class="Prev" href="$PrevLink" title="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize"><img src="cms/images/pagination/record-prev.png" alt="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize" /></a>
		<% else %><img class="Prev" src="cms/images/pagination/record-prev-g.png" alt="<% _t('VIEWPREVIOUS', 'View previous') %> $PageSize" /><% end_if %>
		<span class="Count">
			<% _t('DISPLAYING', 'Displaying') %> $FirstItem <% _t('TO', 'to') %> $LastItem <% _t('OF', 'of') %> $TotalCount
		</span>
		<% if NextLink %><a class="Next" href="$NextLink" title="<% _t('VIEWNEXT', 'View next') %> $PageSize"><img src="cms/images/pagination/record-next.png" alt="<% _t('VIEWNEXT', 'View next') %> $PageSize" /></a>
		<% else %><img class="Next" src="cms/images/pagination/record-next-g.png" alt="<% _t('VIEWNEXT', 'View next') %> $PageSize" /><% end_if %>
		<% if LastLink %><a class="Last" href="$LastLink" title="<% _t('VIEWLAST', 'View last') %> $PageSize"><img src="cms/images/pagination/record-last.png" alt="<% _t('VIEWLAST', 'View last') %> $PageSize" /></a>
		<% else %><span class="Last"><img src="cms/images/pagination/record-last-g.png" alt="<% _t('VIEWLAST', 'View last') %> $PageSize" /></span><% end_if %>
		
	</div>
	<% end_if %>
<% end_if %>