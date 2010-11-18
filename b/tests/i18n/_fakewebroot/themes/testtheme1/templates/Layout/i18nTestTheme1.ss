<% _t('i18nTestTheme1.LAYOUTTEMPLATE',"Theme1 Layout Template") %>
<% _t('LAYOUTTEMPLATENONAMESPACE',"Theme1 Layout Template no namespace") %>
<% sprintf(_t('i18nTestTheme1.SPRINTFNAMESPACE','Theme1 My replacement: %s'),$TestProperty) %>
<% sprintf(_t('SPRINTFNONAMESPACE','Theme1 My replacement no namespace: %s'),$TestProperty) %>
<% include i18nTestTheme1Include %>