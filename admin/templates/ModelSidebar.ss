<% if CreateForm %>
	<h3><% _t('ADDLISTING','Add') %></h3>
	$CreateForm
<% end_if %>

<h3><% _t('SEARCHLISTINGS','Search') %></h3>
$SearchForm

<% if ImportForm %>
	<h3><% _t('IMPORT_TAB_HEADER', 'Import') %></h3>
	$ImportForm
<% end_if %>