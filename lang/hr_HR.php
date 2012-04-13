<?php

/**
 * Croatian (Croatia) language pack
 * @package framework
 * @subpackage i18n
 */

i18n::include_locale_file(FRAMEWORK_DIR, 'en_US');

global $lang;

if(array_key_exists('hr_HR', $lang) && is_array($lang['hr_HR'])) {
	$lang['hr_HR'] = array_merge($lang['en_US'], $lang['hr_HR']);
} else {
	$lang['hr_HR'] = $lang['en_US'];
}

$lang['hr_HR']['BasicAuth']['ENTERINFO'] = 'Unesite korisničko ime i lozinu';
$lang['hr_HR']['BasicAuth']['ERRORNOTADMIN'] = 'Korisnik nije administrator';
$lang['hr_HR']['BasicAuth']['ERRORNOTREC'] = 'Korisničko ime / lozinka nije prepoznata';
$lang['hr_HR']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Promjenili ste lozinku za ';
$lang['hr_HR']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT2'] = 'Za prijavu koristite slijedeće podatke';
$lang['hr_HR']['ChangePasswordEmail.ss']['HELLO'] = 'Pozdrav';
$lang['hr_HR']['ComplexTableField.ss']['ADDITEM'] = 'Dodaj';
$lang['hr_HR']['ComplexTableField.ss']['NOITEMSFOUND'] = 'Ništa nije pronađeno';
$lang['hr_HR']['ComplexTableField.ss']['SORTASC'] = 'Sortiraj (ascending)';
$lang['hr_HR']['ComplexTableField.ss']['SORTDESC'] = 'Sortiraj (descending)';
$lang['hr_HR']['ComplexTableField_popup.ss']['NEXT'] = 'Slijedeći';
$lang['hr_HR']['ComplexTableField_popup.ss']['PREVIOUS'] = 'Prethodni';
$lang['hr_HR']['ConfirmedFormAction']['CONFIRMATION'] = 'Jeste li sigurni?';
$lang['hr_HR']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'Promjeni Lozinku';
$lang['hr_HR']['ContentController']['DRAFT_SITE_ACCESS_RESTRICTION'] = 'Morate biti prijavljeni sa vašom CMS zaporkom kako bi ste mogli vidjeti privremeni ili arhivirani sadržaj. <a href="%s">Kliknite kako bi ste se vratili na objavljen dio stranice</a>';
$lang['hr_HR']['Controller']['FILE'] = 'Datoteka';
$lang['hr_HR']['Controller']['IMAGE'] = 'Slika';
$lang['hr_HR']['DataObject']['PLURALNAME'] = 'Podatkovni Objekti';
$lang['hr_HR']['DataObject']['SINGULARNAME'] = 'Podatkovni Objekt';
$lang['hr_HR']['Date']['DAY'] = 'dan';
$lang['hr_HR']['Date']['DAYS'] = 'dani';
$lang['hr_HR']['Date']['HOUR'] = 'sat';
$lang['hr_HR']['Date']['HOURS'] = 'sati';
$lang['hr_HR']['Date']['MIN'] = 'minute';
$lang['hr_HR']['Date']['MINS'] = 'minute';
$lang['hr_HR']['Date']['MONTH'] = 'mjesec';
$lang['hr_HR']['Date']['MONTHS'] = 'mjeseci';
$lang['hr_HR']['Date']['SEC'] = 'sekunde';
$lang['hr_HR']['Date']['SECS'] = 'sekundi';
$lang['hr_HR']['Date']['TIMEDIFFAGO'] = 'Prije %s';
$lang['hr_HR']['Date']['TIMEDIFFAWAY'] = 'Prije %s';
$lang['hr_HR']['Date']['YEAR'] = 'godina';
$lang['hr_HR']['Date']['YEARS'] = 'godine';
$lang['hr_HR']['DateField']['VALIDDATEFORMAT'] = 'Molim upišite ispravan format datuma (DD/MM/GGGG).';
$lang['hr_HR']['DropdownField']['CHOOSE'] = '(Odaberi)';
$lang['hr_HR']['EmailField']['VALIDATION'] = 'Upišite email adresu';
$lang['hr_HR']['ErrorPage']['CODE'] = 'Error code';
$lang['hr_HR']['ErrorPage']['PLURALNAME'] = 'Stranice Grešaka';
$lang['hr_HR']['ErrorPage']['SINGULARNAME'] = 'Stranica Greške';
$lang['hr_HR']['File']['INVALIDEXTENSION'] = 'Nastavak nije dozvoljen (ispravno: %s)';
$lang['hr_HR']['File']['PLURALNAME'] = 'Datoteke';
$lang['hr_HR']['File']['SINGULARNAME'] = 'Datoteka';
$lang['hr_HR']['File']['TOOLARGE'] = 'Prevelika datoteka, dozvoljeni maksimum je %s.';
$lang['hr_HR']['Folder']['PLURALNAME'] = 'Datoteke';
$lang['hr_HR']['Folder']['SINGULARNAME'] = 'Datoteka';
$lang['hr_HR']['ForgotPasswordEmail.ss']['HELLO'] = 'Pozdrav';
$lang['hr_HR']['ForgotPasswordEmail.ss']['TEXT1'] = 'Ovdje je vaš';
$lang['hr_HR']['ForgotPasswordEmail.ss']['TEXT2'] = 'link za resetiranje zaporke';
$lang['hr_HR']['ForgotPasswordEmail.ss']['TEXT3'] = 'za';
$lang['hr_HR']['Form']['DATENOTSET'] = '(Datum nije postavljen)';
$lang['hr_HR']['Form']['FIELDISREQUIRED'] = '%s je obavezan';
$lang['hr_HR']['Form']['LANGAOTHER'] = 'Drugi jezici';
$lang['hr_HR']['Form']['LANGAVAIL'] = 'Dostupni jezici';
$lang['hr_HR']['Form']['NOTSET'] = '(nije postavljeno)';
$lang['hr_HR']['Form']['VALIDATIONALLDATEVALUES'] = 'Provjerite jeste li upisali točne datume ';
$lang['hr_HR']['Form']['VALIDATIONBANKACC'] = 'Unesite ispravan \'bank number\'';
$lang['hr_HR']['Form']['VALIDATIONCREDITNUMBER'] = 'Uvjerite se da ste unjeli %s broj kreditne kartice ispravno';
$lang['hr_HR']['Form']['VALIDATIONFAILED'] = 'Provjera nije uspjela';
$lang['hr_HR']['Form']['VALIDATIONNOTUNIQUE'] = 'Unesena vrijednost nije unikatna';
$lang['hr_HR']['Form']['VALIDATIONPASSWORDSDONTMATCH'] = 'Lozine se ne slažu';
$lang['hr_HR']['Form']['VALIDATIONPASSWORDSNOTEMPTY'] = 'Lozinke moraju imati najmanje jedan broj i jedan alfanumerički znak';
$lang['hr_HR']['Form']['VALIDATIONSTRONGPASSWORD'] = 'Lozinke moraju imati najmanje jedan broj i jedan alfanumerički znak';
$lang['hr_HR']['Form']['VALIDCURRENCY'] = 'Unesite ispravnu valutu';
$lang['hr_HR']['GhostPage']['NOLINKED'] = 'Stranica nije povezana';
$lang['hr_HR']['GhostPage']['PLURALNAME'] = 'Lažne stranice';
$lang['hr_HR']['GhostPage']['SINGULARNAME'] = 'Lažna stranica';
$lang['hr_HR']['Group']['Code'] = 'Krupni kod';
$lang['hr_HR']['Group']['has_many_Permissions'] = 'Dozvole';
$lang['hr_HR']['Group']['Locked'] = 'Zaključano?';
$lang['hr_HR']['Group']['many_many_Members'] = 'Članovi';
$lang['hr_HR']['Group']['Parent'] = 'Roditeljska grupa';
$lang['hr_HR']['Group']['PLURALNAME'] = 'Grupe';
$lang['hr_HR']['Group']['SINGULARNAME'] = 'Grupa';
$lang['hr_HR']['GSTNumberField']['VALIDATION'] = 'Please enter a valid GST Number';
$lang['hr_HR']['HtmlEditorField']['ALTTEXT'] = 'Opis';
$lang['hr_HR']['HtmlEditorField']['ANCHOR'] = 'Ubaci anchor';
$lang['hr_HR']['HtmlEditorField']['BULLETLIST'] = 'Bullet-point lista (UL)';
$lang['hr_HR']['HtmlEditorField']['BUTTONALIGNCENTER'] = 'Središnje poravnanje';
$lang['hr_HR']['HtmlEditorField']['BUTTONALIGNJUSTIFY'] = 'Justify';
$lang['hr_HR']['HtmlEditorField']['BUTTONALIGNLEFT'] = 'Lijevo poravnanje';
$lang['hr_HR']['HtmlEditorField']['BUTTONALIGNRIGHT'] = 'Desno poravnanje';
$lang['hr_HR']['HtmlEditorField']['BUTTONBOLD'] = 'Bold (Ctrl+B)';
$lang['hr_HR']['HtmlEditorField']['BUTTONINSERTFLASH'] = 'Umetni Flash';
$lang['hr_HR']['HtmlEditorField']['BUTTONINSERTIMAGE'] = 'Umetni sliku';
$lang['hr_HR']['HtmlEditorField']['BUTTONINSERTLINK'] = 'Ubaci vezu';
$lang['hr_HR']['HtmlEditorField']['BUTTONITALIC'] = 'Italic (Ctrl+I)';
$lang['hr_HR']['HtmlEditorField']['BUTTONREMOVELINK'] = 'Obriši vezu';
$lang['hr_HR']['HtmlEditorField']['BUTTONSTRIKE'] = 'strikethrough';
$lang['hr_HR']['HtmlEditorField']['BUTTONUNDERLINE'] = 'Underline (Ctrl+U)';
$lang['hr_HR']['HtmlEditorField']['CHARMAP'] = 'Ubaci simbol';
$lang['hr_HR']['HtmlEditorField']['COPY'] = 'Copy (Ctrl+C)';
$lang['hr_HR']['HtmlEditorField']['CREATEFOLDER'] = 'izradite mapu ( direktorij )';
$lang['hr_HR']['HtmlEditorField']['CSSCLASS'] = 'Poravnanje / Stil';
$lang['hr_HR']['HtmlEditorField']['CSSCLASSCENTER'] = 'Centralno';
$lang['hr_HR']['HtmlEditorField']['CSSCLASSLEFT'] = 'Lijevo, sa okruženjem teksta.';
$lang['hr_HR']['HtmlEditorField']['CSSCLASSRIGHT'] = 'Desno, sa okruženjem teksta';
$lang['hr_HR']['HtmlEditorField']['CUT'] = 'Cut (Ctrl+X)';
$lang['hr_HR']['HtmlEditorField']['DELETECOL'] = 'Obriši stupac';
$lang['hr_HR']['HtmlEditorField']['DELETEROW'] = 'Obriši redak';
$lang['hr_HR']['HtmlEditorField']['EDITCODE'] = 'Editiraj HTML';
$lang['hr_HR']['HtmlEditorField']['EMAIL'] = 'Email adresa';
$lang['hr_HR']['HtmlEditorField']['FILE'] = 'Datoteka';
$lang['hr_HR']['HtmlEditorField']['FLASH'] = 'Ubaci flash';
$lang['hr_HR']['HtmlEditorField']['FOLDER'] = 'Direktorij';
$lang['hr_HR']['HtmlEditorField']['FOLDERCANCEL'] = 'prekini';
$lang['hr_HR']['HtmlEditorField']['FORMATADDR'] = 'Adresa';
$lang['hr_HR']['HtmlEditorField']['FORMATH1'] = 'Naslov 1 (h1)';
$lang['hr_HR']['HtmlEditorField']['FORMATH2'] = 'Naslov 2 (h2)';
$lang['hr_HR']['HtmlEditorField']['FORMATH3'] = 'Naslov 3 (h3)';
$lang['hr_HR']['HtmlEditorField']['FORMATH4'] = 'Naslov 4 (h4)';
$lang['hr_HR']['HtmlEditorField']['FORMATH5'] = 'Naslov 5 (h5)';
$lang['hr_HR']['HtmlEditorField']['FORMATH6'] = 'Naslov 6 (h6)';
$lang['hr_HR']['HtmlEditorField']['FORMATP'] = 'Paragraf';
$lang['hr_HR']['HtmlEditorField']['FORMATPRE'] = 'Predformatirano';
$lang['hr_HR']['HtmlEditorField']['HR'] = 'Ubaci horizontalnu liniju';
$lang['hr_HR']['HtmlEditorField']['IMAGE'] = 'Ubaci sliku';
$lang['hr_HR']['HtmlEditorField']['IMAGEDIMENSIONS'] = 'Dimenzije';
$lang['hr_HR']['HtmlEditorField']['IMAGEHEIGHTPX'] = 'Visina';
$lang['hr_HR']['HtmlEditorField']['IMAGEWIDTHPX'] = 'Širina';
$lang['hr_HR']['HtmlEditorField']['INDENT'] = 'Povećaj uvlačenje';
$lang['hr_HR']['HtmlEditorField']['INSERTCOLAFTER'] = 'Ubaci stupac iza';
$lang['hr_HR']['HtmlEditorField']['INSERTCOLBEF'] = 'Upaci stupac ispred';
$lang['hr_HR']['HtmlEditorField']['INSERTROWAFTER'] = 'Ubaci redak ispod';
$lang['hr_HR']['HtmlEditorField']['INSERTROWBEF'] = 'Ubaci redak ispred';
$lang['hr_HR']['HtmlEditorField']['INSERTTABLE'] = 'Ubaci tablicu';
$lang['hr_HR']['HtmlEditorField']['LINK'] = 'Ubaci/editiraj link za označeni tekst';
$lang['hr_HR']['HtmlEditorField']['LINKDESCR'] = 'Opis veze';
$lang['hr_HR']['HtmlEditorField']['LINKEMAIL'] = 'Email adresa';
$lang['hr_HR']['HtmlEditorField']['LINKEXTERNAL'] = 'Na drugom webu';
$lang['hr_HR']['HtmlEditorField']['LINKFILE'] = 'Downlad datoteke';
$lang['hr_HR']['HtmlEditorField']['LINKINTERNAL'] = 'Stranicu na ovom webu';
$lang['hr_HR']['HtmlEditorField']['LINKOPENNEWWIN'] = 'Otvori vezu (link) u novom prozoru?';
$lang['hr_HR']['HtmlEditorField']['LINKTO'] = 'Poveži na';
$lang['hr_HR']['HtmlEditorField']['OK'] = 'uredu';
$lang['hr_HR']['HtmlEditorField']['OL'] = 'Numbered list (OL)';
$lang['hr_HR']['HtmlEditorField']['OUTDENT'] = 'Smanji uvlačenje';
$lang['hr_HR']['HtmlEditorField']['PAGE'] = 'Stranica';
$lang['hr_HR']['HtmlEditorField']['PASTE'] = 'Paste (Ctrl+V)';
$lang['hr_HR']['HtmlEditorField']['REDO'] = 'Redo (Ctrl+Y)';
$lang['hr_HR']['HtmlEditorField']['UNDO'] = 'Undo (Ctrl+Z)';
$lang['hr_HR']['HtmlEditorField']['UNLINK'] = 'Obriši link';
$lang['hr_HR']['HtmlEditorField']['UPLOAD'] = 'postavi';
$lang['hr_HR']['HtmlEditorField']['URL'] = 'URL';
$lang['hr_HR']['HtmlEditorField']['VISUALAID'] = 'Pokaži/Sakrij vodilice';
$lang['hr_HR']['Image']['PLURALNAME'] = 'Datoteke';
$lang['hr_HR']['Image']['SINGULARNAME'] = 'Datoteka';
$lang['hr_HR']['ImageField']['NOTEADDIMAGES'] = 'Slike možete dodavati nakon što spremite prvi put.';
$lang['hr_HR']['ImageUplaoder']['ONEFROMFILESTORE'] = 'Sa jednim iz \'file store\' - a';
$lang['hr_HR']['ImageUploader']['ATTACH'] = 'Priloži %s';
$lang['hr_HR']['ImageUploader']['DELETE'] = 'Obriši %s';
$lang['hr_HR']['ImageUploader']['FROMCOMPUTER'] = 'Sa Vašeg računala';
$lang['hr_HR']['ImageUploader']['FROMFILESTORE'] = 'Iz \'file store\' - a';
$lang['hr_HR']['ImageUploader']['ONEFROMCOMPUTER'] = 'Sa nekim sa vašeg računala';
$lang['hr_HR']['ImageUploader']['REALLYDELETE'] = 'Želite je obrisati %s?';
$lang['hr_HR']['ImageUploader']['REPLACE'] = 'Zamijeni %s';
$lang['hr_HR']['Image_iframe.ss']['TITLE'] = 'Iframe za upload slike';
$lang['hr_HR']['LoginAttempt']['PLURALNAME'] = 'Pokušaji prijave';
$lang['hr_HR']['LoginAttempt']['SINGULARNAME'] = 'Pokušaj prijave';
$lang['hr_HR']['Member']['ADDRESS'] = 'Adresa';
$lang['hr_HR']['Member']['belongs_many_many_Groups'] = 'Grupe';
$lang['hr_HR']['Member']['BUTTONCHANGEPASSWORD'] = 'Promjeni lozinku';
$lang['hr_HR']['Member']['BUTTONLOGIN'] = 'Prijavi';
$lang['hr_HR']['Member']['BUTTONLOGINOTHER'] = 'Prijavite se kao netko drugi';
$lang['hr_HR']['Member']['BUTTONLOSTPASSWORD'] = 'Zaboraljena lozinka';
$lang['hr_HR']['Member']['CONFIRMNEWPASSWORD'] = 'Potvrdite novu lozinku';
$lang['hr_HR']['Member']['CONFIRMPASSWORD'] = 'Potvrdi lozinku';
$lang['hr_HR']['Member']['CONTACTINFO'] = 'Kontakt informacije';
$lang['hr_HR']['Member']['db_LockedOutUntil'] = 'Zaključano do';
$lang['hr_HR']['Member']['db_PasswordExpiry'] = 'Lozinka ističe';
$lang['hr_HR']['Member']['EMAIL'] = 'Email';
$lang['hr_HR']['Member']['EMAILSIGNUPINTRO1'] = 'Hvala Vam na prijavi za novog člana, Vaši detalji prikazani su ispod';
$lang['hr_HR']['Member']['EMAILSIGNUPINTRO2'] = 'Na stranicu se možete prijaviti koristeći podatke navedene ispod';
$lang['hr_HR']['Member']['EMAILSIGNUPSUBJECT'] = 'Hvala Vam na prijavi';
$lang['hr_HR']['Member']['ERRORNEWPASSWORD'] = 'Pogrečno ste upisali novu lozinku, pokušajte ponovno';
$lang['hr_HR']['Member']['ERRORPASSWORDNOTMATCH'] = 'Vaša trenutna lozinka se ne podudara, probajte ponovno';
$lang['hr_HR']['Member']['ERRORWRONGCRED'] = 'Pogrešan Email ili lozinka. Pokušajte ponovno';
$lang['hr_HR']['Member']['FIRSTNAME'] = 'Ime';
$lang['hr_HR']['Member']['GREETING'] = 'Dobrodošli';
$lang['hr_HR']['Member']['INTERFACELANG'] = 'Jezik sučelja';
$lang['hr_HR']['Member']['LOGGEDINAS'] = 'Prijavljeni ste kao %s';
$lang['hr_HR']['Member']['MOBILE'] = 'Broj Mobitela';
$lang['hr_HR']['Member']['NAME'] = 'Ime';
$lang['hr_HR']['Member']['NEWPASSWORD'] = 'Nova lozinka';
$lang['hr_HR']['Member']['PASSWORD'] = 'Lozinka';
$lang['hr_HR']['Member']['PASSWORDCHANGED'] = 'Lozinka je promjenjena i poslana na Vaš email';
$lang['hr_HR']['Member']['PERSONALDETAILS'] = 'Osobni detalji';
$lang['hr_HR']['Member']['PHONE'] = 'Broj Telefona';
$lang['hr_HR']['Member']['PLURALNAME'] = 'Članovi';
$lang['hr_HR']['Member']['REMEMBERME'] = 'Zapamti me';
$lang['hr_HR']['Member']['SINGULARNAME'] = 'Član';
$lang['hr_HR']['Member']['SUBJECTPASSWORDCHANGED'] = 'Vaša lozinka je promjenjena';
$lang['hr_HR']['Member']['SUBJECTPASSWORDRESET'] = 'Link za reset lozinke';
$lang['hr_HR']['Member']['SURNAME'] = 'Prezime';
$lang['hr_HR']['Member']['USERDETAILS'] = 'Korisnički detalji';
$lang['hr_HR']['Member']['VALIDATIONMEMBEREXISTS'] = 'Već postoji korisnik sa tim Emailom';
$lang['hr_HR']['Member']['WELCOMEBACK'] = 'Dobrodošli %s';
$lang['hr_HR']['Member']['YOUROLDPASSWORD'] = 'Stara lozinka';
$lang['hr_HR']['MemberAuthenticator']['TITLE'] = 'E-mail &amp; Lozinka';
$lang['hr_HR']['MemberPassword']['PLURALNAME'] = 'Korisnička lozinke';
$lang['hr_HR']['MemberPassword']['SINGULARNAME'] = 'Korisnička lozinka';
$lang['hr_HR']['NumericField']['VALIDATION'] = '\'%s\' nije broj, prihvaćaju se samo brojevi';
$lang['hr_HR']['Page']['PLURALNAME'] = 'Stranice';
$lang['hr_HR']['Page']['SINGULARNAME'] = 'Stranica';
$lang['hr_HR']['Permission']['PLURALNAME'] = 'Dozvole';
$lang['hr_HR']['Permission']['SINGULARNAME'] = 'Dozvola';
$lang['hr_HR']['PhoneNumberField']['VALIDATION'] = 'Molim unesite ispravan telefonski broj';
$lang['hr_HR']['QueuedEmail']['PLURALNAME'] = 'Uključeni Emailovi';
$lang['hr_HR']['QueuedEmail']['SINGULARNAME'] = 'Uključeni Email';
$lang['hr_HR']['RedirectorPage']['HASBEENSETUP'] = 'Stranica za preusjeravanje nema postavljenog preusmjeravanja';
$lang['hr_HR']['RedirectorPage']['HEADER'] = 'Ova stranica preusmjeriti će korisnike na drugu stranicu';
$lang['hr_HR']['RedirectorPage']['OTHERURL'] = 'URL drugog weba';
$lang['hr_HR']['RedirectorPage']['REDIRECTTO'] = 'Preusmjeri na';
$lang['hr_HR']['RedirectorPage']['REDIRECTTOEXTERNAL'] = 'Drugi web';
$lang['hr_HR']['RedirectorPage']['REDIRECTTOPAGE'] = 'Stranicu na Vašom webu';
$lang['hr_HR']['RedirectorPage']['YOURPAGE'] = 'Stranica na Vašem webu';
$lang['hr_HR']['RelationComplexTableField.ss']['ADD'] = 'Dodaj';
$lang['hr_HR']['RelationComplexTableField.ss']['DELETE'] = 'izbriši';
$lang['hr_HR']['RelationComplexTableField.ss']['EDIT'] = 'ažuriraj';
$lang['hr_HR']['RelationComplexTableField.ss']['NOTFOUND'] = 'Nije pronađeno';
$lang['hr_HR']['RelationComplexTableField.ss']['SHOW'] = 'prikaži';
$lang['hr_HR']['Security']['ALREADYLOGGEDIN'] = 'Nemate pristup na ovu stranicu. Imate li drugi korisnički račun, koristite njega';
$lang['hr_HR']['Security']['BUTTONSEND'] = 'Pošalji mi link za reset lozinke';
$lang['hr_HR']['Security']['CHANGEPASSWORDBELOW'] = 'Svoju lozinku možete promjeniti ovdje';
$lang['hr_HR']['Security']['CHANGEPASSWORDHEADER'] = 'Promjeni lozinku';
$lang['hr_HR']['Security']['ENCDISABLED1'] = 'Enkripcija zaporke je isključena!';
$lang['hr_HR']['Security']['ENTERNEWPASSWORD'] = 'Upišite novu lozinku';
$lang['hr_HR']['Security']['ERRORPASSWORDPERMISSION'] = 'Morate biti prijavljeni kako bi ste promjenili lozinku';
$lang['hr_HR']['Security']['IPADDRESSES'] = 'IP adresa';
$lang['hr_HR']['Security']['LOGGEDOUT'] = 'Odlogirani ste. Želite li se ponovno logirati, upišite podatke';
$lang['hr_HR']['Security']['LOGIN'] = 'Logiraj se';
$lang['hr_HR']['Security']['LOSTPASSWORDHEADER'] = 'Izgubljena lozinka';
$lang['hr_HR']['Security']['NOTEPAGESECURED'] = 'Stranica je osigurana. Upišite svoje podatke i poslat ćemo Vam.';
$lang['hr_HR']['Security']['NOTERESETPASSWORD'] = 'Upišite vaš e-mail i polati ćemo Vam link na kojem možete dobiti novu lozinku';
$lang['hr_HR']['Security']['PASSWORDSENTHEADER'] = 'Link je poslan na \'%s\'';
$lang['hr_HR']['Security']['PASSWORDSENTTEXT'] = 'Hvala Vam! Link na reset lozinke je poslan na \'%s\'.';
$lang['hr_HR']['Security']['PERMFAILURE'] = 'Ova stranica je zaštićena i trebate imati administratorska prava da bi ste joj mogli pristupiti. Unesite svoje pristupne podatke, a mi ćemo vas odmah prosijediti dalje.';
$lang['hr_HR']['SecurityAdmin']['IPADDRESSESHELP'] = '<p>Možete ograničiti ovu grupu na određen raspon IP adresa. Unesite 1 raspon po retku. Rasponi IP adresa mogu biti u bilo kojem od ovih 4 formi: <br />
203.96.152.12<br />
203.96.152/24<br />
203.96/16<br />
203/8<br />
<br />
Ukoliko unesete jednu ili više IP adresa u ovaj box, tada će članovi imati pravo pristupiti grupi ukoliko se logiraju sa neke od dozvoljenih IIP adresa. To neće spriječiti da se ljudi logiraju. To je zato da se koristnik može logirati u dijelove sustava na koje se ne odnosi restrikcija u IP adresama.</p>';
$lang['hr_HR']['SecurityAdmin']['OPTIONALID'] = 'Opcionalni ID';
$lang['hr_HR']['SecurityAdmin']['VIEWUSER'] = 'Pogledaj korisnika';
$lang['hr_HR']['SimpleImageField']['NOUPLOAD'] = 'Nema uploadanih slika';
$lang['hr_HR']['SiteTree']['ACCESSANYONE'] = 'Svi';
$lang['hr_HR']['SiteTree']['ACCESSHEADER'] = 'Tko može pregledavati ovu stranicu?';
$lang['hr_HR']['SiteTree']['ACCESSLOGGEDIN'] = 'Samo prijavljeni korisnici';
$lang['hr_HR']['SiteTree']['ACCESSONLYTHESE'] = 'Samo slijedeći korisnici (odaberite s popisa)';
$lang['hr_HR']['SiteTree']['ADDEDTODRAFT'] = 'Dodano privremenoj stranici';
$lang['hr_HR']['SiteTree']['ALLOWCOMMENTS'] = 'Dozvoli komentare na stranici';
$lang['hr_HR']['SiteTree']['APPEARSVIRTUALPAGES'] = 'Ovaj sadržaj pojavljuje se i na virtualnim stranicama u %s sekcijama';
$lang['hr_HR']['SiteTree']['BUTTONCANCELDRAFT'] = 'Otkaži promjene na privremenoj stranici (draft)';
$lang['hr_HR']['SiteTree']['BUTTONCANCELDRAFTDESC'] = 'Obiriši privremenu stranicu i vrati na trenutno objavljenu';
$lang['hr_HR']['SiteTree']['BUTTONSAVEPUBLISH'] = 'Spremi i Objavi';
$lang['hr_HR']['SiteTree']['BUTTONUNPUBLISH'] = 'Unpublish';
$lang['hr_HR']['SiteTree']['BUTTONUNPUBLISHDESC'] = 'Izbrišite ovu stranicu sa objavljene';
$lang['hr_HR']['SiteTree']['CHANGETO'] = 'Promijeni u "%s"';
$lang['hr_HR']['SiteTree']['Content'] = 'Sadržaj';
$lang['hr_HR']['SiteTree']['EDITANYONE'] = 'Svi koji se mogu prijaviti  u CMS';
$lang['hr_HR']['SiteTree']['EDITHEADER'] = 'Tko može uređivati unutar CMSa?';
$lang['hr_HR']['SiteTree']['EDITONLYTHESE'] = 'Samo slijedeći korisnici (odaberite s popisa)';
$lang['hr_HR']['SiteTree']['GROUP'] = 'Grupa';
$lang['hr_HR']['SiteTree']['HASBROKENLINKS'] = 'Ova stranica ima pogrešne linkove';
$lang['hr_HR']['SiteTree']['has_one_Parent'] = 'Roditeljska stranica';
$lang['hr_HR']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Domena';
$lang['hr_HR']['SiteTree']['HTMLEDITORTITLE'] = 'Sadržaj';
$lang['hr_HR']['SiteTree']['MENUTITLE'] = 'Oznaka navigacije';
$lang['hr_HR']['SiteTree']['METADESC'] = 'Opis';
$lang['hr_HR']['SiteTree']['METAEXTRA'] = 'Podešeni Meta Tagovi';
$lang['hr_HR']['SiteTree']['METAHEADER'] = 'Meta-tagovi za pretraživače';
$lang['hr_HR']['SiteTree']['METAKEYWORDS'] = 'Ključne riječi';
$lang['hr_HR']['SiteTree']['METATITLE'] = 'Naslov';
$lang['hr_HR']['SiteTree']['MODIFIEDONDRAFT'] = 'Promjenjeno na privremenoj stranici';
$lang['hr_HR']['SiteTree']['NOBACKLINKS'] = 'Ova stranica nije povezana s ostalim stranicama.';
$lang['hr_HR']['SiteTree']['NOTEUSEASHOMEPAGE'] = 'Koristi ovu stranicu kao početnu za slijedeće domene: (domene odvojite zarezom)';
$lang['hr_HR']['SiteTree']['PAGESLINKING'] = 'Slijedeće stranice su vezane na ovu:';
$lang['hr_HR']['SiteTree']['PAGETITLE'] = 'Ime stranice';
$lang['hr_HR']['SiteTree']['PAGETYPE'] = 'Vrsta stranice';
$lang['hr_HR']['SiteTree']['PLURALNAME'] = 'Stranična stabla';
$lang['hr_HR']['SiteTree']['REMOVEDFROMDRAFT'] = 'Obrisano sa privremene stranice';
$lang['hr_HR']['SiteTree']['SHOWINMENUS'] = 'Pokaži u izbornicima?';
$lang['hr_HR']['SiteTree']['SHOWINSEARCH'] = 'Pokaži u tražilici?';
$lang['hr_HR']['SiteTree']['SINGULARNAME'] = 'Stranično stablo';
$lang['hr_HR']['SiteTree']['TABACCESS'] = 'Pristup
';
$lang['hr_HR']['SiteTree']['TABBACKLINKS'] = 'Povratni linkovi';
$lang['hr_HR']['SiteTree']['TABBEHAVIOUR'] = 'Karakteristike';
$lang['hr_HR']['SiteTree']['TABCONTENT'] = 'Sadržaj';
$lang['hr_HR']['SiteTree']['TABMAIN'] = '\'Main\'';
$lang['hr_HR']['SiteTree']['TABMETA'] = 'Meta-data';
$lang['hr_HR']['SiteTree']['TABREPORTS'] = 'Izvještaji';
$lang['hr_HR']['SiteTree']['TOPLEVEL'] = 'Sadržaj stranice (Top Level)';
$lang['hr_HR']['SiteTree']['URL'] = 'URL';
$lang['hr_HR']['SiteTree']['URLSegment'] = 'Dio URLa';
$lang['hr_HR']['SiteTree']['VALIDATIONURLSEGMENT1'] = 'Ovaj URL se već koristi. URL mora biti jedinstvaen';
$lang['hr_HR']['SiteTree']['VALIDATIONURLSEGMENT2'] = 'URL smije sadržavati samo slova, brojeve i crtice';
$lang['hr_HR']['TableField']['ISREQUIRED'] = 'U %s \'%s\' je obavezan';
$lang['hr_HR']['TableField.ss']['ADD'] = 'Dodaj novi redak';
$lang['hr_HR']['TableField.ss']['CSVEXPORT'] = 'Izvezi u CSV';
$lang['hr_HR']['TableListField_PageControls.ss']['DISPLAYING'] = 'Prikazujem';
$lang['hr_HR']['TableListField_PageControls.ss']['OF'] = 'od';
$lang['hr_HR']['TableListField_PageControls.ss']['TO'] = 'do';
$lang['hr_HR']['TableListField_PageControls.ss']['VIEWFIRST'] = 'Pogledaj prvi';
$lang['hr_HR']['TableListField_PageControls.ss']['VIEWLAST'] = 'Pogledaj zadnji';
$lang['hr_HR']['TableListField_PageControls.ss']['VIEWNEXT'] = 'Pogledaj slijedeći';
$lang['hr_HR']['TableListField_PageControls.ss']['VIEWPREVIOUS'] = 'Pogledaj prethodni';
$lang['hr_HR']['ToggleCompositeField.ss']['HIDE'] = 'Sakrij';
$lang['hr_HR']['ToggleCompositeField.ss']['SHOW'] = 'Pokaži';
$lang['hr_HR']['ToggleField']['LESS'] = 'manje';
$lang['hr_HR']['ToggleField']['MORE'] = 'više';
$lang['hr_HR']['TypeDropdown']['NONE'] = 'None';
$lang['hr_HR']['Versioned']['has_many_Versions'] = 'Verzije';
$lang['hr_HR']['VirtualPage']['CHOOSE'] = 'Odaberite stranicu na koju se želite povezati';
$lang['hr_HR']['VirtualPage']['EDITCONTENT'] = 'kliknite za uređivanje sadržaja';
$lang['hr_HR']['VirtualPage']['HEADER'] = 'Ovo je virtualna stranica';
$lang['hr_HR']['VirtualPage']['PLURALNAME'] = 'Virtualne stranice';
$lang['hr_HR']['VirtualPage']['SINGULARNAME'] = 'Virtualna stranica';
$lang['hr_HR']['Widget']['PLURALNAME'] = 'Widgeti';
$lang['hr_HR']['Widget']['SINGULARNAME'] = 'Widget';
$lang['hr_HR']['WidgetArea']['PLURALNAME'] = 'Prostori za Widgete';
$lang['hr_HR']['WidgetArea']['SINGULARNAME'] = 'Prostor za Widget';

