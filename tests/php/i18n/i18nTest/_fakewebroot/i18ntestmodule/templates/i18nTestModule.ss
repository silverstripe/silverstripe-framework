<%t i18nTestModule.MAINTEMPLATE "Main Template" %>
$Layout
lonely _t() call that should be ignored
<%t i18nTestModule.NEWENTITY "Not stored in master file yet" %>
Single: <%t i18nTestModule.PLURALS 'An item|{count} items' count=1 %>
Multiple: <%t i18nTestModule.PLURALS 'An item|{count} items' count=4 %>
None: <%t i18nTestModule.PLURALS 'An item|{count} items' count=0 %>
