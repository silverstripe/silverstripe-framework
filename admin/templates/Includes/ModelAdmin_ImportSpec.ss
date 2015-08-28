<div class="importSpec" id="SpecFor{$ClassName}">
	<a href="#SpecDetailsFor{$ClassName}" class="detailsLink"><% sprintf(_t('ModelAdmin_ImportSpec_ss.IMPORTSPECLINK', 'Show Specification for %s'),$ModelName) %></a>
	<div class="details" id="SpecDetailsFor{$ClassName}">
	<h4><% sprintf(_t('ModelAdmin_ImportSpec_ss.IMPORTSPECTITLE', 'Specification for %s'),$ModelName) %></h4>
		<h5><% _t('ModelAdmin_ImportSpec_ss.IMPORTSPECFIELDS', 'Database columns') %></h5>
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
