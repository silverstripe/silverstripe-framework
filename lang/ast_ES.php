<?php

/**
 * Asturian language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('ast_ES', $lang) && is_array($lang['ast_ES'])) {
	$lang['ast_ES'] = array_merge($lang['en_US'], $lang['ast_ES']);
} else {
	$lang['ast_ES'] = $lang['en_US'];
}

$lang['ast_ES']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Camudasti la contraseña pa';
$lang['ast_ES']['ComplexTableField.ss']['ADDITEM'] = 'Amestar %s';
$lang['ast_ES']['ConfirmedFormAction']['CONFIRMATION'] = '¿Tas seguru?';
$lang['ast_ES']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'Camudar contraseña';
$lang['ast_ES']['DataObject']['PLURALNAME'] = 'Oxetos de datos';
$lang['ast_ES']['DataObject']['SINGULARNAME'] = 'Oxetu de datos';
$lang['ast_ES']['Date']['TIMEDIFFAGO'] = 'Hai %s';
$lang['ast_ES']['Date']['TIMEDIFFAWAY'] = 'Dientro de %s';
$lang['ast_ES']['DropdownField']['CHOOSE'] = '(Escoyer)';
$lang['ast_ES']['Email_BounceRecord']['PLURALNAME'] = 'Rexistros de rebote de corréu';
$lang['ast_ES']['Email_BounceRecord']['SINGULARNAME'] = 'Rexistru de rebote de corréu';
$lang['ast_ES']['ErrorPage']['PLURALNAME'] = 'Páxines d\'error';
$lang['ast_ES']['ErrorPage']['SINGULARNAME'] = 'Páxina d\'error';
$lang['ast_ES']['File']['INVALIDEXTENSION'] = 'La estensión nun ta permitida (válides: %s)';
$lang['ast_ES']['File']['PLURALNAME'] = 'Ficheros';
$lang['ast_ES']['File']['SINGULARNAME'] = 'Ficheru';
$lang['ast_ES']['File']['TOOLARGE'] = 'El tamañu de ficheru ye enforma grande, el máximu permitíu ye %s.';
$lang['ast_ES']['Folder']['PLURALNAME'] = 'Ficheros';
$lang['ast_ES']['Folder']['SINGULARNAME'] = 'Ficheru';
$lang['ast_ES']['Group']['Code'] = 'Códigu de grupu';
$lang['ast_ES']['Group']['has_many_Permissions'] = 'Permisos';
$lang['ast_ES']['Group']['Locked'] = '¿Bloquiáu?';
$lang['ast_ES']['Group']['many_many_Members'] = 'Miembros';
$lang['ast_ES']['Group']['Parent'] = 'Grupu padre';
$lang['ast_ES']['Group']['PLURALNAME'] = 'Grupos';
$lang['ast_ES']['Group']['SINGULARNAME'] = 'Grupu';
$lang['ast_ES']['HtmlEditorField']['FORMATADDR'] = 'Direición';
$lang['ast_ES']['HtmlEditorField']['FORMATH1'] = 'Testera 1';
$lang['ast_ES']['HtmlEditorField']['FORMATH2'] = 'Testera 2';
$lang['ast_ES']['HtmlEditorField']['FORMATH3'] = 'Testera 3';
$lang['ast_ES']['HtmlEditorField']['FORMATH4'] = 'Testera 4';
$lang['ast_ES']['HtmlEditorField']['FORMATH5'] = 'Testera 5';
$lang['ast_ES']['HtmlEditorField']['FORMATH6'] = 'Testera 6';
$lang['ast_ES']['HtmlEditorField']['FORMATP'] = 'Parágrafu';
$lang['ast_ES']['HtmlEditorField']['FORMATPRE'] = 'Con formatu previu';
$lang['ast_ES']['Image']['PLURALNAME'] = 'Ficheros';
$lang['ast_ES']['Image']['SINGULARNAME'] = 'Ficheru';
$lang['ast_ES']['Image_Cached']['PLURALNAME'] = 'Ficheros';
$lang['ast_ES']['Image_Cached']['SINGULARNAME'] = 'Ficheru';
$lang['ast_ES']['LoginAttempt']['PLURALNAME'] = 'Intentos de conexón';
$lang['ast_ES']['LoginAttempt']['SINGULARNAME'] = 'Intentu de conexón';
$lang['ast_ES']['Member']['belongs_many_many_Groups'] = 'Grupos';
$lang['ast_ES']['Member']['db_LockedOutUntil'] = 'Bloquiáu fasta';
$lang['ast_ES']['Member']['db_PasswordExpiry'] = 'Data d\'espiración de la contraseña';
$lang['ast_ES']['Member']['EMAIL'] = 'Corréu';
$lang['ast_ES']['Member']['INTERFACELANG'] = 'Llingua de la interfaz';
$lang['ast_ES']['Member']['PERSONALDETAILS'] = 'Información personal';
$lang['ast_ES']['Member']['PLURALNAME'] = 'Miembros';
$lang['ast_ES']['Member']['SINGULARNAME'] = 'Miembru';
$lang['ast_ES']['Member']['SUBJECTPASSWORDCHANGED'] = 'Se camudó la to contraseña';
$lang['ast_ES']['Member']['SUBJECTPASSWORDRESET'] = 'Enllaz pa reaniciar la contraseña';
$lang['ast_ES']['Member']['USERDETAILS'] = 'Detalles del usuariu';
$lang['ast_ES']['Member']['ValidationIdentifierFailed'] = 'Nun se pue sobroscribir el miembru esistente #%d col mesmu identificador (%s = %s))';
$lang['ast_ES']['MemberPassword']['PLURALNAME'] = 'Contraseñes del miembru';
$lang['ast_ES']['MemberPassword']['SINGULARNAME'] = 'Contraseña del miembru';
$lang['ast_ES']['NullableField']['IsNullLabel'] = 'Ye nulu';
$lang['ast_ES']['Page']['PLURALNAME'] = 'Páxines';
$lang['ast_ES']['Page']['SINGULARNAME'] = 'Páxina';
$lang['ast_ES']['Permission']['PLURALNAME'] = 'Permisos';
$lang['ast_ES']['Permission']['SINGULARNAME'] = 'Permisu';
$lang['ast_ES']['PermissionCheckboxSetField']['FromGroup'] = 'heredáu del grupu "%s"';
$lang['ast_ES']['PermissionCheckboxSetField']['FromRole'] = 'heredáu del rol "%s"';
$lang['ast_ES']['PermissionCheckboxSetField']['FromRoleOnGroup'] = 'heredáu del rol "%s" nel grupu "%s"';
$lang['ast_ES']['PermissionRole']['PLURALNAME'] = 'Roles';
$lang['ast_ES']['PermissionRole']['SINGULARNAME'] = 'Rol';
$lang['ast_ES']['PermissionRoleCode']['PLURALNAME'] = 'Códigos del rol de permisu';
$lang['ast_ES']['PermissionRoleCode']['SINGULARNAME'] = 'Códigu del rol de permisu';
$lang['ast_ES']['QueuedEmail']['PLURALNAME'] = 'Correos na cola';
$lang['ast_ES']['QueuedEmail']['SINGULARNAME'] = 'Corréu na cola';
$lang['ast_ES']['RedirectorPage']['PLURALNAME'] = 'Páxines del redireutor';
$lang['ast_ES']['RedirectorPage']['SINGULARNAME'] = 'Páxina del redireutor';
$lang['ast_ES']['Security']['ALREADYLOGGEDIN'] = 'Nun tienes accesu a esta páxina. Si tienes otra cuenta que pueda entrar nesta páxina, pues <a href="%s">volver a coneutate</a>.';
$lang['ast_ES']['SiteConfig']['PLURALNAME'] = 'Configuraciones del sitiu';
$lang['ast_ES']['SiteConfig']['SINGULARNAME'] = 'Configuración del sitiu';
$lang['ast_ES']['SiteTree']['CHANGETO'] = 'Camudar a "%s"';
$lang['ast_ES']['SiteTree']['Content'] = 'Conteníu';
$lang['ast_ES']['SiteTree']['has_one_Parent'] = 'Páxina padre';
$lang['ast_ES']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Dominiu(os)';
$lang['ast_ES']['SiteTree']['HTMLEDITORTITLE'] = 'Conteníu';
$lang['ast_ES']['SiteTree']['PAGETYPE'] = 'Triba de páxina';
$lang['ast_ES']['SiteTree']['PARENTID'] = 'Páxina padre';
$lang['ast_ES']['SiteTree']['PARENTTYPE'] = 'Llocalización de la páxina';
$lang['ast_ES']['SiteTree']['PLURALNAME'] = 'Árboles del sitiu';
$lang['ast_ES']['SiteTree']['SINGULARNAME'] = 'Árbol del sitiu';
$lang['ast_ES']['SiteTree']['URLSegment'] = 'Segmentu URL';
$lang['ast_ES']['Translatable']['TRANSLATEPERMISSION'] = 'Traducir %s';
$lang['ast_ES']['Versioned']['has_many_Versions'] = 'Versiones';
$lang['ast_ES']['VirtualPage']['PLURALNAME'] = 'Páxines virtuales';
$lang['ast_ES']['VirtualPage']['SINGULARNAME'] = 'Páxina virtual';
$lang['ast_ES']['Widget']['PLURALNAME'] = 'Widgets';
$lang['ast_ES']['Widget']['SINGULARNAME'] = 'Widget';
$lang['ast_ES']['WidgetArea']['PLURALNAME'] = 'Estayes del widget';
$lang['ast_ES']['WidgetArea']['SINGULARNAME'] = 'Estaya del widget';

?>