<?php

/**
 * Vietnamese (United States) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('vi_US', $lang) && is_array($lang['vi_US'])) {
	$lang['vi_US'] = array_merge($lang['en_US'], $lang['vi_US']);
} else {
	$lang['vi_US'] = $lang['en_US'];
}

$lang['vi_US']['ConfirmedFormAction']['CONFIRMATION'] = 'Bạn có chắc chắn ?';
$lang['vi_US']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'Đổi mật khẩu';
$lang['vi_US']['DataObject']['PLURALNAME'] = 'Data Objects';
$lang['vi_US']['DataObject']['SINGULARNAME'] = 'Data Object';
$lang['vi_US']['ErrorPage']['PLURALNAME'] = 'Trang báo lỗi';
$lang['vi_US']['ErrorPage']['SINGULARNAME'] = 'Trang báo lỗi';
$lang['vi_US']['File']['INVALIDEXTENSION'] = 'Định dạng không hợp lệ (hợp lê: %s)';
$lang['vi_US']['File']['PLURALNAME'] = 'Các tập tin';
$lang['vi_US']['File']['SINGULARNAME'] = 'Tập tin';
$lang['vi_US']['File']['TOOLARGE'] = 'Dung lượng tập tin quá lớn, tối đa %s';
$lang['vi_US']['Folder']['PLURALNAME'] = 'Các tập tin';
$lang['vi_US']['Folder']['SINGULARNAME'] = 'Tập tin';
$lang['vi_US']['Group']['Code'] = 'Mã nhóm';
$lang['vi_US']['Group']['has_many_Permissions'] = 'Quyền';
$lang['vi_US']['Group']['Locked'] = 'Khóa ?';
$lang['vi_US']['Group']['many_many_Members'] = 'Thành viên';
$lang['vi_US']['Group']['Parent'] = 'Nhóm cha';
$lang['vi_US']['Group']['PLURALNAME'] = 'Nhóm';
$lang['vi_US']['Group']['SINGULARNAME'] = 'Nhóm';
$lang['vi_US']['Image']['PLURALNAME'] = 'Các tập tin';
$lang['vi_US']['Image']['SINGULARNAME'] = 'Tập tin';
$lang['vi_US']['Member']['belongs_many_many_Groups'] = 'Nhóm';
$lang['vi_US']['Member']['db_LockedOutUntil'] = 'Khóa cho đến khi';
$lang['vi_US']['Member']['PLURALNAME'] = 'Thành viên';
$lang['vi_US']['Member']['SINGULARNAME'] = 'Thành viên';

?>