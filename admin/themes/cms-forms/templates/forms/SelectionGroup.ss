<ul class="list-unstyled selection-group">
	<% if $IsReadonly %>
		<% loop $FieldSet %>
			<% if $Selected %>
				<li class="selected selection-group__item" id="$HolderID">
					$RadioLabel
					<%-- Bypass composite item field and directly render child fields --%>
					<% if $FieldList %>
						<div class="selection-group selection-group__item__fieldlist" id="$ID">
							<% loop $FieldList %>
								$Fieldholder
							<% end_loop %>
						</div>
					<% end_if %>
				</li>
			<% end_if %>
		<% end_loop %>
	<% else %>
		<% loop $FieldSet %>
			<li class="selection-group__item<% if $Selected %> selected<% end_if %>" id="$HolderID">
				{$RadioButton}{$RadioLabel}
				<%-- Bypass composite item field and directly render child fields --%>
				<% if $FieldList %>
					<div class="selection-group selection-group__item__fieldlist" id="$ID">
						<% loop $FieldList %>
							$Fieldholder
						<% end_loop %>
					</div>
				<% end_if %>
			</li>
		<% end_loop %>
	<% end_if %>
</ul>
