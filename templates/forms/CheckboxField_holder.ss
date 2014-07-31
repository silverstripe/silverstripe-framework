<div id="$Name.ATT" class="field<% if extraClass %> $extraClass<% end_if %>">
	$Field
	<label class="right" for="$ID">$Title.XML</label>
	<% if $Message %><span class="message $MessageType">$Message.XML</span><% end_if %>
	<% if $Description %><span class="description">$Description.XML</span><% end_if %>
</div>
