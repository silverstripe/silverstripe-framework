# Common configuration through _config.php

## Introduction

SilverStripe doesn't have a global configuration-array or an interface with all available configuration-options. As all
SilverStripe logic is contained in classes, the appropriate place to configure their behaviour is directly in the class
itself. 

This lack of a configuration-GUI is on purpose, as we'd like to keep developer-level options where they belong (into
code), without cluttering up the interface. See this core forum discussion ["The role of the
CMS"](http://www.silverstripe.com/core-team-discussion/flat/2723) for further reasoning.

In addition to these principle, some settings are 
 * Author-level configuration like interface language or date/time formats can be performed in the CMS "My Profile" section (`admin/myprofile`). 
 * Group-related configuration like `[api:HTMLEditorField]` settings can be found in the "Security" section (`admin/security`).
 * Site-wide settings like page titles can be set (and extended) on the root tree element in the CMS "Content" section (through the [siteconfig](/reference/siteconfig) API).

## _ss_environment.php

See [environment-management](/topics/environment-management).

## mysite/_config.php

This file is detected in each folder by `[api:ManifestBuilder]`. This way, every toplevel-folder (=module)
can have independent configuration-rules.


Please note that this is the only place where you can put in procedural code - all other functionality is wrapped in
classes (see [common-problems](/installation/common-problems)).

You can call most static methods from _config.php - classes will be loaded as required. Here's a list - **this is
incomplete - please add to it** *Try to keep it in alphabetical order too! :)*

 | Call    |                                                            | Description |                                                                                                                                                                                                                                
 | ----    |                                                            | ----------- |                                                                                                                                                                                                                             
 | Authenticator::register_authenticator($authenticator);|              | Enable an authentication method (for more details see [security](/topics/security)). |        
 | Authenticator::set_default_authenticator($authenticator); |          | Modify tab-order on login-form.|        
 | BasicAuth::disable() |                                               | Disable basic authentication checking for dev sites (useful when testing credit card transaction post-backs etc) |                                                             
 | BBCodeParser::disable_autolink_urls(); |                             | Disables plain hyperlinks from being turned into links when bbcode is parsed. |     
 | BlogEntry::allow_wysiwyg_editing();  |                               | Enable rich text editing for blog posts.  |                                                                                  
 | ContentNegotiator::set_encoding(string $encoding)  |					| The encoding charset to use - UTF-8 by default  |        
 | ContentNegotiator::disable()  |										| Disables the negotiation of content type -usually used to stop it from rewriting the DOCTYPE of the document                                                         
 | Debug::send_errors_to(string $email) |								| Send live errors on your site to this address (site has to be in 'live' mode using Director::set_environment_type(live) for this to occur |        
 | Director::set_environment_type(string dev,test,live) | 				| Sets the environment type (e.g. dev site will show errors, live site hides them and displays a 500 error instead) | 
 | Director::set_dev_servers(array('localhost', 'dev.mysite.com)) |     | Set servers that should be run in dev mode (see [debugging](debugging)) |                                                                                         
 | Director::addRules(int priority, array rules) |	                    | Create a number of URL rules to be checked against when SilverStripe tries to figure out how to display a page. See cms/_config.php for some examples. Note: Using ->something/ as the value for one of these will redirect the user to the something/ page. |        
 | Email::setAdminEmail(string $adminemail)  |                          | Sets the admin email for the site, used if there is no From address specified, or when you call Email::getAdminEmail() |        
 | Email::send_all_emails_to(string $email)  |                          | Sends all emails to this address. Useful for debugging your email sending functions  |        
 | Email::cc_all_emails_to(string $email)  |                            | Useful for CC'ing all emails to someone checking correspondence |        
 | Email::bcc_all_emails_to(string $email) |                            | BCC all emails to this address, similar to CC'ing emails (above)  |        
 | MathSpamProtection::setEnabled()  |                                  | Adds a math spam question to all page comment forms |        
 | PageComment::enableModeration();  |                                  | Enables comment moderation |        
 | Security::encrypt_passwords($encrypt_passwords);  |                  | Specify if you want store your passwords in clear text or encrypted (for more details see [security](/topics/security)) |        
 | Security::set_password_encryption_algorithm($algorithm, $use_salt);| | If you choose to encrypt your passwords, you can choose which algorithm is used to and if a salt should be used to increase the security level even more (for more details see [security](/topics/security)). |        
 | Security::setDefaultAdmin('admin','password'); |                     | Set default admin email and password, helpful for recovering your password |        
 | SSAkismet::setAPIKey(string $key)  |                                 | Enables use of the Akismet spam filter. The key must be a valid WordPress API key. |        
 | SSViewer::set_theme(string $themename) |                             | Choose the default theme for your site |   

## Constants

Some constants are user-defineable within *_ss_environment.php*.

 | Name  |																| Description | 
 | ----  |																| ----------- | 
 | *TEMP_FOLDER* |														| Absolute file path to store temporary files such as cached templates or the class manifest. Needs to be writeable by the webserver user. Defaults to *sys_get_temp_dir()*, and falls back to *silverstripe-cache* in the webroot. See *getTempFolder()* in *sapphire/core/Core.php* | 

## User-level: Member-object

All user-related preferences are stored as a property of the `[api:Member]`-class (and as a database-column in the
*Member*-table). You can "mix in" your custom preferences by using `[api:DataObject]` for details.

## Permissions

See [security](/topics/security) and [permission](/reference/permission)

## See Also

[Config Cheat sheet](http://www.ssbits.com/a-config-php-cheatsheet/)