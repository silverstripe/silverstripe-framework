<a href="#" class="toggle">
	<% _t('MemberDatetimeOptionsetField.Toggle', 'Show formatting help') %>
</a>
<ul class="toggle-content list-unstyled">
	<li>HH = <% _t('MemberDatetimeOptionsetField.TWODIGITHOUR24', 'Two digits of hour, 24 hour format (00 through 23)',
			40, 'Help text describing what "hh" means in ISO date formatting') %></li>
	<li>H = <% _t('MemberDatetimeOptionsetField.HOURNOLEADING24', 'Hour without leading zero, 24 hour format',
			40, 'Help text describing what "h" means in ISO date formatting') %></li>
	<li>hh = <% _t('MemberDatetimeOptionsetField.TWODIGITHOUR', 'Two digits of hour, 12 hour format (00 through 12)',
			40, 'Help text describing what "hh" means in ISO date formatting') %></li>
	<li>h = <% _t('MemberDatetimeOptionsetField.HOURNOLEADING', 'Hour without leading zero, 12 hour format',
			40, 'Help text describing what "h" means in ISO date formatting') %></li>
	<li>mm = <% _t('MemberDatetimeOptionsetField.TWODIGITMINUTE',
			'Two digits of minute (00 through 59)',
			40, 'Help text describing what "mm" means in ISO date formatting') %></li>
	<li>m = <% _t('MemberDatetimeOptionsetField.MINUTENOLEADING', 'Minute without leading zero',
			40, 'Help text describing what "m" means in ISO date formatting') %></li>
	<li>ss = <% _t('MemberDatetimeOptionsetField.TWODIGITSECOND',
			'Two digits of second (00 through 59)',
			40, 'Help text describing what "ss" means in ISO date formatting') %></li>
	<li>s = <% _t('MemberDatetimeOptionsetField.DIGITSDECFRACTIONSECOND',
			'One or more digits representing a decimal fraction of a second',
			40, 'Help text describing what "s" means in ISO date formatting') %></li>
	<li>a = <% _t('MemberDatetimeOptionsetField.AMORPM', 'AM (Ante meridiem) or PM (Post meridiem)',
			40, 'Help text describing what "a" means in ISO date formatting') %></li>
</ul>
