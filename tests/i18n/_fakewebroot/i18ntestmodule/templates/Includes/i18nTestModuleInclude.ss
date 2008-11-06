<% _t("i18nTestModule.WITHNAMESPACE", 'Include Entity with Namespace') %>
<% _t("NONAMESPACE", 'Include Entity without Namespace') %>
<% sprintf(_t('i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE','My include replacement: %s'),$TestProperty) %>
<% sprintf(_t('SPRINTFINCLUDENONAMESPACE','My include replacement no namespace: %s'),$TestProperty) %>