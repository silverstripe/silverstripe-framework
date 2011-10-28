<% require css(sapphire/css/GridFieldPaginator.css) %>

<% if Pages %>
<div class="ss-gridfield-pagination">
	<% if FirstLink %> 
	<button class="ss-gridfield-pagination-button" type="submit" name="page" value="$FirstLink">First</button>
	<% end_if %> 
	
	<% if PreviousLink %>
	<button class="ss-gridfield-pagination-button" type="submit" name="page" value="$PreviousLink">Previous page</button>
	<% end_if %> 
	
	<% control Pages %>
		<% if Current %>
			$PageNumber
		<% else %>
		<button class="ss-gridfield-pagination-button" type="submit" name="page" value="$PageNumber">$PageNumber</button>
		<% end_if %>
	<% end_control%>
	
	<% if NextLink %>
	<button class="ss-gridfield-pagination-button" type="submit" name="page" value="$NextLink">Next Page</button>
	<% end_if %> 
	
	<% if LastLink %>
	<button class="ss-gridfield-pagination-button" type="submit" name="page" value="$LastLink">Last</button>
	<% end_if %> 
</div>
<% end_if %> 