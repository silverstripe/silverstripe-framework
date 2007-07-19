<% if ShowPagination %>
	<div class="PageControls">
		<% if LastLink %><a class="Last" href="$LastLink" title="View last $PageSize members"><img src="cms/images/pagination/record-last.png" alt="View last $PageSize members" /></a>
		<% else %><span class="Last"><img src="cms/images/pagination/record-last-g.png" alt="View last $PageSize members" /></span><% end_if %>
		<% if FirstLink %><a class="First" href="$FirstLink" title="View first $PageSize members"><img src="cms/images/pagination/record-first.png" alt="View first $PageSize members" /></a>
		<% else %><span class="First"><img  src="cms/images/pagination/record-first-g.png" alt="View first $PageSize members" /></span><% end_if %>
		<% if PrevLink %><a class="Prev" href="$PrevLink" title="View previous $PageSize members"><img src="cms/images/pagination/record-prev.png" alt="View previous $PageSize members" /></a>
		<% else %><img class="Prev" src="cms/images/pagination/record-prev-g.png" alt="View previous $PageSize members" /><% end_if %>
		<span class="Count">
			Displaying $FirstItem to $LastItem of $TotalCount
		</span>
		<% if NextLink %><a class="Next" href="$NextLink" title="View next $PageSize members"><img src="cms/images/pagination/record-next.png" alt="View next $PageSize members" /></a>
		<% else %><img class="Next" src="cms/images/pagination/record-next-g.png" alt="View next $PageSize members" /><% end_if %>
		<% if LastLink %><a class="Last" href="$LastLink" title="View last $PageSize members"><img src="cms/images/pagination/record-last.png" alt="View last $PageSize members" /></a>
		<% else %><span class="Last"><img src="cms/images/pagination/record-last-g.png" alt="View last $PageSize members" /></span><% end_if %>
	</div>
<% end_if %>