<div <% if $Name %>id="$Name.ATT"<% end_if %> class="field <% if $extraClass %>$extraClass<% end_if %>">
	<% if $Title %><label class="left">$Title.XML</label><% end_if %>
	
	<div class="middleColumn fieldgroup<% if $Zebra %> fieldgroup-$Zebra<% end_if %>">
		<% loop $FieldList %>
			<div class="fieldgroup-field $FirstLast $EvenOdd">
				$SmallFieldHolder
			</div>
		<% end_loop %>
	</div>
	<% if $RightTitle %><label class="right">$RightTitle.XML</label><% end_if %>
	<% if $Message %><span class="message $MessageType.ATT">$Message.XML</span><% end_if %>
	<% if $Description %><span class="description">$Description.XML</span><% end_if %>
</div>
