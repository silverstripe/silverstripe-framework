<% if Foo %>
<% control Foo %><% end_control %>
<% _t('INCLUDENONAMESPACE', 'Include Value'); %>
<% _t('Test.INCLUDEWITHNAMESPACE', 'Include Value with namespace'); %>
<% include i18nTextCollectorTest_NestedInclude %>
<% end_if %>
_t(in text)