<% if IncludeFormTag %>
<form $FormAttributes data-layout-type="border">
<% end_if %>
	<div class="cms-content-header north">
		<div>
			
			<% if Backlink %>
				<a class="backlink ss-ui-button cms-panel-link" data-icon="back" href="$Backlink">
					<% _t('Back', 'Back') %>
				</a>
			<% end_if %>

			<h2 id="page-title-heading">
			<% control Controller %>
				<% include CMSBreadcrumbs %>
			<% end_control %>
			</h2>
			<% if Fields.hasTabset %>
				<% with Fields.fieldByName('Root') %>
				<div class="cms-content-header-tabs">
					<ul>
					<% control Tabs %>
						<li><a href="#$id"<% if extraClass %> class="$extraClass"<% end_if %>>$Title</a></li>
					<% end_control %>
					</ul>
				</div>
				<% end_with %>
			<% end_if %>
	
			<!-- <div class="cms-content-search">...</div> -->

		</div>
	</div>

	<% control Controller %>
		$EditFormTools	
	<% end_control %>
	
	<div class="cms-content-fields center cms-panel-padded">
		<% if Message %>
		<p id="{$FormName}_error" class="message $MessageType">$Message</p>
		<% else %>
		<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
		<% end_if %>

		<fieldset>
			<% if Legend %><legend>$Legend</legend><% end_if %> 
			<% control Fields %>
				$FieldHolder
			<% end_control %>
			<div class="clear"><!-- --></div>
		</fieldset>
	</div>

	<div class="cms-content-actions south">
		<% if Actions %>
		<div class="Actions">
			<% control Actions %>
				$Field
			<% end_control %>
			<% if CurrentPage.PreviewLink %>
			<a href="$CurrentPage.PreviewLink" class="cms-preview-toggle-link ss-ui-button" data-icon="preview">
				<% _t('LeftAndMain.PreviewButton', 'Preview') %> &raquo;
			</a>
			<% end_if %>
		</div>
		<% end_if %>
	</div>
<% if IncludeFormTag %>
</form>
<% end_if %>