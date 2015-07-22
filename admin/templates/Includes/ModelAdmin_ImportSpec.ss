<div class="importSpec" id="SpecFor{$ModelName}">
	<a href="#SpecDetailsFor{$ModelName}" class="detailsLink"><%t ModelAdmin_ImportSpec_ss.IMPORTSPECLINK 'Show Specification for %s' s=$ModelName %></a>
	<div class="details" id="SpecDetailsFor{$ModelName}">
	<h4><%t ModelAdmin_ImportSpec_ss.IMPORTSPECTITLE 'Specification for %s' s=$ModelName %></h4>
		<h5><%t ModelAdmin_ImportSpec_ss.IMPORTSPECFIELDS 'Database columns' %></h5>
		<% loop $Fields %>
		<dl>
			<dt><em>$Name</em></dt>
			<dd>$Description</dd>
		</dl>
		<% end_loop %>

		<h5><%t ModelAdmin_ImportSpec_ss.IMPORTSPECRELATIONS 'Relations' %></h5>
		<% loop $Relations %>
		<dl>
			<dt><em>$Name</em></dt>
			<dd>$Description</dd>
		</dl>
		<% end_loop %>
	</div>
</div>
