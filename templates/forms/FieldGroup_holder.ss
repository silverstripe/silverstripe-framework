<div <% if $Name %>id="$Name"<% end_if %> class="field <% if $extraClass %>$extraClass<% end_if %>">
	<% if $Title %><label class="left">$Title</label><% end_if %>

	<div class="middleColumn fieldgroup<% if $Zebra %> fieldgroup-$Zebra<% end_if %>">
		<% loop $FieldList %>
			<div class="fieldgroup-field $FirstLast $EvenOdd">
				$SmallFieldHolder
			</div>
		<% end_loop %>
	</div>
	<% if $RightTitle %><label class="right">$RightTitle</label><% end_if %>
	<% if $Message %><div class="alert $MessageType" role="alert">$Message</div><% end_if %>
	<% if $Description %><p class="description">$Description</p><% end_if %>
</div>
