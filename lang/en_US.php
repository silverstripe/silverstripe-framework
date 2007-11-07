<?php

global $lang;

$lang['en_US']['ErrorPage']['CODE'] = 'Error code';
$lang['en_US']['GhostPage']['NOLINKED'] = 'This ghost page has no linked page.';
$lang['en_US']['Controller']['IMAGE'] = 'Image';
$lang['en_US']['Controller']['FILE'] = 'File';
$lang['en_US']['ImageUploader']['REPLACE'] = array(
	'Replace %s',
	 PR_MEDIUM,
	'Replace file/image'
);
$lang['en_US']['ImageUploader']['ONEFROMCOMPUTER'] = 'With one from your computer';
$lang['en_US']['ImageUplaoder']['ONEFROMFILESTORE'] = 'With one from the file store';
$lang['en_US']['ImageUploader']['ATTACH'] = array(
	'Attach %s',
	 PR_MEDIUM,
	'Attach image/file'
);
$lang['en_US']['ImageUploader']['FROMCOMPUTER'] = 'From your computer';
$lang['en_US']['ImageUploader']['FROMFILESTORE'] = 'From the file store';
$lang['en_US']['ImageUploader']['DELETE'] = array(
	'Delete %s',
	 PR_MEDIUM,
	'Delete file/image'
);
$lang['en_US']['ImageUploader']['CLICKREMOVE'] = array(
	'Click the button below to remove this %s.',
	
								PR_MEDIUM,
	'... this image/file'
);
$lang['en_US']['ImageUploader']['REALLYDELETE'] = 'Do you really want to remove this %s?';
$lang['en_US']['RedirectorPage']['HEADER'] = 'This page will redirect users to another page';
$lang['en_US']['RedirectorPage']['REDIRECTTO'] = 'Redirect to';
$lang['en_US']['RedirectorPage']['REDIRECTTOPAGE'] = 'A page on your website';
$lang['en_US']['RedirectorPage']['REDIRECTTOEXTERNAL'] = 'Another website';
$lang['en_US']['RedirectorPage']['YOURPAGE'] = 'Page on your website';
$lang['en_US']['RedirectorPage']['OTHERURL'] = 'Other website URL';
$lang['en_US']['RedirectorPage']['HASBEENSETUP'] = 'A redirector page has been set up without anywhere to redirect to.';
$lang['en_US']['SiteTree']['LINKSCHANGEDTO'] = ' changed %s -> %s';
$lang['en_US']['SiteTree']['LINKSALREADYUNIQUE'] = ' %s is already unique';
$lang['en_US']['SiteTree']['PAGESLINKING'] = 'The following pages link to this page:';
$lang['en_US']['SiteTree']['NOBACKLINKS'] = 'This page hasn\'t been linked to from any pages.';
$lang['en_US']['SiteTree']['TOPLEVEL'] = 'Site Content (Top Level)';
$lang['en_US']['SiteTree']['APPEARSVIRTUALPAGES'] = 'This content also appears on the virtual pages in the %s sections.';
$lang['en_US']['SiteTree']['HASBROKENLINKS'] = 'This page has broken links.';
$lang['en_US']['SiteTree']['PAGETITLE'] = 'Page name';
$lang['en_US']['SiteTree']['MENUTITLE'] = 'Navigation label';
$lang['en_US']['SiteTree']['HTMLEDITORTITLE'] = array(
	'Content',
	 PR_MEDIUM,
	'HTML editor title'
);
$lang['en_US']['SiteTree']['URL'] = 'URL';
$lang['en_US']['SiteTree']['VALIDATIONURLSEGMENT1'] = 'Another page is using that URL. URL must be unique for each page';
$lang['en_US']['SiteTree']['VALIDATIONURLSEGMENT2'] = 'URLs can only be made up of letters, digits and hyphens.';
$lang['en_US']['SiteTree']['METAHEADER'] = 'Search Engine Meta-tags';
$lang['en_US']['SiteTree']['METATITLE'] = 'Title';
$lang['en_US']['SiteTree']['METADESC'] = 'Description';
$lang['en_US']['SiteTree']['METAKEYWORDS'] = 'Keywords';
$lang['en_US']['SiteTree']['METAADVANCEDHEADER'] = 'Advanced Options...';
$lang['en_US']['SiteTree']['METAEXTRA'] = 'Custom Meta Tags';
$lang['en_US']['SiteTree']['METANOTEPRIORITY'] = 'Manually specify a Priority for this page: 
											(valid values are from 0 to 1, a zero will remove this page from the index)';
