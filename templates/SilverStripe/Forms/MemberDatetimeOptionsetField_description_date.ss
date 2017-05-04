<a href="#" class="toggle">
	<% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.Toggle', 'Show formatting help') %>
</a>
<ul class="toggle-content list-unstyled">
	<li>YYYY = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.FOURDIGITYEAR', 'Four-digit year',
			40, 'Help text describing what "YYYY" means in ISO date formatting') %></li>
	<li>YY = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITYEAR', 'Two-digit year',
			40, 'Help text describing what "YY" means in ISO date formatting') %></li>
	<li>MMMM = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.FULLNAMEMONTH', 'Full name of month (e.g. June)',
			40, 'Help text describing what "MMMM" means in ISO date formatting') %></li>
	<li>MMM = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.SHORTMONTH', 'Short name of month (e.g. Jun)',
			40, 'Help text letting describing what "MMM" means in ISO date formatting') %></li>
	<li>MM = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITMONTH', 'Two-digit month (01=January, etc.)',
			40, 'Help text describing what "MM" means in ISO date formatting') %></li>
	<li>M = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.MONTHNOLEADING', 'Month digit without leading zero',
			40, 'Help text describing what "M" means in ISO date formatting') %></li>
	<li>dd = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITDAY', 'Two-digit day of month',
			40, 'Help text describing what "dd" means in ISO date formatting') %></li>
	<li>d = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.DAYNOLEADING', 'Day of month without leading zero',
			40, 'Help text describing what "d" means in ISO date formatting') %></li>
</ul>
