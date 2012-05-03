<div id="$Name" class="$Type $extraClass">	
	<h$HeadingLevel style="cursor: pointer;" class="trigger$ClosedClass">
		<img class="triggerClosed" src="$ModulePath(framework)/images/toggle-closed.gif" alt="+" style="display:none;" title="<% _t('SHOW', 'Show') %>" />
		<img class="triggerOpened" src="$ModulePath(framework)/images/toggle-open.gif" alt="-" style="display:none;" title="<% _t('HIDE', 'Hide') %>" /> 
		$Title
	</h$HeadingLevel>
	<div class="contentMore">
	<% control FieldSet %>
	$FieldHolder
	<% end_control %>
	</div>
</div>
