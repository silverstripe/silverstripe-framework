<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<% base_tag %>
</head>
<body>
	<div class="right $PopupClasses">
		$DetailForm
		<img src="cms/images/network-save.gif" class="ajaxloader" style="display: none" />
	</div>
	<% if IsAddMode %>
	<% else %>
		<% if ShowPagination %>
		    <div id="Pagination">
		        <% if PopupPrevLink %>
    		        <div id="Pagination_Previous">
    		            <a href="$PopupPrevLink"><img src="cms/images/pagination/previousArrow.png" /></a>
                        <a href="$PopupPrevLink"><div>Previous</div></a>
    		        </div>
                <% end_if %>
                <% if TotalCount == 1 %>
                <% else %>
                    <% control pagination %>
                        <% if active %>
                            <a href="$link">$number</a>
                        <% else %>    
                            <span>$number</span>
                        <% end_if %>
                    <% end_control %>
                <% end_if %> 
		        <% if PopupNextLink %>
    		        <div id="Pagination_Next">
    		            <a href="$PopupNextLink"><img src="cms/images/pagination/nextArrow.png" /></a>
                        <a href="$PopupNextLink"><div>Next</div></a>		                
    		        </div>
    		    <% end_if %>
		<% end_if %>
	<% end_if %>
	<script type="text/javascript">
       divQ = $('Pagination').getElementsByTagName('div').length;
       aQ = $('Pagination').getElementsByTagName('a').length - divQ + 1;
       $('Pagination').style.width = aQ * 15 + 130 +  "px";       
	</script>
</body>
</html>