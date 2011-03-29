/**
 * File: SecurityAdmin.Tree.js
 * 
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/security/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/security/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/security/getitem';
SiteTreeHandlers.loadTree_url = 'admin/security/getsubtree';
SiteTreeHandlers.showRecord_url = 'admin/security/show/';
SiteTreeHandlers.controller_url = 'admin/security';