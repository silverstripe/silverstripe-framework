			<div id="$Name" class="field $Type $extraClass">
				<% if Title %><label class="left" for="$id">$Title</label><% end_if %>
				<span class="middleColumn">
					$Field
				</span>
				<% if RightTitle %><label class="right" for="$id">$RightTitle</label><% end_if %>
				<% if Message %><span class="message $MessageType">$Message</span><% end_if %>
			</div>