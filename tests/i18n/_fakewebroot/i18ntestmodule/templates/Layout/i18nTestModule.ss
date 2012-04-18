<% _t('i18nTestModule.LAYOUTTEMPLATE',"Layout Template") %>
<% _t('LAYOUTTEMPLATENONAMESPACE',"Layout Template no namespace") %>
<% sprintf(_t('i18nTestModule.SPRINTFNAMESPACE','My replacement: %s'),$TestProperty) %>
<% sprintf(_t('SPRINTFNONAMESPACE','My replacement no namespace: %s'),$TestProperty) %>
<% include i18nTestModuleInclude %>

<%t i18nTestModule.NEWMETHODSIG "New _t method signature test" %>
<%t i18nTestModule.INJECTIONS_DOES_NOT_EXIST "Hello {name} {greeting}. But it is late, {goodbye}" name="Mark" greeting="welcome" goodbye="bye" %>
<%t i18nTestModule.INJECTIONS "Hello {name} {greeting}. But it is late, {goodbye}" name="Paul" greeting="good you are here" goodbye="see you" %>
<%t i18nTestModule.INJECTIONS "Hello {name} {greeting}. But it is late, {goodbye}" is "New context (this should be ignored)" name="Steffen" greeting="willkommen" goodbye="wiedersehen" %>
<%t i18nTestModule.INJECTIONS name="Cat" greeting='meow' goodbye="meow" %>
<%t i18nTestModule.INJECTIONS name=$absoluteBaseURL greeting=$get_locale goodbye="global calls" %>