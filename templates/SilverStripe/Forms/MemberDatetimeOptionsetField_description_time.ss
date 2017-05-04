<a href="#" class="toggle">
	<% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.Toggle', 'Show formatting help') %>
</a>
<ul class="toggle-content list-unstyled">
	<li>HH = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITHOUR24', 'Two digits of hour, 24 hour format (00 through 23)',
			40, 'Help text describing what "hh" means in ISO date formatting') %></li>
	<li>H = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.HOURNOLEADING24', 'Hour without leading zero, 24 hour format',
			40, 'Help text describing what "h" means in ISO date formatting') %></li>
	<li>hh = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITHOUR', 'Two digits of hour, 12 hour format (00 through 12)',
			40, 'Help text describing what "hh" means in ISO date formatting') %></li>
	<li>h = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.HOURNOLEADING', 'Hour without leading zero, 12 hour format',
			40, 'Help text describing what "h" means in ISO date formatting') %></li>
	<li>mm = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITMINUTE',
			'Two digits of minute (00 through 59)',
			40, 'Help text describing what "mm" means in ISO date formatting') %></li>
	<li>m = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.MINUTENOLEADING', 'Minute without leading zero',
			40, 'Help text describing what "m" means in ISO date formatting') %></li>
	<li>ss = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.TWODIGITSECOND',
			'Two digits of second (00 through 59)',
			40, 'Help text describing what "ss" means in ISO date formatting') %></li>
	<li>s = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.DIGITSDECFRACTIONSECOND',
			'One or more digits representing a decimal fraction of a second',
			40, 'Help text describing what "s" means in ISO date formatting') %></li>
	<li>a = <% _t('SilverStripe\Forms\MemberDatetimeOptionsetField.AMORPM', 'AM (Ante meridiem) or PM (Post meridiem)',
			40, 'Help text describing what "a" means in ISO date formatting') %></li>
</ul>
