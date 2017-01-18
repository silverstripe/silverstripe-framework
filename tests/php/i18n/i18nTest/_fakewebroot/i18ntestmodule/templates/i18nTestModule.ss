<% _t('i18nTestModule.MAINTEMPLATE',"Main Template") %>
$Layout
lonely _t() call that should be ignored
<% _t('i18nTestModule.NEWENTITY',"Not stored in master file yet") %>
Single: $pluralise('i18nTestModule.PLURALS', 'An item|{count} items', 1)
Multiple: $pluralise('i18nTestModule.PLURALS', 'An item|{count} items', 4)
None: $pluralise('i18nTestModule.PLURALS', 'An item|{count} items', 0)
