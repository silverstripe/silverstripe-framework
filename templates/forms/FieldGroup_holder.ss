<div <% if $Name %>id="$Name"<% end_if %> class="field $Type $extraClass">
	<% if $Title %><label class="left">$Title</label><% end_if %>
	
	<div class="middleColumn fieldgroup <% if Zebra %>fieldgroup-$Zebra<% end_if %>">
		<% loop $FieldList %>
			<div class="fieldgroup-field $FirstLast $EvenOdd">
				$SmallFieldHolder
			</div>
		<% end_loop %>
	</div>
	<% if $RightTitle %><label class="right">$RightTitle</label><% end_if %>
	<% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
</div>
