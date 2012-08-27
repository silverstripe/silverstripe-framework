<% if ShowPagination %>
	<% if TotalCount %>
	<div class="PageControls">
		<% if FirstLink %><a class="First" href="$FirstLink" title="<% _t('TableListField_PageControls.ss.VIEWFIRST', 'View first') %> $PageSize"><img src="$ModulePath(framework)/images/pagination/record-first.png" alt="<% _t('TableListField_PageControls.ss.VIEWFIRST', 'View first') %> $PageSize" /></a>
		<% else %><span class="First"><img src="$ModulePath(framework)/images/pagination/record-first-g.png" alt="<% _t('TableListField_PageControls.ss.VIEWFIRST', 'View first') %> $PageSize" /></span><% end_if %>
		<% if PrevLink %><a class="Prev" href="$PrevLink" title="<% _t('TableListField_PageControls.ss.VIEWPREVIOUS', 'View previous') %> $PageSize"><img src="$ModulePath(framework)/images/pagination/record-prev.png" alt="<% _t('TableListField_PageControls.ss.VIEWPREVIOUS', 'View previous') %> $PageSize" /></a>
		<% else %><img class="Prev" src="$ModulePath(framework)/images/pagination/record-prev-g.png" alt="<% _t('TableListField_PageControls.ss.VIEWPREVIOUS', 'View previous') %> $PageSize" /><% end_if %>
		<span class="Count">
			<% _t('DISPLAYING', 'Displaying') %> $FirstItem <% _t('TableListField_PageControls.ss.TO', 'to') %> $LastItem <% _t('TableListField_PageControls.ss.OF', 'of') %> $TotalCount
		</span>
		<% if NextLink %><a class="Next" href="$NextLink" title="<% _t('TableListField_PageControls.ss.VIEWNEXT', 'View next') %> $PageSize"><img src="$ModulePath(framework)/images/pagination/record-next.png" alt="<% _t('TableListField_PageControls.ss.VIEWNEXT', 'View next') %> $PageSize" /></a>
		<% else %><img class="Next" src="$ModulePath(framework)/images/pagination/record-next-g.png" alt="<% _t('TableListField_PageControls.ss.VIEWNEXT', 'View next') %> $PageSize" /><% end_if %>
		<% if LastLink %><a class="Last" href="$LastLink" title="<% _t('TableListField_PageControls.ss.VIEWLAST', 'View last') %> $PageSize"><img src="$ModulePath(framework)/images/pagination/record-last.png" alt="<% _t('TableListField_PageControls.ss.VIEWLAST', 'View last') %> $PageSize" /></a>
		<% else %><span class="Last"><img src="$ModulePath(framework)/images/pagination/record-last-g.png" alt="<% _t('TableListField_PageControls.ss.VIEWLAST', 'View last') %> $PageSize" /></span><% end_if %>
		
	</div>
	<% end_if %>
<% end_if %>
