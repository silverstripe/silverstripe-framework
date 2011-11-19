<?php

/**
 * Vietnamese (Vietnam) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('vi_VN', $lang) && is_array($lang['vi_VN'])) {
	$lang['vi_VN'] = array_merge($lang['en_US'], $lang['vi_VN']);
} else {
	$lang['vi_VN'] = $lang['en_US'];
}

$lang['vi_VN']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Bạn có thể thay đổi mật khẩu tại';
$lang['vi_VN']['ComplexTableField.ss']['ADDITEM'] = 'Thêm';
$lang['vi_VN']['CompositeDateField']['DAY'] = 'Ngày';
$lang['vi_VN']['CompositeDateField']['DAYJS'] = 'ngày';
$lang['vi_VN']['CompositeDateField']['MONTH'] = 'Tháng';
$lang['vi_VN']['CompositeDateField']['MONTHJS'] = 'tháng';
$lang['vi_VN']['CompositeDateField']['YEARJS'] = 'năm';
$lang['vi_VN']['ContentController']['DRAFT_SITE_ACCESS_RESTRICTION'] = 'Bạn phải đăng nhập để xem trang nháp hoặc trang lưu trữ.  <a href="%s">Trở lại trang cũ.</a>';
$lang['vi_VN']['Date']['DAY'] = 'ngày';
$lang['vi_VN']['Date']['DAYS'] = 'ngày';
$lang['vi_VN']['Date']['HOUR'] = 'giờ';
$lang['vi_VN']['Date']['HOURS'] = 'giờ';
$lang['vi_VN']['Date']['MIN'] = 'phút';
$lang['vi_VN']['Date']['MINS'] = 'phút';
$lang['vi_VN']['Date']['MONTH'] = 'tháng';
$lang['vi_VN']['Date']['MONTHS'] = 'tháng';
$lang['vi_VN']['Date']['SEC'] = 'giây';
$lang['vi_VN']['Date']['SECS'] = 'giây';
$lang['vi_VN']['Date']['YEAR'] = 'năm';
$lang['vi_VN']['Date']['YEARS'] = 'năm';
$lang['vi_VN']['DateField']['TODAY'] = 'hôm nay';
$lang['vi_VN']['DateField']['VALIDATIONJS'] = 'Hãy nhập một giá trị ngày hợp lệ (ngày/tháng/năm)';
$lang['vi_VN']['DateField']['VALIDDATEFORMAT'] = 'Hãy nhập một giá trị ngày hợp lệ (ngày/tháng/năm)';
$lang['vi_VN']['DMYDateField']['VALIDDATEFORMAT'] = 'Hãy nhập một giá trị ngày hợp lệ (ngày-tháng-năm)';
$lang['vi_VN']['DropdownField']['CHOOSE'] = '(chọn)';
$lang['vi_VN']['ErrorPage']['400'] = '400 - Bad Request';
$lang['vi_VN']['ErrorPage']['401'] = '401 - Unauthorized';
$lang['vi_VN']['ErrorPage']['403'] = '403 - Forbidden';
$lang['vi_VN']['ErrorPage']['404'] = '404 - Not Found';
$lang['vi_VN']['ErrorPage']['405'] = '405 - Method Not Allowed';
$lang['vi_VN']['ErrorPage']['406'] = '406 - Not Acceptable';
$lang['vi_VN']['ErrorPage']['407'] = '407 - Proxy Authentication Required';
$lang['vi_VN']['ErrorPage']['408'] = '408 - Request Timeout';
$lang['vi_VN']['ErrorPage']['409'] = '409 - Conflict';
$lang['vi_VN']['ErrorPage']['410'] = '410 - Gone';
$lang['vi_VN']['ErrorPage']['411'] = '411 - Length Required';
$lang['vi_VN']['ErrorPage']['412'] = '412 - Precondition Failed';
$lang['vi_VN']['ErrorPage']['413'] = '413 - Request Entity Too Large';
$lang['vi_VN']['ErrorPage']['414'] = '414 - Request-URI Too Long';
$lang['vi_VN']['ErrorPage']['415'] = '415 - Unsupported Media Type';
$lang['vi_VN']['ErrorPage']['416'] = '416 - Request Range Not Satisfiable';
$lang['vi_VN']['ErrorPage']['417'] = '417 - Expectation Failed';
$lang['vi_VN']['ErrorPage']['500'] = '500 - Internal Server Error';
$lang['vi_VN']['ErrorPage']['501'] = '501 - Not Implemented';
$lang['vi_VN']['ErrorPage']['502'] = '502 - Bad Gateway';
$lang['vi_VN']['ErrorPage']['503'] = '503 - Service Unavailable';
$lang['vi_VN']['ErrorPage']['504'] = '504 - Gateway Timeout';
$lang['vi_VN']['ErrorPage']['505'] = '505 - HTTP Version Not Supported';
$lang['vi_VN']['ErrorPage']['CODE'] = 'Mã lỗi';
$lang['vi_VN']['ErrorPage']['DEFAULTERRORPAGECONTENT'] = '<p>Bạn truy cập vào một trang không tồn tại</p>
<p>Xin vui lòng kiểm tra lại đường dẩn hoặc quay lại trang chủ</p>';
$lang['vi_VN']['ErrorPage']['DEFAULTERRORPAGETITLE'] = 'Không tìm thấy';
$lang['vi_VN']['HtmlEditorField']['FORMATADDR'] = 'Địa Chỉ';
$lang['vi_VN']['HtmlEditorField']['FORMATH1'] = 'Heading 1';
$lang['vi_VN']['HtmlEditorField']['FORMATH2'] = 'Heading 2';
$lang['vi_VN']['HtmlEditorField']['FORMATH3'] = 'Heading 3';
$lang['vi_VN']['HtmlEditorField']['FORMATH4'] = 'Heading 4';
$lang['vi_VN']['HtmlEditorField']['FORMATH5'] = 'Heading 5';
$lang['vi_VN']['HtmlEditorField']['FORMATH6'] = 'Heading 6';
$lang['vi_VN']['HtmlEditorField']['FORMATP'] = 'Paragraph';
$lang['vi_VN']['Member']['EMAIL'] = 'Email';
$lang['vi_VN']['Member']['INTERFACELANG'] = 'Ngôn ngữ';
$lang['vi_VN']['Member']['PERSONALDETAILS'] = 'Thông tin cá nhân';
$lang['vi_VN']['Member']['SUBJECTPASSWORDCHANGED'] = 'Thay đổi mật khẩu';
$lang['vi_VN']['Member']['SUBJECTPASSWORDRESET'] = 'Quên mật khẩu';
$lang['vi_VN']['Member']['USERDETAILS'] = 'Thông tin thành viên';
$lang['vi_VN']['RedirectorPage']['HASBEENSETUP'] = 'Một trang di chuyển chưa cấu hình đích di chuyển đến.';
$lang['vi_VN']['RedirectorPage']['HEADER'] = 'Trang này sẽ di chuyển người dùng đến một trang khác';
$lang['vi_VN']['RedirectorPage']['OTHERURL'] = 'Đường dẩn website khác';
$lang['vi_VN']['RedirectorPage']['REDIRECTTO'] = 'Di chuyển đến';
$lang['vi_VN']['RedirectorPage']['REDIRECTTOEXTERNAL'] = 'Website khác';
$lang['vi_VN']['RedirectorPage']['REDIRECTTOPAGE'] = 'Một trang trên website';
$lang['vi_VN']['RedirectorPage']['YOURPAGE'] = 'Trang';
$lang['vi_VN']['SiteTree']['ACCESSANYONE'] = 'Bất cứ ai';
$lang['vi_VN']['SiteTree']['ACCESSHEADER'] = 'Ai có thể xem trang này ?';
$lang['vi_VN']['SiteTree']['ACCESSLOGGEDIN'] = 'Người dùng đã đăng nhập';
$lang['vi_VN']['SiteTree']['ACCESSONLYTHESE'] = 'Chỉ những người sau đây (chọn từ danh sách)';
$lang['vi_VN']['SiteTree']['ADDEDTODRAFT'] = 'Thêm vào trang nháp thành công';
$lang['vi_VN']['SiteTree']['ALLOWCOMMENTS'] = 'Cho phép bình luận trên trang này?';
$lang['vi_VN']['SiteTree']['BUTTONCANCELDRAFT'] = 'Hủy thay đổi nháp';
$lang['vi_VN']['SiteTree']['BUTTONCANCELDRAFTDESC'] = 'Xóa khỏi mục nháp và khôi phục lại trang gốc';
$lang['vi_VN']['SiteTree']['BUTTONSAVEPUBLISH'] = 'Lưu & Công bố';
$lang['vi_VN']['SiteTree']['BUTTONUNPUBLISH'] = 'Ẩn';
$lang['vi_VN']['SiteTree']['BUTTONUNPUBLISHDESC'] = 'Xóa trang này khỏi trang hoạt động';
$lang['vi_VN']['SiteTree']['CHANGETO'] = 'Thay đổi thành ';
$lang['vi_VN']['SiteTree']['DEFAULTABOUTCONTENT'] = '<p>Bạn có thể điền vào thông tin trang này với nội dung của bạn, hoặc xóa trang này và tạo một trang riêng phù hợp hơn.<br /></p>';
$lang['vi_VN']['SiteTree']['DEFAULTABOUTTITLE'] = 'Giới thiệu';
$lang['vi_VN']['SiteTree']['DEFAULTCONTACTCONTENT'] = '<p>Bạn có thể điền vào thông tin trang này với nội dung của bạn, hoặc xóa trang này và tạo một trang riêng phù hợp hơn.<br /></p>';
$lang['vi_VN']['SiteTree']['DEFAULTCONTACTTITLE'] = 'Liên hệ';
$lang['vi_VN']['SiteTree']['DEFAULTHOMETITLE'] = 'Trang chủ';
$lang['vi_VN']['SiteTree']['EDITANYONE'] = 'Bất cứ ai có thể đăng nhập vào phần quản lý';
$lang['vi_VN']['SiteTree']['EDITHEADER'] = 'Ai có thể thay đổi trang này ?';
$lang['vi_VN']['SiteTree']['EDITONLYTHESE'] = 'Chỉ những người sau đây (chọn từ danh sách)';
$lang['vi_VN']['SiteTree']['HASBROKENLINKS'] = 'Trang này có liên kết hỏng.';
$lang['vi_VN']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Tên miền';
$lang['vi_VN']['SiteTree']['HTMLEDITORTITLE'] = 'Nội dung';
$lang['vi_VN']['SiteTree']['MENUTITLE'] = 'Nhãn';
$lang['vi_VN']['SiteTree']['METADESC'] = 'Mô tả';
$lang['vi_VN']['SiteTree']['METAEXTRA'] = 'Thẻ thông tin bổ sung';
$lang['vi_VN']['SiteTree']['METAHEADER'] = 'Thẻ hổ trợ tìm kiếm';
$lang['vi_VN']['SiteTree']['METAKEYWORDS'] = 'Từ khóa';
$lang['vi_VN']['SiteTree']['METATITLE'] = 'Tiêu đề';
$lang['vi_VN']['SiteTree']['MODIFIEDONDRAFT'] = 'Đã thay đổi trên trang nháp';
$lang['vi_VN']['SiteTree']['NOTEUSEASHOMEPAGE'] = 'Dùng trang này như \'Trang Chủ\' cho các tên miền sau:  (cách nhau bằng dấu \',\' )';
$lang['vi_VN']['SiteTree']['PAGESLINKING'] = 'Các trang có liên kết đến trang này:';
$lang['vi_VN']['SiteTree']['PAGETITLE'] = 'Tên trang';
$lang['vi_VN']['SiteTree']['PAGETYPE'] = 'Loại';
$lang['vi_VN']['SiteTree']['REMOVEDFROMDRAFT'] = 'Xóa khỏi trang nháp thành công';
$lang['vi_VN']['SiteTree']['SHOWINMENUS'] = 'Hiển thị trên menu?';
$lang['vi_VN']['SiteTree']['SHOWINSEARCH'] = 'Hiển thị khi tìm kiếm?';
$lang['vi_VN']['SiteTree']['TABACCESS'] = 'Quyền truy cập';
$lang['vi_VN']['SiteTree']['TABBACKLINKS'] = 'Liên kết';
$lang['vi_VN']['SiteTree']['TABBEHAVIOUR'] = 'Hoạt động';
$lang['vi_VN']['SiteTree']['TABCONTENT'] = 'Thông tin';
$lang['vi_VN']['SiteTree']['TABMAIN'] = 'Nội dung';
$lang['vi_VN']['SiteTree']['TABREPORTS'] = 'Báo cáo';
$lang['vi_VN']['SiteTree']['TODOHELP'] = '<p>Bạn có thể dùng mục này để ghi nhận các công việc cần hoàn thành cho trang của bạn. Để xem tất cả các trang với thông tin các việc cần làm, mở cửa sổ the \'Báo cáo\' bên trái và chọn mục \'To Do\'</p>';
$lang['vi_VN']['SiteTree']['URL'] = 'Đường dẫn';
$lang['vi_VN']['SiteTree']['VALIDATIONURLSEGMENT1'] = 'Một trang khác đang dùng đường dẫn này. Đường dẫn cho các trang phải không được trùng nhau.';
$lang['vi_VN']['SiteTree']['VALIDATIONURLSEGMENT2'] = 'Đường dẩn chỉ có thể chứa ký tự, số và dấu gạch ngang "-".';

?>