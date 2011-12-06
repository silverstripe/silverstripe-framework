<% require css(sapphire/css/GridFieldPaginator.css) %>

<% if Pages %>
<div class="ss-gridfield-pagination">
	<% if FirstPageState %> 
	<button class="ss-gridfield-pagination-button" type="submit" name="$FirstPageState.attrName" value="$FirstPageState.attrValue">First</button>
	<% end_if %> 
	
	<% if PreviousPageState %>
	<button class="ss-gridfield-pagination-button" type="submit" name="$PreviousPageState.attrName" value="$PreviousPageState.attrValue">Previous page</button>
	<% end_if %> 
	
	<% control Pages %>
		<% if Current %>
			$PageNumber
		<% else %>
		<button class="ss-gridfield-pagination-button" type="submit" name="$PageState.attrName" value="$PageState.attrValue">$PageNumber</button>
		<% end_if %>
	<% end_control%>
	
	<% if NextPageState %>
	<button class="ss-gridfield-pagination-button" type="submit" name="$NextPageState.attrName" value="$NextPageState.attrValue">Next Page</button>
	<% end_if %> 
	
	<% if LastPageState %>
	<button class="ss-gridfield-pagination-button" type="submit" name="$LastPageState.attrName" value="$LastPageState.attrValue">Last</button>
	<% end_if %> 
</div>
<% end_if %> 