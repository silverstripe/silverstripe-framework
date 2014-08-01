<div id="$Name.ATT" class="field<% if $extraClass %> $extraClass<% end_if %>">
	<% if $Title %><label class="left">$Title.XML</label><% end_if %>
	<div class="middleColumn">
		$Field
	</div>
	<% if $RightTitle %><label class="right">$RightTitle.XML</label><% end_if %>
	<% if $Message %><span class="message $MessageType.ATT">$Message.XML</span><% end_if %>
	<% if $Description %><span class="description">$Description.XML</span><% end_if %>
</div>
