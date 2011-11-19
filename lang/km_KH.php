<?php

/**
 * Khmer (Cambodia) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('km_KH', $lang) && is_array($lang['km_KH'])) {
	$lang['km_KH'] = array_merge($lang['en_US'], $lang['km_KH']);
} else {
	$lang['km_KH'] = $lang['en_US'];
}

$lang['km_KH']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'អ្នកបានផ្លាស់ប្តូរពាក្យសំងាត់សំរាប់';
$lang['km_KH']['ComplexTableField.ss']['ADDITEM'] = 'បញ្ចូល %s';
$lang['km_KH']['ConfirmedFormAction']['CONFIRMATION'] = 'តើអ្នកច្បាស់ទេ?';
$lang['km_KH']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'ផ្លាស់ប្តូរពាក្យសំងាត់';
$lang['km_KH']['ContentController']['DRAFT_SITE_ACCESS_RESTRICTION'] = 'ដើម្បីមើលទំព័រប្រៀងសូម ចូលកាន់ប្រព័ន្ធគ្រប់គ្រងដោយប្រើយឈ្មោះ និងពាក្យសំងាត់របស់អ្នក សូមចុចត្រនេះ <a href="%s"> ដើម្បីត្រលប់ទៅ កាន់ទំព័ររួចរាល់។</a>';
$lang['km_KH']['DataObject']['PLURALNAME'] = 'Data Objects';
$lang['km_KH']['DataObject']['SINGULARNAME'] = 'Data Objects';
$lang['km_KH']['Date']['DAY'] = 'ថ្ងៃ';
$lang['km_KH']['Date']['DAYS'] = 'ថ្ងៃ';
$lang['km_KH']['Date']['HOUR'] = 'ម៉ោង';
$lang['km_KH']['Date']['HOURS'] = 'ម៉ោង';
$lang['km_KH']['Date']['MIN'] = 'នាទី';
$lang['km_KH']['Date']['MINS'] = 'នាទី';
$lang['km_KH']['Date']['MONTH'] = 'ខែ';
$lang['km_KH']['Date']['MONTHS'] = 'ខែ';
$lang['km_KH']['Date']['SEC'] = 'វិនាទី';
$lang['km_KH']['Date']['SECS'] = 'វិនាទី';
$lang['km_KH']['Date']['TIMEDIFFAGO'] = '%s មុន';
$lang['km_KH']['Date']['TIMEDIFFAWAY'] = '%s ទៀត';
$lang['km_KH']['Date']['YEAR'] = 'ឆ្នាំ';
$lang['km_KH']['Date']['YEARS'] = 'ឆ្នាំ';
$lang['km_KH']['DropdownField']['CHOOSE'] = '(ជ្រើសរើស)';
$lang['km_KH']['ErrorPage']['PLURALNAME'] = 'ទំព័រមានបញ្ហា';
$lang['km_KH']['ErrorPage']['SINGULARNAME'] = 'ទំព័រមានបញ្ហា';
$lang['km_KH']['File']['INVALIDEXTENSION'] = 'Extension មិនអនុញ្ញាត្តិ (សុពលភាព: %s)';
$lang['km_KH']['File']['PLURALNAME'] = 'ឯកសារ';
$lang['km_KH']['File']['SINGULARNAME'] = 'ឯកសារ';
$lang['km_KH']['File']['TOOLARGE'] = 'ឯកសារនេះធំពេក, អាចដាក់បានត្រឹមតែ %s';
$lang['km_KH']['Folder']['PLURALNAME'] = 'ឯកសារ';
$lang['km_KH']['Folder']['SINGULARNAME'] = 'ឯកសារ';
$lang['km_KH']['Group']['Code'] = 'លេខកូដក្រុម';
$lang['km_KH']['Group']['has_many_Permissions'] = 'ការអនុញ្ញាតិ្ត';
$lang['km_KH']['Group']['Locked'] = 'មិនអាចប្រើ';
$lang['km_KH']['Group']['many_many_Members'] = 'សមាជិក';
$lang['km_KH']['Group']['Parent'] = 'ចំណាត់ក្រុមដើម';
$lang['km_KH']['Group']['PLURALNAME'] = 'ចំណាត់ក្រុម';
$lang['km_KH']['Group']['SINGULARNAME'] = 'ចំណាត់ក្រុម';
$lang['km_KH']['HtmlEditorField']['BUTTONINSERTFLASH'] = 'បញ្ចូល Flash';
$lang['km_KH']['HtmlEditorField']['BUTTONINSERTIMAGE'] = 'បញ្ចូលរូបភាព';
$lang['km_KH']['HtmlEditorField']['CREATEFOLDER'] = 'បង្កើតកន្លែងដាក់ឯកសារថ្មី';
$lang['km_KH']['HtmlEditorField']['FOLDERCANCEL'] = 'ចាកចេញ';
$lang['km_KH']['HtmlEditorField']['FORMATADDR'] = 'អាសយដ្ឋាន';
$lang['km_KH']['HtmlEditorField']['FORMATH1'] = 'អក្សរធំ 1';
$lang['km_KH']['HtmlEditorField']['FORMATH2'] = 'អក្សរធំ 2';
$lang['km_KH']['HtmlEditorField']['FORMATH3'] = 'អក្សរធំ 3';
$lang['km_KH']['HtmlEditorField']['FORMATH4'] = 'អក្សរធំ 4';
$lang['km_KH']['HtmlEditorField']['FORMATH5'] = 'អក្សរធំ 5';
$lang['km_KH']['HtmlEditorField']['FORMATH6'] = 'អក្សរធំ 6';
$lang['km_KH']['HtmlEditorField']['FORMATP'] = 'អត្ថបទ';
$lang['km_KH']['HtmlEditorField']['FORMATPRE'] = 'កែសំរួលមុន';
$lang['km_KH']['HtmlEditorField']['OK'] = 'យល់ព្រម';
$lang['km_KH']['HtmlEditorField']['UPLOAD'] = 'បញ្ជូន';
$lang['km_KH']['Image']['PLURALNAME'] = 'ឯកសារ';
$lang['km_KH']['Image']['SINGULARNAME'] = 'ឯកសារ';
$lang['km_KH']['LoginAttempt']['PLURALNAME'] = 'ចំនួនព្យាយាមចូល';
$lang['km_KH']['LoginAttempt']['SINGULARNAME'] = 'ព្យាយាមចូល';
$lang['km_KH']['Member']['belongs_many_many_Groups'] = 'ចំណាត់ក្រុម';
$lang['km_KH']['Member']['db_LockedOutUntil'] = 'ដោះចេញរហូតដល់';
$lang['km_KH']['Member']['db_PasswordExpiry'] = 'កាលបរិច្ឆេទផុតកំណត់ពាក្យសំងាត់';
$lang['km_KH']['Member']['EMAIL'] = 'អ៊ីម៉េល';
$lang['km_KH']['Member']['INTERFACELANG'] = 'ភាសាប្រើសំរាប់ទំព័រមុខ';
$lang['km_KH']['Member']['PERSONALDETAILS'] = 'ព័ត៌មានលំអិតផ្ទាល់ខ្លួន';
$lang['km_KH']['Member']['PLURALNAME'] = 'សមាជិក';
$lang['km_KH']['Member']['SINGULARNAME'] = 'សមាជិក';
$lang['km_KH']['Member']['SUBJECTPASSWORDCHANGED'] = 'ពាក្យសំងាត់របស់អ្នកបានផ្លាស់ប្តូរ';
$lang['km_KH']['Member']['SUBJECTPASSWORDRESET'] = 'លីងសំរាប់ប្តូរពាក្យសំងាត់របស់អ្នក';
$lang['km_KH']['Member']['USERDETAILS'] = 'ព័ត៌មានលំអិត';
$lang['km_KH']['MemberPassword']['PLURALNAME'] = 'ពាក្យសំងាត់របស់សមាជិក';
$lang['km_KH']['MemberPassword']['SINGULARNAME'] = 'ពាក្យសំងាត់របស់សមាជិក';
$lang['km_KH']['NullableField']['IsNullLabel'] = 'ទទេ';
$lang['km_KH']['Page']['PLURALNAME'] = 'ទំព័រ';
$lang['km_KH']['Page']['SINGULARNAME'] = 'ទំព័រ';
$lang['km_KH']['Permission']['PLURALNAME'] = 'ការអនុញ្ញាត្តិ';
$lang['km_KH']['Permission']['SINGULARNAME'] = 'ការអនុញ្ញាត្តិ';
$lang['km_KH']['QueuedEmail']['PLURALNAME'] = 'អ៊ីម៉េលក្នុងជួររងចាំ';
$lang['km_KH']['QueuedEmail']['SINGULARNAME'] = 'អ៊ីម៉េលក្នុងជួររងចាំ';
$lang['km_KH']['RedirectorPage']['PLURALNAME'] = 'ទំព័រភ្ជាប់ផ្ទាល់';
$lang['km_KH']['RedirectorPage']['SINGULARNAME'] = 'ទំព័រភ្ជាប់ផ្ទាល់';
$lang['km_KH']['Security']['ALREADYLOGGEDIN'] = 'អ្នកមិនអាចមើលទំព័រនេះបានទេ។ សូមប្រើប្រាស់ព័ត៌មានសំរាប់ថ្មី មួយទៀតសំរាប់ចូលមើល។ សូមចូលតាម <a href="%s">';
$lang['km_KH']['SiteTree']['CHANGETO'] = 'ផ្លាស់ប្តូរទៅ "%s"';
$lang['km_KH']['SiteTree']['Content'] = 'មាតិការ';
$lang['km_KH']['SiteTree']['has_one_Parent'] = 'ទំព័រមេ';
$lang['km_KH']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'ដូមេន';
$lang['km_KH']['SiteTree']['HTMLEDITORTITLE'] = 'អត្ថបទ';
$lang['km_KH']['SiteTree']['PAGETYPE'] = 'ប្រភេទទំព័រ';
$lang['km_KH']['SiteTree']['PLURALNAME'] = 'សម្ព័ន្ធទំព័រ';
$lang['km_KH']['SiteTree']['SINGULARNAME'] = 'សម្ព័ន្ធទំព័រ';
$lang['km_KH']['SiteTree']['URLSegment'] = 'កំណាត់ URL';
$lang['km_KH']['Versioned']['has_many_Versions'] = 'ជំនាន់';
$lang['km_KH']['VirtualPage']['PLURALNAME'] = 'ទំព័រដូច';
$lang['km_KH']['VirtualPage']['SINGULARNAME'] = 'ទំព័រដូច';
$lang['km_KH']['Widget']['PLURALNAME'] = 'វីហ្គេត';
$lang['km_KH']['Widget']['SINGULARNAME'] = 'វីហ្គេត';
$lang['km_KH']['WidgetArea']['PLURALNAME'] = 'ទីតាំវីហ្គេត';
$lang['km_KH']['WidgetArea']['SINGULARNAME'] = 'ទីតាំវីហ្គេត';

?>