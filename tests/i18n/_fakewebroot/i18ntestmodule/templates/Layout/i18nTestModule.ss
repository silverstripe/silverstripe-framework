<% _t('i18nTestModule.LAYOUTTEMPLATE',"Layout Template") %>
<% _t('LAYOUTTEMPLATENONAMESPACE',"Layout Template no namespace") %>
<% sprintf(_t('i18nTestModule.SPRINTFNAMESPACE','My replacement: %s'),$TestProperty) %>
<% sprintf(_t('SPRINTFNONAMESPACE','My replacement no namespace: %s'),$TestProperty) %>
<% include i18nTestModuleInclude %>