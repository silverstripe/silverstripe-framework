<?php

/**
 * Thai (Thailand) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('th_TH', $lang) && is_array($lang['th_TH'])) {
	$lang['th_TH'] = array_merge($lang['en_US'], $lang['th_TH']);
} else {
	$lang['th_TH'] = $lang['en_US'];
}

$lang['th_TH']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'คุณเปลี่ยนรหัสผ่านสำหรับ';
$lang['th_TH']['ComplexTableField.ss']['ADDITEM'] = 'เพิ่ม %s';
$lang['th_TH']['ConfirmedFormAction']['CONFIRMATION'] = 'คุณแน่ใจหรือไม่?';
$lang['th_TH']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'เปลี่ยนรหัสผ่าน';
$lang['th_TH']['Date']['TIMEDIFFAGO'] = '%s ที่ผ่านมา';
$lang['th_TH']['DropdownField']['CHOOSE'] = '(เลือก)';
$lang['th_TH']['File']['INVALIDEXTENSION'] = 'ส่วนขยายไม่ได้รับอนุญาต (ถูกต้อง: %s)';
$lang['th_TH']['File']['PLURALNAME'] = 'ไฟล์';
$lang['th_TH']['File']['SINGULARNAME'] = 'ไฟล์';
$lang['th_TH']['File']['TOOLARGE'] = 'ไฟล์ขนาดใหญ่เกินไป จำกัดสูงสุดที่ %s';
$lang['th_TH']['Folder']['PLURALNAME'] = 'ไฟล์';
$lang['th_TH']['Folder']['SINGULARNAME'] = 'ไฟล์';
$lang['th_TH']['Group']['Code'] = 'รหัสกลุ่ม';
$lang['th_TH']['Group']['has_many_Permissions'] = 'สิทธิ์';
$lang['th_TH']['Group']['Locked'] = 'ล็อค?';
$lang['th_TH']['Group']['many_many_Members'] = 'สมาชิก';
$lang['th_TH']['Group']['Parent'] = 'กลุ่มแม่';
$lang['th_TH']['Group']['PLURALNAME'] = 'กลุ่ม';
$lang['th_TH']['Group']['SINGULARNAME'] = 'กลุ่ม';
$lang['th_TH']['HtmlEditorField']['FORMATADDR'] = 'ที่อยู่';
$lang['th_TH']['HtmlEditorField']['FORMATH1'] = 'หัวข้อ 1';
$lang['th_TH']['HtmlEditorField']['FORMATH2'] = 'หัวข้อ 2';
$lang['th_TH']['HtmlEditorField']['FORMATH3'] = 'หัวข้อ 3';
$lang['th_TH']['HtmlEditorField']['FORMATH4'] = 'หัวข้อ 4';
$lang['th_TH']['HtmlEditorField']['FORMATH5'] = 'หัวข้อ 15';
$lang['th_TH']['HtmlEditorField']['FORMATH6'] = 'เฮดดิ้ง 6';
$lang['th_TH']['HtmlEditorField']['FORMATP'] = 'Paragraph';
$lang['th_TH']['Image']['PLURALNAME'] = 'ไฟล์';
$lang['th_TH']['Image']['SINGULARNAME'] = 'ไฟล์';
$lang['th_TH']['Member']['belongs_many_many_Groups'] = 'กลุ่ม';
$lang['th_TH']['Member']['db_PasswordExpiry'] = 'วันที่รหัสผ่านหมดอายุ';
$lang['th_TH']['Member']['EMAIL'] = 'Email';
$lang['th_TH']['Member']['INTERFACELANG'] = 'ภาษา';
$lang['th_TH']['Member']['PERSONALDETAILS'] = 'รายละเอียด ส่วนตัว';
$lang['th_TH']['Member']['SINGULARNAME'] = 'สมาชิก';
$lang['th_TH']['Member']['SUBJECTPASSWORDCHANGED'] = 'รหัสผ่านได้รับการเปลี่ยนแปลงแล้ว';
$lang['th_TH']['Member']['SUBJECTPASSWORDRESET'] = 'ลิ้งค์  สำหรับ ตั้งค่า รหัสผ่านใหม่';
$lang['th_TH']['Member']['USERDETAILS'] = 'รายระเอียด ผู้ใช้งาน';
$lang['th_TH']['MemberPassword']['PLURALNAME'] = 'รหัสผ่านสมาชิก';
$lang['th_TH']['MemberPassword']['SINGULARNAME'] = 'รหัสผ่านสมาชิก';
$lang['th_TH']['Page']['PLURALNAME'] = 'หน้า';
$lang['th_TH']['Page']['SINGULARNAME'] = 'หน้า';
$lang['th_TH']['Permission']['PLURALNAME'] = 'สิทธิ์';
$lang['th_TH']['Permission']['SINGULARNAME'] = 'สิทธิ์';
$lang['th_TH']['PermissionRole']['PLURALNAME'] = 'บทบาท';
$lang['th_TH']['PermissionRole']['SINGULARNAME'] = 'บทบาท';
$lang['th_TH']['QueuedEmail']['PLURALNAME'] = 'อีเมลคิว';
$lang['th_TH']['QueuedEmail']['SINGULARNAME'] = 'อีเมลคิว';
$lang['th_TH']['RedirectorPage']['PLURALNAME'] = 'ส่งต่อไปหน้า';
$lang['th_TH']['RedirectorPage']['SINGULARNAME'] = 'ส่งต่อไปหน้า';
$lang['th_TH']['SiteTree']['ACCESSANYONE'] = 'ทุกคน';
$lang['th_TH']['SiteTree']['ACCESSHEADER'] = 'ใครบ้างสามารถดูหน้านี้ได้?';
$lang['th_TH']['SiteTree']['ACCESSLOGGEDIN'] = 'ผู้ใช้งานที่ล็อกอินเข้าใช้งาน';
$lang['th_TH']['SiteTree']['ACCESSONLYTHESE'] = 'บุคคลอื่น (เลือกจากรายการ)';
$lang['th_TH']['SiteTree']['ALLOWCOMMENTS'] = 'อนุญาตให้แสดงความเห็นในหน้านีหรือไม่?';
$lang['th_TH']['SiteTree']['BUTTONSAVEPUBLISH'] = 'บันทึกและเผยแพร่';
$lang['th_TH']['SiteTree']['BUTTONUNPUBLISH'] = 'ไม่เผยแพร่';
$lang['th_TH']['SiteTree']['CHANGETO'] = 'เปลี่ยนเป็น "%s"';
$lang['th_TH']['SiteTree']['Content'] = 'เนื้อหา';
$lang['th_TH']['SiteTree']['EDITANYONE'] = 'ทุกคนที่ล็อกอินสู่ CMS';
$lang['th_TH']['SiteTree']['EDITHEADER'] = 'ใครสามารถแก้ไขหน้านี้ได้?';
$lang['th_TH']['SiteTree']['EDITONLYTHESE'] = 'บุคคลเหล่านี้ (เลือกจากรายการ)';
$lang['th_TH']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'ขอบเขต';
$lang['th_TH']['SiteTree']['HTMLEDITORTITLE'] = 'เนื้อหา';
$lang['th_TH']['SiteTree']['METADESC'] = 'รายละเอียด';
$lang['th_TH']['SiteTree']['METATITLE'] = 'ชื่อเรื่อง';
$lang['th_TH']['SiteTree']['PAGETYPE'] = 'ประเภท เอกสาร';
$lang['th_TH']['SiteTree']['PLURALNAME'] = 'โครงสร้างไซต์';
$lang['th_TH']['SiteTree']['SHOWINSEARCH'] = 'แสดงในผลลัพธ์ของการค้นหาหรือไม่?';
$lang['th_TH']['SiteTree']['SINGULARNAME'] = 'โครงสร้างไซต์';
$lang['th_TH']['SiteTree']['TABACCESS'] = 'เข้าถึง';
$lang['th_TH']['SiteTree']['TABCONTENT'] = 'เนื้อหา';
$lang['th_TH']['SiteTree']['TABMAIN'] = 'หลัก';
$lang['th_TH']['SiteTree']['URLSegment'] = 'URL Segment';
$lang['th_TH']['Translatable']['TRANSLATEPERMISSION'] = 'แปล %s';
$lang['th_TH']['Versioned']['has_many_Versions'] = 'เวอร์ชั่น';
$lang['th_TH']['Widget']['PLURALNAME'] = 'วิดเจ็ท';
$lang['th_TH']['Widget']['SINGULARNAME'] = 'วิดเจ็ท';

?>