$lang['en_US']['SiteTree']['METAPAGEPRIO'] = 'Page Priority';
$lang['en_US']['SiteTree']['PAGETYPE'] = array(
	'Page type',
	 PR_MEDIUM,
	'Classname of a page object'
);
$lang['en_US']['SiteTree']['SHOWINMENUS'] = 'Show in menus?';
$lang['en_US']['SiteTree']['SHOWINSEARCH'] = 'Show in search?';
$lang['en_US']['SiteTree']['ALLOWCOMMENTS'] = 'Allow comments on this page?';
$lang['en_US']['SiteTree']['NOTEUSEASHOMEPAGE'] = 'Use this page as the \'home page\' for the following domains: 
							(separate multiple domains with commas)';
$lang['en_US']['SiteTree']['HOMEPAGEFORDOMAIN'] = array(
	'Domain(s)',
	 PR_MEDIUM,
	'Listing domains that should be used as homepage'
);
$lang['en_US']['SiteTree']['ACCESSHEADER'] = 'Who can view this page on my site?';
$lang['en_US']['SiteTree']['ACCESSANYONE'] = 'Anyone';
$lang['en_US']['SiteTree']['ACCESSLOGGEDIN'] = 'Logged-in users';
$lang['en_US']['SiteTree']['ACCESSONLYTHESE'] = 'Only these people (choose from list)';
$lang['en_US']['SiteTree']['GROUP'] = 'Group';
$lang['en_US']['SiteTree']['EDITHEADER'] = 'Who can edit this inside the CMS?';
$lang['en_US']['SiteTree']['EDITANYONE'] = 'Anyone who can log-in to the CMS';
$lang['en_US']['SiteTree']['EDITONLYTHESE'] = 'Only these people (choose from list)';
$lang['en_US']['SiteTree']['TABCONTENT'] = 'Content';
$lang['en_US']['SiteTree']['TABMAIN'] = 'Main';
$lang['en_US']['SiteTree']['TABMETA'] = 'Meta-data';
$lang['en_US']['SiteTree']['TABBEHAVIOUR'] = 'Behaviour';
$lang['en_US']['SiteTree']['TABREPORTS'] = 'Reports';
$lang['en_US']['SiteTree']['TABACCESS'] = 'Access';
$lang['en_US']['SiteTree']['TABBACKLINKS'] = 'BackLinks';
$lang['en_US']['SiteTree']['BUTTONUNPUBLISH'] = 'Unpublish';
$lang['en_US']['SiteTree']['BUTTONUNPUBLISHDESC'] = 'Remove this page from the published site';
$lang['en_US']['SiteTree']['BUTTONCANCELDRAFT'] = 'Cancel draft changes';
$lang['en_US']['SiteTree']['BUTTONCANCELDRAFTDESC'] = 'Delete your draft and revert to the currently published page';
$lang['en_US']['SiteTree']['BUTTONSAVEPUBLISH'] = 'Save & Publish';
$lang['en_US']['SiteTree']['REMOVEDFROMDRAFT'] = 'Removed from draft site';
$lang['en_US']['SiteTree']['ADDEDTODRAFT'] = 'Added to draft site';
$lang['en_US']['SiteTree']['MODIFIEDONDRAFT'] = 'Modified on draft site';
$lang['en_US']['VirtualPage']['CHOOSE'] = 'Choose a page to link to';
$lang['en_US']['VirtualPage']['HEADER'] = 'This is a virtual page';
$lang['en_US']['VirtualPage']['EDITCONTENT'] = 'click here to edit the content';
$lang['en_US']['Date']['AWAY'] = ' away';
$lang['en_US']['Date']['AGO'] = ' ago';
$lang['en_US']['Date']['SECS'] = ' secs';
$lang['en_US']['Date']['SEC'] = ' sec';
$lang['en_US']['Date']['MINS'] = ' mins';
$lang['en_US']['Date']['MIN'] = ' min';
$lang['en_US']['Date']['HOURS'] = ' hours';
$lang['en_US']['Date']['HOUR'] = ' hour';
$lang['en_US']['Date']['DAYS'] = ' days';
$lang['en_US']['Date']['DAY'] = ' day';
$lang['en_US']['Date']['MONTHS'] = ' months';
$lang['en_US']['Date']['MONTH'] = ' month';
$lang['en_US']['Date']['YEARS'] = ' years';
$lang['en_US']['Date']['YEAR'] = ' year';
$lang['en_US']['Form']['VALIDATIONNOTUNIQUE'] = 'The value entered is not unique';
$lang['en_US']['Form']['VALIDATIONBANKACC'] = 'Please enter a valid bank number';
$lang['en_US']['Form']['VALIDATIONALLDATEVALUES'] = 'Please ensure you have set all date values';
$lang['en_US']['Form']['DATENOTSET'] = '(No date set)';
$lang['en_US']['Form']['NOTSET'] = '(not set)';
$lang['en_US']['Member']['CONFIRMPASSWORD'] = 'Confirm Password';
$lang['en_US']['Form']['VALIDATIONPASSWORDSDONTMATCH'] = 'Passwords don\'t match';
$lang['en_US']['Form']['VALIDATIONPASSWORDSNOTEMPTY'] = 'Passwords can\'t be empty';
$lang['en_US']['Form']['VALIDATIONSTRONGPASSWORD'] = 'Passwords must have at least one digit and one alphanumeric character.';
$lang['en_US']['Form']['VALIDATIONCREDITNUMBER'] = 'Please ensure you have entered the %s credit card number correctly.';
$lang['en_US']['Form']['VALIDCURRENCY'] = 'Please enter a valid currency.';
$lang['en_US']['Form']['FIELDISREQUIRED'] = '%s is required';
$lang['en_US']['DateField']['VALIDDATEFORMAT'] = 'Please enter a valid  date format (DD/MM/YYYY).';
$lang['en_US']['Form']['SAVECHANGES'] = 'Save Changes';
$lang['en_US']['EmailField']['VALIDATION'] = 'Please enter an email address.';
$lang['en_US']['FileIframeField']['NOTEADDFILES'] = 'You can add files once you have saved for the first time.';
$lang['en_US']['Form']['VALIDATIONFAILED'] = 'Validation failed';
$lang['en_US']['GSTNumberField']['VALIDATION'] = 'Please enter a valid GST Number';
$lang['en_US']['HtmlEditorField']['BUTTONBOLD'] = 'Bold (Ctrl+B)';
$lang['en_US']['HtmlEditorField']['BUTTONITALIC'] = 'Italic (Ctrl+I)';
$lang['en_US']['HtmlEditorField']['BUTTONUNDERLINE'] = 'Underline (Ctrl+U)';
$lang['en_US']['HtmlEditorField']['BUTTONSTRIKE'] = 'strikethrough';
$lang['en_US']['HtmlEditorField']['BUTTONALIGNLEFT'] = 'Align left';
$lang['en_US']['HtmlEditorField']['BUTTONALIGNCENTER'] = 'Align center';
$lang['en_US']['HtmlEditorField']['BUTTONALIGNRIGHT'] = 'Align right';
$lang['en_US']['HtmlEditorField']['BUTTONALIGNJUSTIFY'] = 'Justify';
$lang['en_US']['HtmlEditorField']['FORMATP'] = array(
	'Paragraph',
	 PR_MEDIUM,
	'<p> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATADDR'] = array(
	'Address',
	 PR_MEDIUM,
	'<address> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH1'] = array(
	'Heading 1',
	 PR_MEDIUM,
	'<h1> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH2'] = array(
	'Heading 2',
	 PR_MEDIUM,
	'<h2> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH3'] = array(
	'Heading 3',
	 PR_MEDIUM,
	'<h3> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH4'] = array(
	'Heading 4',
	 PR_MEDIUM,
	'<h4> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH5'] = array(
	'Heading 5',
	 PR_MEDIUM,
	'<h5> tag'
);
$lang['en_US']['HtmlEditorField']['FORMATH6'] = array(
	'Heading 6',
	 PR_MEDIUM,
	'<h6> tag'
);
$lang['en_US']['HtmlEditorField']['BULLETLIST'] = 'Bullet-point list';
$lang['en_US']['HtmlEditorField']['OL'] = 'Numbered list';
$lang['en_US']['HtmlEditorField']['OUTDENT'] = 'Decrease outdent';
$lang['en_US']['HtmlEditorField']['INDENT'] = 'Increase indent';
$lang['en_US']['HtmlEditorField']['HR'] = 'Insert horizontal line';
$lang['en_US']['HtmlEditorField']['CHARMAP'] = 'Insert symbol';
$lang['en_US']['HtmlEditorField']['UNDO'] = 'Undo (Ctrl+Z)';
$lang['en_US']['HtmlEditorField']['REDO'] = 'Redo (Ctrl+Y)';
$lang['en_US']['HtmlEditorField']['CUT'] = 'Cut (Ctrl+X)';
$lang['en_US']['HtmlEditorField']['COPY'] = 'Copy (Ctrl+C)';
$lang['en_US']['HtmlEditorField']['PASTE'] = 'Paste (Ctrl+V)';
$lang['en_US']['HtmlEditorField']['IMAGE'] = 'Insert image';
$lang['en_US']['HtmlEditorField']['FLASH'] = 'Insert flash';
$lang['en_US']['HtmlEditorField']['LINK'] = 'Insert/edit link for highlighted text';
$lang['en_US']['HtmlEditorField']['UNLINK'] = 'Remove link';
$lang['en_US']['HtmlEditorField']['ANCHOR'] = 'Insert/edit anchor';
$lang['en_US']['HtmlEditorField']['EDITCODE'] = 'Edit HTML Code';
$lang['en_US']['HtmlEditorField']['VISUALAID'] = 'Show/hide guidelines';
$lang['en_US']['HtmlEditorField']['INSERTTABLE'] = 'Insert table';
$lang['en_US']['HtmlEditorField']['INSERTROWBEF'] = 'Insert row before';
$lang['en_US']['HtmlEditorField']['INSERTROWAFTER'] = 'Insert row after';
$lang['en_US']['HtmlEditorField']['DELETEROW'] = 'Delete row';
$lang['en_US']['HtmlEditorField']['INSERTCOLBEF'] = 'Insert column before';
$lang['en_US']['HtmlEditorField']['INSERTCOLAFTER'] = 'Insert column after';
$lang['en_US']['HtmlEditorField']['DELETECOL'] = 'Delete column';
$lang['en_US']['HtmlEditorField']['LINKTO'] = 'Link to';
$lang['en_US']['HtmlEditorField']['LINKINTERNAL'] = 'Page on the site';
$lang['en_US']['HtmlEditorField']['LINKEXTERNAL'] = 'Another website';
$lang['en_US']['HtmlEditorField']['LINKEMAIL'] = 'Email address';
$lang['en_US']['HtmlEditorField']['LINKFILE'] = 'Download a file';
$lang['en_US']['HtmlEditorField']['PAGE'] = 'Page';
$lang['en_US']['HtmlEditorField']['URL'] = 'URL';
$lang['en_US']['HtmlEditorField']['EMAIL'] = 'Email address';
$lang['en_US']['HtmlEditorField']['FILE'] = 'File';
$lang['en_US']['HtmlEditorField']['LINKDESCR'] = 'Link description';
$lang['en_US']['HtmlEditorField']['LINKOPENNEWWIN'] = 'Open link in a new window?';
$lang['en_US']['HtmlEditorField']['BUTTONINSERTLINK'] = 'Insert link';
$lang['en_US']['HtmlEditorField']['BUTTONREMOVELINK'] = 'Remove link';
$lang['en_US']['HtmlEditorField']['BUTTONCANCEL'] = 'Cancel';
$lang['en_US']['HtmlEditorField']['FOLDER'] = 'Folder';
$lang['en_US']['HtmlEditorField']['ALTTEXT'] = 'Description';
$lang['en_US']['HtmlEditorField']['CSSCLASS'] = 'Alignment / style';
$lang['en_US']['HtmlEditorField']['CSSCLASSLEFT'] = 'On the left, with text wrapping around.';
$lang['en_US']['HtmlEditorField']['CSSCLASSRIGHT'] = 'On the right, with text wrapping around.';
$lang['en_US']['HtmlEditorField']['CSSCLASSCENTER'] = 'Centered, on its own.';
$lang['en_US']['HtmlEditorField']['IMAGEDIMENSIONS'] = 'Dimensions';
$lang['en_US']['HtmlEditorField']['IMAGEWIDTHPX'] = 'Width';
$lang['en_US']['HtmlEditorField']['IMAGEHEIGHTPX'] = 'Height';
$lang['en_US']['ImageField']['NOTEADDIMAGES'] = 'You can add images once you have saved for the first time.';
$lang['en_US']['Form']['LANGAVAIL'] = 'Available languages';
$lang['en_US']['Form']['LANGAOTHER'] = 'Other languages';
$lang['en_US']['NumericField']['VALIDATION'] = '\'%s\' is not a number, only numbers can be accepted for this field';
$lang['en_US']['PhoneNumberField']['VALIDATION'] = 'Please enter a valid phone number';
$lang['en_US']['SimpleImageField']['NOUPLOAD'] = 'No Image Uploaded';
$lang['en_US']['TableField']['ISREQUIRED'] = 'In %s \'%s\' is required.';
$lang['en_US']['ToggleField']['MORE'] = 'more';
$lang['en_US']['ToggleField']['LESS'] = 'less';
$lang['en_US']['DropdownField']['CHOOSE'] = array(
	'(Choose)',
	 PR_MEDIUM,
	'Start-value of a dropdown'
);
$lang['en_US']['TypeDropdown']['NONE'] = 'None';
$lang['en_US']['BasicAuth']['ERRORNOTREC'] = 'That username / password isn\'t recognised';
$lang['en_US']['BasicAuth']['ENTERINFO'] = 'Please enter a username and password.';
$lang['en_US']['BasicAuth']['ERRORNOTADMIN'] = 'That user is not an administrator.';
$lang['en_US']['Member']['YOUROLDPASSWORD'] = 'Your old password';
$lang['en_US']['Member']['NEWPASSWORD'] = 'New Password';
$lang['en_US']['Member']['CONFIRMNEWPASSWORD'] = 'Confirm New Password';
$lang['en_US']['Member']['BUTTONCHANGEPASSWORD'] = 'Change Password';
$lang['en_US']['Member']['ERRORPASSWORDNOTMATCH'] = 'Your current password does not match, please try again';
$lang['en_US']['Member']['PASSWORDCHANGED'] = 'Your password has been changed, and a copy emailed to you.';
$lang['en_US']['Member']['ERRORNEWPASSWORD'] = 'Your have entered your new password differently, try again';
$lang['en_US']['Member']['FIRSTNAME'] = 'First Name';
$lang['en_US']['Member']['SURNAME'] = 'Surname';
$lang['en_US']['Member']['EMAIL'] = array(
	'Email',
	 PR_MEDIUM,
	'Noun'
);
$lang['en_US']['Member']['PASSWORD'] = 'Password';
$lang['en_US']['Member']['PERSONALDETAILS'] = array(
	'Personal Details',
	 PR_MEDIUM,
	'Headline for formfields'
);
$lang['en_US']['Member']['USERDETAILS'] = array(
	'User Details',
	 PR_MEDIUM,
	'Headline for formfields'
);
$lang['en_US']['Member']['INTERFACELANG'] = array(
	'Interface Language',
	 PR_MEDIUM,
	'Language of the CMS'
);
$lang['en_US']['Member']['EMAILSIGNUPSUBJECT'] = 'Thanks for signing up';
$lang['en_US']['Member']['GREETING'] = 'Welcome';
$lang['en_US']['Member']['EMAILSIGNUPINTRO1'] = 'Thanks for signing up to become a new member, your details are listed below for future reference.';
$lang['en_US']['Member']['EMAILSIGNUPINTRO2'] = 'You can login to the website using the credentials listed below';
$lang['en_US']['Member']['CONTACTINFO'] = 'Contact Information';
$lang['en_US']['Member']['NAME'] = 'Name';
$lang['en_US']['Member']['PHONE'] = 'Phone';
$lang['en_US']['Member']['MOBILE'] = 'Mobile';
$lang['en_US']['Member']['ADDRESS'] = 'Address';
$lang['en_US']['Member']['SUBJECTPASSWORDCHANGED'] = array(
	'Your password has been changed',
	 PR_MEDIUM,
	'Email subject'
);
$lang['en_US']['Member']['SUBJECTPASSWORDRESET'] = array(
	'Your password reset link',
	 PR_MEDIUM,
	'Email subject'
);
$lang['en_US']['Member']['EMAILPASSWORDINTRO'] = 'Here\'s your new password';
$lang['en_US']['Member']['EMAILPASSWORDAPPENDIX'] = 'Your password has been changed. Please keep this email, for future reference.';
$lang['en_US']['Member']['VALIDATIONMEMBEREXISTS'] = 'There already exists a member with this email';
$lang['en_US']['Member']['ERRORWRONGCRED'] = 'That doesn\'t seem to be the right e-mail address or password. Please try again.';
$lang['en_US']['MemberAuthenticator']['TITLE'] = 'E-mail &amp; Password';
$lang['en_US']['Member']['BUTTONLOGINOTHER'] = 'Log in as someone else';
$lang['en_US']['Member']['REMEMBERME'] = 'Remember me next time?';
$lang['en_US']['Member']['BUTTONLOGIN'] = 'Log in';
$lang['en_US']['Member']['BUTTONLOSTPASSWORD'] = 'I\'ve lost my password';
$lang['en_US']['Member']['LOGGEDINAS'] = 'You\'re logged in as %s.';
$lang['en_US']['Member']['WELCOMEBACK'] = 'Welcome Back, %s';
$lang['en_US']['Security']['NOTEPAGESECURED'] = 'That page is secured. Enter your credentials below and we will send you right along.';
$lang['en_US']['Security']['ALREADYLOGGEDIN'] = 'You don\'t have access to this page.  If you have another account that can access that page, you can log in below.';
$lang['en_US']['Security']['LOGGEDOUT'] = 'You have been logged out.  If you would like to log in again, enter your credentials below.';
$lang['en_US']['Security']['LOSTPASSWORDHEADER'] = 'Lost Password';
$lang['en_US']['Security']['NOTERESETPASSWORD'] = 'Enter your e-mail address and we will send you a link with which you can reset your password';
$lang['en_US']['Security']['BUTTONSEND'] = 'Send me the password reset link';
$lang['en_US']['Security']['PASSWORDSENTHEADER'] = 'Password reset link sent to \'%s\'';
$lang['en_US']['Security']['PASSWORDSENTTEXT'] = 'Thank you! The password reset link has been sent to \'%s\'.';
$lang['en_US']['Security']['CHANGEPASSWORDHEADER'] = 'Change your password';
$lang['en_US']['Security']['ENTERNEWPASSWORD'] = 'Please enter a new password.';
$lang['en_US']['Security']['CHANGEPASSWORDBELOW'] = 'You can change your password below.';
$lang['en_US']['Security']['ERRORPASSWORDPERMISSION'] = 'You must be logged in in order to change your password!';
$lang['en_US']['ComplexTableField.ss']['SORTASC'] = 'Sort ascending';
$lang['en_US']['ComplexTableField.ss']['SORTDESC'] = 'Sort descending';
$lang['en_US']['ComplexTableField.ss']['ADDITEM'] = array(
	'Add',
	 PR_MEDIUM,
	'Add [name]'
);
$lang['en_US']['ComplexTableField.ss']['SHOW'] = 'show';
$lang['en_US']['ComplexTableField.ss']['EDIT'] = 'edit';
$lang['en_US']['ComplexTableField.ss']['DELETEROW'] = 'Delete this row';
$lang['en_US']['ComplexTableField.ss']['DELETE'] = 'delete';
$lang['en_US']['ComplexTableField.ss']['NOITEMSFOUND'] = 'No items found';
$lang['en_US']['ComplexTableField_popup.ss']['PREVIOUS'] = 'Previous';
$lang['en_US']['ComplexTableField_popup.ss']['NEXT'] = 'Next';
$lang['en_US']['Image_iframe.ss']['TITLE'] = 'Image Uploading Iframe';
$lang['en_US']['TableField.ss']['CSVEXPORT'] = 'Export to CSV';
$lang['en_US']['ToggleCompositeField.ss']['SHOW'] = 'Show';
$lang['en_US']['ToggleCompositeField.ss']['HIDE'] = 'Hide';
$lang['en_US']['ChangePasswordEmail.ss']['HELLO'] = 'Hi';
$lang['en_US']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = array(
	'You changed your password for',
	 PR_MEDIUM,
	'for a url'
);
$lang['en_US']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT2'] = 'You can now use the following credentials to log in:';
$lang['en_US']['ForgotPasswordEmail.ss']['HELLO'] = 'Hi';

?>