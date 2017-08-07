<%t i18nTestTheme1.LAYOUTTEMPLATE "Theme1 Layout Template" %>
<%t LAYOUTTEMPLATENONAMESPACE "Theme1 Layout Template no namespace" %>
<%t i18nTestTheme1.REPLACEMENTNAMESPACE 'Theme1 My replacement: {replacement}' replacement=$TestProperty %>
<%t REPLACEMENTNONAMESPACE 'Theme1 My replacement no namespace: {replacement}' replacement=$TestProperty %>
<% include i18nTestTheme1Include %>
