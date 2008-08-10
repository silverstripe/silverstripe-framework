			<div id="$Name" class="field $Type $extraClass">
				<% if Title %><label class="left" for="$id">$Title</label><% end_if %>
				<div class="middleColumn">
					$Field
				</div>
				<% if RightTitle %><label class="right" for="$id">$RightTitle</label><% end_if %>
				<% if Message %><span class="message $MessageType">$Message</span><% end_if %>
			</div>