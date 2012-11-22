<div class="cms-preview-states">
    <input type="checkbox" name="cms-preview" class="hide cms-preview" id="cms-preview-state" checked>
    <label for="cms-preview-state">
	    <span class="switch-options">
	    	<% loop Items %>
	    	<a href="$Link" class="$FirstLast <% if isActive %> active<% end_if %>">
	    		$Title		
			</a>
			<% end_loop %>
	    </span>
	    <span class="switch"></span>
    </label>
</div> 
