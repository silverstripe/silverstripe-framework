					<tr id="record-$Parent.id-$ID"<% if HighlightClasses %> class="$HighlightClasses"<% end_if %>>
						<% if Markable %><td width="16">$MarkingCheckbox</td><% end_if %>
						<% control Fields %>
						<td class="field-$Title.HTMLATT">$Value</td>
						<% end_control %>
						<% if Can(delete) %>
							<td width="16"><a class="deletelink" href="$DeleteLink"><img src="cms/images/delete.gif" alt="<% _t('Form.DELETE') %>" /></a></td>
						<% end_if %>
					</tr>