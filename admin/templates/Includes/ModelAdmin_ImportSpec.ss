<div class="importSpec" id="SpecFor{$ModelName}">
	<a href="#SpecDetailsFor{$ModelName}" class="detailsLink"><% sprintf(_t('IMPORTSPECLINK', 'Show Specification for %s'),$ModelName) %></a>
	<div class="details" id="SpecDetailsFor{$ModelName}">
	<h4><% sprintf(_t('IMPORTSPECTITLE', 'Specification for %s'),$ModelName) %></h4>
		<h5><% _t('IMPORTSPECFIELDS', 'Database columns') %></h5>
		<% loop Fields %>
		<dl>
			<dt><em>$Name</em></dt>
			<dd>$Description</dd>
		</dl>
		<% end_loop %>

		<h5><% _t('IMPORTSPECRELATIONS', 'Relations') %></h5>
		<% loop Relations %>
		<dl>
			<dt><em>$Name</em></dt>
			<dd>$Description</dd>
		</dl>
		<% end_loop %>
	</div>
</div>
