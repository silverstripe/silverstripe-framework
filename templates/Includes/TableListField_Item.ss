<tr id="record-$Parent.id-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
	<% if Markable %><td width="16" class="$SelectOptionClasses">$MarkingCheckbox</td><% end_if %>
	<% loop Fields %>
		<td class="field-$Title.HTMLATT $FirstLast $Name">$Value</td>
	<% end_loop %>
	<% loop Actions %>
		<td width="16" class="action">
			<% if IsAllowed %>
			<a class="$Class" href="$Link"<% if TitleText %> title="$TitleText"<% end_if %>>
				<% if Icon %><img src="$Icon" alt="$Label" /><% else %>$Label<% end_if %>
			</a>
			<% else %>
				<span class="disabled">
					<% if IconDisabled %><img src="$IconDisabled" alt="$Label" /><% else %>$Label<% end_if %>
				</span>
			<% end_if %>
		</td>
	<% end_loop %>
</tr>
