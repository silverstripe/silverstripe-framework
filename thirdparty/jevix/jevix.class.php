<?php
/**
 * Jevix — средство автоматического применения правил набора текстов,
 * наделённое способностью унифицировать разметку HTML/XML документов,
 * контролировать перечень допустимых тегов и аттрибутов,
 * предотвращать возможные XSS-атаки в коде документов.
 * http://code.google.com/p/jevix/
 *
 * @author ur001 <ur001ur001@gmail.com>, http://ur001.habrahabr.ru
 * @version 1.01
 *
 * История версий:
 * 1.11:
 *  + Исправлены ошибки из-за которых удалялись теги и аттрибуты со значением "0". Спасибо Dmitry Shurupov (dmitry.shurupov@trueoffice.ru)
 * 1.1:
 *  + cfgSetTagParamsAutoAdd() deprecated. Вместо него следует использовать cfgSetTagParamDefault() с более удобным синтаксисом
 *  + Исправлен критический баг с обработкой атрибутов тегов https://code.google.com/p/jevix/issues/detail?id=1
 *  + Удаление атрибутов тегов с пустым значением. Атрибуты без значений (checked, nowrap) теперь превращаются в checked="checked"
 *  + Исправлен тест, проведена небольшая ревизия кода
 * 1.02:
 *  + Функции для работы со строками заменены на аналогичные mb_*, чтобы не перегружать через mbstring.func_overload (ev.y0ga@mail.ru)
 * 1.01
 *  + cfgSetAutoReplace теперь регистронезависимый
 *  + Возможность указать через cfgSetTagIsEmpty теги с пустым содержанием, которые не будут адалены парсером (rus.engine)
 *  + фикс бага удаления контента тега при разном регистре открывающего и закрывающего тегов  (rus.engine)
 *  + Исправлено поведение парсера при установке правила sfgParamsAutoAdd(). Теперь
 *    параметр устанавливается только в том случае, если его вообще нет в
 *    обрабатываемом тексте. Если есть - оставляется оригинальное значение. (deadyaga)
 * 1.00
 *  + Исправлен баг с закрывающимися тегами приводящий к созданию непарного тега рушащего вёрстку
 * 1.00 RC2
 *  + Небольшая чистка кода
 * 1.00 RC1
 *  + Добавлен символьный класс Jevix::RUS для определния русских символов
 *  + Авторасстановка пробелов после пунктуации только для кирилицы
 *  + Добавлена настройка cfgSetTagNoTypography() отключающая типографирование в указанном теге
 *  + Немного переделан алгоритм обработки кавычек. Он стал более строгим
 *  + Знак дюйма 33" больше не превращается в открывающуюся кавычку. Однако варриант "мой 24" монитор" - парсер не переварит.
 * 0.99
 *  + Расширена функциональность для проверки атрибутов тега:
 *    можно указать тип атрибута ( 'colspan'=>'#int', 'value' => '#text' )
 *    в Jevix, по-умолчанию, определён массив типов для нескольких стандартных атрибутов (src, href, width, height)
 * 0.98
 *  + Расширена функциональность для проверки атрибутов тега:
 *    можно задавать список дозможных значений атрибута (  'align'=>array('left', 'right', 'center') )
 * 0.97
 *  + Обычные "кавычки" сохраняются как &quote; если они были так написаны
 * 0.96
 *  + Добавлены разрешённые протоколы https и ftp для ссылок (a href="https://...)
 * 0.95
 *  + Исправлено типографирование ?.. и !.. (две точки в конце больше не превращаются в троеточие)
 *  + Отключено автоматическое добавление пробела после точки для латиницы из-за чего невозможно было написать
 *    index.php или .htaccess
 * 0.94
 *  + Добавлена настройка автодобавления параметров тегов. Непример rel = "nofolow" для ссылок.
 *    Спасибо Myroslav Holyak (vbhjckfd@gmail.com)
 * 0.93
 *      + Исправлен баг с удалением пробелов (например в "123 &mdash; 123")
 *  + Исправлена ошибка из-за которой иногда не срабатывало автоматическое преобразования URL в ссылу
 *  + Добавлена настройка cfgSetAutoLinkMode для отключения автоматического преобразования URL в ссылки
 *  + Автодобавление пробела после точки, если после неё идёт русский символ
 * 0.92
 *      + Добавлена настройка cfgSetAutoBrMode. При установке в false, переносы строк не будут автоматически заменяться на BR
 *      + Изменена обработка HTML-сущностей. Теперь все сущности имеющие эквивалент в Unicode (за исключением <>)
 *    автоматически преобразуются в символ
 * 0.91
 *      + Добавлена обработка преформатированных тегов <pre>, <code>. Для задания используйте cfgSetTagPreformatted()
 *  + Добавлена настройка cfgSetXHTMLMode. При отключении пустые теги будут оформляться как <br>, при включенном - <br/>
 *      + Несколько незначительных багфиксов
 * 0.9
 *      + Первый бета-релиз
 */

class JevixMod {
	const PRINATABLE  = 0x1;
	const ALPHA       = 0x2;
	const LAT	 = 0x4;
	const RUS	 = 0x8;
	const NUMERIC     = 0x10;
	const SPACE       = 0x20;
	const NAME	= 0x40;
	const URL	 = 0x100;
	const NOPRINT     = 0x200;
	const PUNCTUATUON = 0x400;
	//const	   = 0x800;
	//const	   = 0x1000;
	const HTML_QUOTE  = 0x2000;
	const TAG_QUOTE   = 0x4000;
	const QUOTE_CLOSE = 0x8000;
	const NL	  = 0x10000;
	const QUOTE_OPEN  = 0;

	const STATE_TEXT = 0;
	const STATE_TAG_PARAMS = 1;
	const STATE_TAG_PARAM_VALUE = 2;
	const STATE_INSIDE_TAG = 3;
	const STATE_INSIDE_NOTEXT_TAG = 4;
	const STATE_INSIDE_PREFORMATTED_TAG = 5;
	const STATE_INSIDE_CALLBACK_TAG = 6;

	public $tagsRules = array();
	public $entities1 = array('"'=>'&quot;', "'"=>'&#39;', '&'=>'&amp;', '<'=>'&lt;', '>'=>'&gt;');
	public $entities2 = array('<'=>'&lt;', '>'=>'&gt;', '"'=>'&quot;');
	public $textQuotes = array(array('«', '»'), array('„', '“'));
	public $dash = " — ";
	public $apostrof = "’";
	public $dotes = "…";
	public $nl = "\r\n";
	public $defaultTagParamRules = array('href' => '#link', 'src' => '#image', 'width' => '#int', 'height' => '#int', 'text' => '#text', 'title' => '#text');

	protected $text;
	protected $textBuf;
	protected $textLen = 0;
	protected $curPos;
	protected $curCh;
	protected $curChOrd;
	protected $curChClass;
	protected $curParentTag;
	protected $states;
	protected $quotesOpened = 0;
	protected $brAdded = 0;
	protected $state;
	protected $tagsStack;
	protected $openedTag;
	protected $autoReplace; // Автозамена
	protected $isXHTMLMode  = true; // <br/>, <img/>
	protected $isAutoBrMode = true; // \n = <br/>
	protected $isAutoLinkMode = true;
	protected $br = "<br/>";

	protected $noTypoMode = false;

	public    $outBuffer = '';
	public    $errors;


	/**
	 * Константы для класификации тегов
	 *
	 */
	const TR_TAG_ALLOWED = 1;	// Тег позволен
	const TR_PARAM_ALLOWED = 2;      // Параметр тега позволен (a->title, a->src, i->alt)
	const TR_PARAM_REQUIRED = 3;     // Параметр тега влятся необходимым (a->href, img->src)
	const TR_TAG_SHORT = 4;	  // Тег может быть коротким (img, br)
	const TR_TAG_CUT = 5;	    // Тег необходимо вырезать вместе с контентом (script, iframe)
	const TR_TAG_CHILD = 6;	  // Тег может содержать другие теги
	const TR_TAG_CONTAINER = 7;      // Тег может содержать лишь указанные теги. В нём не может быть текста
	const TR_TAG_CHILD_TAGS = 8;     // Теги которые может содержать внутри себя другой тег
	const TR_TAG_PARENT = 9;	 // Тег в котором должен содержаться данный тег
	const TR_TAG_PREFORMATTED = 10;  // Преформатированные тег, в котором всё заменяется на HTML сущности типа <pre> сохраняя все отступы и пробелы
	const TR_PARAM_AUTO_ADD = 11;    // Auto add parameters + default values (a->rel[=nofollow])
	const TR_TAG_NO_TYPOGRAPHY = 12; // Отключение типографирования для тега
	const TR_TAG_IS_EMPTY = 13;      // Не короткий тег с пустым содержанием имеет право существовать
	const TR_TAG_NO_AUTO_BR = 14;    // Тег в котором не нужна авто-расстановка <br>
	const TR_TAG_CALLBACK = 15;      // Тег обрабатывается callback-функцией

	/**
	 * Классы символов генерируются symclass.php
	 *
	 * @var array
	 */
	protected $chClasses = array(0=>512,1=>512,2=>512,3=>512,4=>512,5=>512,6=>512,7=>512,8=>512,9=>32,10=>66048,11=>512,12=>512,13=>66048,14=>512,15=>512,16=>512,17=>512,18=>512,19=>512,20=>512,21=>512,22=>512,23=>512,24=>512,25=>512,26=>512,27=>512,28=>512,29=>512,30=>512,31=>512,32=>32,97=>71,98=>71,99=>71,100=>71,101=>71,102=>71,103=>71,104=>71,105=>71,106=>71,107=>71,108=>71,109=>71,110=>71,111=>71,112=>71,113=>71,114=>71,115=>71,116=>71,117=>71,118=>71,119=>71,120=>71,121=>71,122=>71,65=>71,66=>71,67=>71,68=>71,69=>71,70=>71,71=>71,72=>71,73=>71,74=>71,75=>71,76=>71,77=>71,78=>71,79=>71,80=>71,81=>71,82=>71,83=>71,84=>71,85=>71,86=>71,87=>71,88=>71,89=>71,90=>71,1072=>11,1073=>11,1074=>11,1075=>11,1076=>11,1077=>11,1078=>11,1079=>11,1080=>11,1081=>11,1082=>11,1083=>11,1084=>11,1085=>11,1086=>11,1087=>11,1088=>11,1089=>11,1090=>11,1091=>11,1092=>11,1093=>11,1094=>11,1095=>11,1096=>11,1097=>11,1098=>11,1099=>11,1100=>11,1101=>11,1102=>11,1103=>11,1040=>11,1041=>11,1042=>11,1043=>11,1044=>11,1045=>11,1046=>11,1047=>11,1048=>11,1049=>11,1050=>11,1051=>11,1052=>11,1053=>11,1054=>11,1055=>11,1056=>11,1057=>11,1058=>11,1059=>11,1060=>11,1061=>11,1062=>11,1063=>11,1064=>11,1065=>11,1066=>11,1067=>11,1068=>11,1069=>11,1070=>11,1071=>11,48=>337,49=>337,50=>337,51=>337,52=>337,53=>337,54=>337,55=>337,56=>337,57=>337,34=>57345,39=>16385,46=>1281,44=>1025,33=>1025,63=>1281,58=>1025,59=>1281,1105=>11,1025=>11,47=>257,38=>257,37=>257,45=>257,95=>257,61=>257,43=>257,35=>257,124=>257,);

	/*
	 * Default 
	 * Configuration
	 * bootstrap
	*/
	public function __construct() {
		//Конфигурация
		
		// 1. Устанавливаем разрешённые теги. (Все не разрешенные теги считаются запрещенными.)
		$this->cfgAllowTags(array('a', 'img', 'i', 'b', 'u', 'em', 'strong','strike','nobr', 'li', 'ol', 'ul', 'sup','sub', 'abbr', 'pre', 'acronym', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'span', 'code', 'object', 'address', 'blockquote', 'div', 'tt', 'big', 'small', 'kbd', 'samp', 'var', 'del', 'ins', 'cite', 'q', ''));
		
		// 2. Устанавливаем коротие теги. (не имеющие закрывающего тега)
		$this->cfgSetTagShort(array('br','img'));
		
		// 3. Устанавливаем преформатированные теги. (в них все будет заменятся на HTML сущности)
		$this->cfgSetTagPreformatted(array('pre'));
		
		// 4. Устанавливаем теги, которые необходимо вырезать из текста вместе с контентом.
		$this->cfgSetTagCutWithContent(array('script', 'iframe', 'style'));
		
		// 5. Устанавливаем разрешённые параметры тегов. Также можно устанавливать допустимые значения этих параметров.
		$this->cfgAllowTagParams('a', array('title', 'href', 'class', 'rel', 'rev'));
		$this->cfgAllowTagParams('span', array('dir', 'style', 'class'));
		$this->cfgAllowTagParams('img', array('src', 'alt' => '#text', 'title', 'align' => array('right', 'left', 'center'), 'width' => '#int', 'height' => '#int', 'hspace' => '#int', 'vspace' => '#int'));
		
		
		// 6. Устанавливаем параметры тегов являющиеся обязяательными. Без них вырезает тег оставляя содержимое.
		$this->cfgSetTagParamsRequired('img', 'src');
		$this->cfgSetTagParamsRequired('a', 'href');
		
		// 7. Устанавливаем теги которые может содержать тег контейнер
		//    cfgSetTagChilds($tag, $childs, $isContainerOnly, $isChildOnly)
		//       $isContainerOnly : тег является только контейнером для других тегов и не может содержать текст (по умолчанию false)
		//       $isChildOnly : вложенные теги не могут присутствовать нигде кроме указанного тега (по умолчанию false)
		//$this->cfgSetTagChilds('ul', 'li', true, false);
		
		// 8. Устанавливаем атрибуты тегов, которые будут добавлятся автоматически
		//$this->cfgSetTagParamDefault('a', 'rel', null, true);
		//$this->cfgSetTagParamsAutoAdd('a', array('rel' => 'nofollow'));
		//$this->cfgSetTagParamsAutoAdd('a', array('name'=>'rel', 'value' => 'nofollow', 'rewrite' => true));
		
		$this->cfgSetTagParamDefault('img', 'width',  '300px');
		$this->cfgSetTagParamDefault('img', 'height', '300px');
		//$this->cfgSetTagParamsAutoAdd('img', array('width' => '300', 'height' => '300'));
		//$this->cfgSetTagParamsAutoAdd('img', array(array('name'=>'width', 'value' => '300'), array('name'=>'height', 'value' => '300') ));
		
		// 9. Устанавливаем автозамену
		$this->cfgSetAutoReplace(array('+/-', '(c)', '(r)'), array('±', '©', '®'));
		
		// 10. Включаем или выключаем режим XHTML. (по умолчанию включен)
		$this->cfgSetXHTMLMode(true);
		
		// 11. Включаем или выключаем режим замены переноса строк на тег <br/>. (по умолчанию включен)
		$this->cfgSetAutoBrMode(false);
		
		// 12. Включаем или выключаем режим автоматического определения ссылок. (по умолчанию включен)
		$this->cfgSetAutoLinkMode(true);
		
		// 13. Отключаем типографирование в определенном теге
		$this->cfgSetTagNoTypography('code');
	}
	
	/**
	 * Установка конфигурационного флага для одного или нескольких тегов
	 *
	 * @param array|string $tags тег(и)
	 * @param int $flag флаг
	 * @param mixed $value значеник=е флага
	 * @param boolean $createIfNoExists если тег ещё не определён - создть его
	 */
	protected function _cfgSetTagsFlag($tags, $flag, $value, $createIfNoExists = true){
		if(!is_array($tags)) $tags = array($tags);
		foreach($tags as $tag){
			if(!isset($this->tagsRules[$tag])) {
				if($createIfNoExists){
					$this->tagsRules[$tag] = array();
				} else {
					throw new Exception("Тег $tag отсутствует в списке разрешённых тегов");
				}
			}
			$this->tagsRules[$tag][$flag] = $value;
		}
	}

	/**
	 * КОНФИГУРАЦИЯ: Разрешение или запрет тегов
	 * Все не разрешённые теги считаются запрещёнными
	 * @param array|string $tags тег(и)
	 */
	function cfgAllowTags($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_ALLOWED, true);
	}

	/**
	 * КОНФИГУРАЦИЯ: Коротие теги типа <img>
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagShort($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_SHORT, true, false);
	}

	/**
	 * КОНФИГУРАЦИЯ: Преформатированные теги, в которых всё заменяется на HTML сущности типа <pre>
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagPreformatted($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_PREFORMATTED, true, false);
	}

	/**
	 * КОНФИГУРАЦИЯ: Теги в которых отключено типографирование типа <code>
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagNoTypography($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_NO_TYPOGRAPHY, true, false);
	}

	/**
	 * КОНФИГУРАЦИЯ: Не короткие теги которые не нужно удалять с пустым содержанием, например, <param name="code" value="die!"></param>
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagIsEmpty($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_IS_EMPTY, true, false);
	}

	/**
	 * КОНФИГУРАЦИЯ: Теги внутри который не нужна авто-расстановка <br/>, например, <ul></ul> и <ol></ol>
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagNoAutoBr($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_NO_AUTO_BR, true, false);
	}

	/**
	 * КОНФИГУРАЦИЯ: Тег необходимо вырезать вместе с контентом (script, iframe)
	 * @param array|string $tags тег(и)
	 */
	function cfgSetTagCutWithContent($tags){
		$this->_cfgSetTagsFlag($tags, self::TR_TAG_CUT, true);
	}

	/**
	 * КОНФИГУРАЦИЯ: Добавление разрешённых параметров тега
	 * @param string $tag тег
	 * @param string|array $params разрешённые параметры
	 */
	function cfgAllowTagParams($tag, $params){
		if(!isset($this->tagsRules[$tag])) throw new Exception("Тег $tag отсутствует в списке разрешённых тегов");
		if(!is_array($params)) $params = array($params);
		// Если ключа со списком разрешенных параметров не существует - создаём ео
		if(!isset($this->tagsRules[$tag][self::TR_PARAM_ALLOWED])) {
			$this->tagsRules[$tag][self::TR_PARAM_ALLOWED] = array();
		}
		foreach($params as $key => $value){
			if(is_string($key)){
				$this->tagsRules[$tag][self::TR_PARAM_ALLOWED][$key] = $value;
			} else {
				$this->tagsRules[$tag][self::TR_PARAM_ALLOWED][$value] = true;
			}
		}
	}

	/**
	 * КОНФИГУРАЦИЯ: Добавление необходимых параметров тега
	 * @param string $tag тег
	 * @param string|array $params разрешённые параметры
	 */
	function cfgSetTagParamsRequired($tag, $params){
		if(!isset($this->tagsRules[$tag])) throw new Exception("Тег $tag отсутствует в списке разрешённых тегов");
		if(!is_array($params)) $params = array($params);
		// Если ключа со списком разрешенных параметров не существует - создаём ео
		if(!isset($this->tagsRules[$tag][self::TR_PARAM_REQUIRED])) {
			$this->tagsRules[$tag][self::TR_PARAM_REQUIRED] = array();
		}
		foreach($params as $param){
			$this->tagsRules[$tag][self::TR_PARAM_REQUIRED][$param] = true;
		}
	}

	/* КОНФИГУРАЦИЯ: Установка тегов которые может содержать тег-контейнер
	 * @param string $tag тег
	 * @param string|array $childs разрешённые теги
	 * @param boolean $isContainerOnly тег является только контейнером других тегов и не может содержать текст
	 * @param boolean $isChildOnly вложенные теги не могут присутствовать нигде кроме указанного тега
	 */
	function cfgSetTagChilds($tag, $childs, $isContainerOnly = false, $isChildOnly = false){
		if(!isset($this->tagsRules[$tag])) throw new Exception("Тег $tag отсутствует в списке разрешённых тегов");
		if(!is_array($childs)) $childs = array($childs);
		// Тег является контейнером и не может содержать текст
		if($isContainerOnly) $this->tagsRules[$tag][self::TR_TAG_CONTAINER] = true;
		// Если ключа со списком разрешенных тегов не существует - создаём ео
		if(!isset($this->tagsRules[$tag][self::TR_TAG_CHILD_TAGS])) {
			$this->tagsRules[$tag][self::TR_TAG_CHILD_TAGS] = array();
		}
		foreach($childs as $child){
			$this->tagsRules[$tag][self::TR_TAG_CHILD_TAGS][$child] = true;
			//  Указанный тег должен сущеаствовать в списке тегов
			if(!isset($this->tagsRules[$child])) throw new Exception("Тег $child отсутствует в списке разрешённых тегов");
			if(!isset($this->tagsRules[$child][self::TR_TAG_PARENT])) $this->tagsRules[$child][self::TR_TAG_PARENT] = array();
			$this->tagsRules[$child][self::TR_TAG_PARENT][$tag] = true;
			// Указанные разрешённые теги могут находится только внтутри тега-контейнера
			if($isChildOnly) $this->tagsRules[$child][self::TR_TAG_CHILD] = true;
		}
	}

	/**
	 * CONFIGURATION: Adding autoadd attributes and their values to tag. If the 'rewrite' set as true, the attribute value will be replaced
	 * @param string $tag tag
	 * @param string|array $params array of pairs array('name'=>attributeName, 'value'=>attributeValue, 'rewrite'=>true|false)
	 * @deprecated устаревший синтаксис. Используйте cfgSetTagParamDefault
	 */
	function cfgSetTagParamsAutoAdd($tag, $params){
		throw new Exception("cfgSetTagParamsAutoAdd() is Deprecated. Use cfgSetTagParamDefault() instead");
	}

	/**
	 * КОНФИГУРАЦИЯ: Установка дефолтных значений для атрибутов тега
	 * @param string $tag тег
	 * @param string $param атрибут
	 * @param string $value значение
	 * @param boolean $isRewrite заменять указанное значение дефолтным
	 */
	function cfgSetTagParamDefault($tag, $param, $value, $isRewrite = false){
		if(!isset($this->tagsRules[$tag])) throw new Exception("Tag $tag is missing in allowed tags list");

		if(!isset($this->tagsRules[$tag][self::TR_PARAM_AUTO_ADD])) {
			$this->tagsRules[$tag][self::TR_PARAM_AUTO_ADD] = array();
		}

		$this->tagsRules[$tag][self::TR_PARAM_AUTO_ADD][$param] = array('value'=>$value, 'rewrite'=>$isRewrite);
	}

/**
	 * КОНФИГУРАЦИЯ: Устанавливаем callback-функцию на обработку содержимого тега
	 * @param string $tag тег
	 * @param mixed $callback функция
	 */
	function cfgSetTagCallback($tag, $callback = null){
		if(!isset($this->tagsRules[$tag])) throw new Exception("Тег $tag отсутствует в списке разрешённых тегов");
		$this->tagsRules[$tag][self::TR_TAG_CALLBACK] = $callback;
	}

	/**
	 * Автозамена
	 *
	 * @param array $from с
	 * @param array $to на
	 */
	function cfgSetAutoReplace($from, $to){
		$this->autoReplace = array('from' => $from, 'to' => $to);
	}

	/**
	 * Включение или выключение режима XTML
	 *
	 * @param boolean $isXHTMLMode
	 */
	function cfgSetXHTMLMode($isXHTMLMode){
		$this->br = $isXHTMLMode ? '<br/>' : '<br>';
		$this->isXHTMLMode = $isXHTMLMode;
	}

	/**
	 * Включение или выключение режима замены новых строк на <br/>
	 *
	 * @param boolean $isAutoBrMode
	 */
	function cfgSetAutoBrMode($isAutoBrMode){
		$this->isAutoBrMode = $isAutoBrMode;
	}

	/**
	 * Включение или выключение режима автоматического определения ссылок
	 *
	 * @param boolean $isAutoLinkMode
	 */
	function cfgSetAutoLinkMode($isAutoLinkMode){
		$this->isAutoLinkMode = $isAutoLinkMode;
	}

	protected function &strToArray($str){
		$chars = null;
		preg_match_all('/./su', $str, $chars);
		return $chars[0];
	}


	function parse($text, &$errors){
		$this->curPos = -1;
		$this->curCh = null;
		$this->curChOrd = 0;
		$this->state = self::STATE_TEXT;
		$this->states = array();
		$this->quotesOpened = 0;
		$this->noTypoMode = false;
		
		// Авто растановка BR?
		if($this->isAutoBrMode) {
			$this->text = preg_replace('/<br\/?>(\r\n|\n\r|\n)?/ui', $this->nl, $text);
		} else {
			$this->text = $text;
		}
		// Remove meta charset include after browser paste operation
		$this->text = preg_replace('!<meta charset="utf-8" \/>!i','',$this->text);


		if(!empty($this->autoReplace)){
			$this->text = str_ireplace($this->autoReplace['from'], $this->autoReplace['to'], $this->text);
		}
		$this->textBuf = $this->strToArray($this->text);
		$this->textLen = count($this->textBuf);
		$this->getCh();
		$content = '';
		$this->outBuffer='';
		$this->brAdded=0;
		$this->tagsStack = array();
		$this->openedTag = null;
		$this->errors = array();
		$this->skipSpaces();
		$this->anyThing($content);
		$errors = $this->errors;
		return $content;
	}

	/**
	 * Получение следующего символа из входной строки
	 * @return string считанный символ
	 */
	protected function getCh(){
		return $this->goToPosition($this->curPos+1);
	}

	/**
	 * Перемещение на указанную позицию во входной строке и считывание символа
	 * @return string символ в указанной позиции
	 */
	protected function goToPosition($position){
		$this->curPos = $position;
		if($this->curPos < $this->textLen){
			$this->curCh = $this->textBuf[$this->curPos];
			$this->curChOrd = $this->uniord($this->curCh);
			$this->curChClass = $this->getCharClass($this->curChOrd);
		} else {
			$this->curCh = null;
			$this->curChOrd = 0;
			$this->curChClass = 0;
		}
		return $this->curCh;
	}

	/**
	 * Сохранить текущее состояние
	 *
	 */
	protected function saveState(){
		$state = array(
			'pos'   => $this->curPos,
			'ch'    => $this->curCh,
			'ord'   => $this->curChOrd,
			'class' => $this->curChClass,
		);

		$this->states[] = $state;
		return count($this->states)-1;
	}

	/**
	 * Восстановить
	 *
	 */
	protected function restoreState($index = null){
		if(!count($this->states)) throw new Exception('Конец стека');
		if($index == null){
			$state = array_pop($this->states);
		} else {
			if(!isset($this->states[$index])) throw new Exception('Неверный индекс стека');
			$state = $this->states[$index];
			$this->states = array_slice($this->states, 0, $index);
		}

		$this->curPos     = $state['pos'];
		$this->curCh      = $state['ch'];
		$this->curChOrd   = $state['ord'];
		$this->curChClass = $state['class'];
	}

	/**
	 * Проверяет точное вхождение символа в текущей позиции
	 * Если символ соответствует указанному автомат сдвигается на следующий
	 *
	 * @param string $ch
	 * @return boolean
	 */
	protected function matchCh($ch, $skipSpaces = false){
		if($this->curCh == $ch) {
			$this->getCh();
			if($skipSpaces) $this->skipSpaces();
			return true;
		}

		return false;
	}

	/**
	 * Проверяет точное вхождение символа указанного класса в текущей позиции
	 * Если символ соответствует указанному классу автомат сдвигается на следующий
	 *
	 * @param int $chClass класс символа
	 * @return string найденый символ или false
	 */
	protected function matchChClass($chClass, $skipSpaces = false){
		if(($this->curChClass & $chClass) == $chClass) {
			$ch = $this->curCh;
			$this->getCh();
			if($skipSpaces) $this->skipSpaces();
			return $ch;
		}

		return false;
	}

	/**
	 * Проверка на точное совпадение строки в текущей позиции
	 * Если строка соответствует указанной автомат сдвигается на следующий после строки символ
	 *
	 * @param string $str
	 * @return boolean
	 */
	protected function matchStr($str, $skipSpaces = false){
		$this->saveState();
		$len = mb_strlen($str, 'UTF-8');
		$test = '';
		while($len-- && $this->curChClass){
			$test.=$this->curCh;
			$this->getCh();
		}

		if($test == $str) {
			if($skipSpaces) $this->skipSpaces();
			return true;
		} else {
			$this->restoreState();
			return false;
		}
	}

	/**
	 * Пропуск текста до нахождения указанного символа
	 *
	 * @param string $ch сиимвол
	 * @return string найденый символ или false
	 */
	protected function skipUntilCh($ch){
		$chPos = mb_strpos($this->text, $ch, $this->curPos, 'UTF-8');
		if($chPos){
			return $this->goToPosition($chPos);
		} else {
			return false;
		}
	}

	/**
	 * Пропуск текста до нахождения указанной строки или символа
	 *
	 * @param string $str строка или символ ля поиска
	 * @return boolean
	 */
	protected function skipUntilStr($str){
		$str = $this->strToArray($str);
		$firstCh = $str[0];
		$len = count($str);
		while($this->curChClass){
			if($this->curCh == $firstCh){
				$this->saveState();
				$this->getCh();
				$strOK = true;
				for($i = 1; $i<$len ; $i++){
					// Конец строки
					if(!$this->curChClass){
						return false;
					}
					// текущий символ не равен текущему символу проверяемой строки?
					if($this->curCh != $str[$i]){
						$strOK = false;
						break;
					}
					// Следующий символ
					$this->getCh();
				}

				// При неудаче откатываемся с переходим на следующий символ
				if(!$strOK){
					$this->restoreState();
				} else {
					return true;
				}
			}
			// Следующий символ
			$this->getCh();
		}
		return false;
	}

	/**
	 * Возвращает класс символа
	 *
	 * @return int
	 */
	protected function getCharClass($ord){
		return isset($this->chClasses[$ord]) ? $this->chClasses[$ord] : self::PRINATABLE;
	}

	/*function isSpace(){
		return $this->curChClass == slf::SPACE;
	}*/

	/**
	 * Пропуск пробелов
	 *
	 */
	protected function skipSpaces(&$count = 0){
		while($this->curChClass == self::SPACE) {
			$this->getCh();
			$count++;
		}
		return $count > 0;
	}

	/**
	 *  Получает име (тега, параметра) по принципу 1 сиивол далее цифра или символ
	 *
	 * @param string $name
	 */
	protected function name(&$name = '', $minus = false){
		if(($this->curChClass & self::LAT) == self::LAT){
			$name.=$this->curCh;
			$this->getCh();
		} else {
			return false;
		}

		while((($this->curChClass & self::NAME) == self::NAME || ($minus && $this->curCh=='-'))){
			$name.=$this->curCh;
			$this->getCh();
		}

		$this->skipSpaces();
		return true;
	}

	protected function tag(&$tag, &$params, &$content, &$short){
		$this->saveState();
		$params = array();
		$tag = '';
		$closeTag = '';
		$params = array();
		$short = false;
		if(!$this->tagOpen($tag, $params, $short)) return false;
		// Короткая запись тега
		if($short) return true;

		// Сохраняем кавычки и состояние
		//$oldQuotesopen = $this->quotesOpened;
		$oldState = $this->state;
		$oldNoTypoMode = $this->noTypoMode;
		//$this->quotesOpened = 0;


		// Если в теге не должно быть текста, а только другие теги
		// Переходим в состояние self::STATE_INSIDE_NOTEXT_TAG
		if(!empty($this->tagsRules[$tag][self::TR_TAG_PREFORMATTED])){
			$this->state = self::STATE_INSIDE_PREFORMATTED_TAG;
		} elseif(!empty($this->tagsRules[$tag][self::TR_TAG_CONTAINER])){
			$this->state = self::STATE_INSIDE_NOTEXT_TAG;
		} elseif(!empty($this->tagsRules[$tag][self::TR_TAG_NO_TYPOGRAPHY])) {
			$this->noTypoMode = true;
			$this->state = self::STATE_INSIDE_TAG;
		} elseif(array_key_exists($tag, $this->tagsRules) && array_key_exists(self::TR_TAG_CALLBACK, $this->tagsRules[$tag])){
			$this->state = self::STATE_INSIDE_CALLBACK_TAG;
		} else {
			$this->state = self::STATE_INSIDE_TAG;
		}

		// Контент тега
		array_push($this->tagsStack, $tag);
		$this->openedTag = $tag;
		$content = '';
		if($this->state == self::STATE_INSIDE_PREFORMATTED_TAG){
			$this->preformatted($content, $tag);
		} elseif($this->state == self::STATE_INSIDE_CALLBACK_TAG){
			$this->callback($content, $tag);
		} else {
			$this->anyThing($content, $tag);
		}

		array_pop($this->tagsStack);
		$this->openedTag = !empty($this->tagsStack) ? array_pop($this->tagsStack) : null;

		$isTagClose = $this->tagClose($closeTag);
		if($isTagClose && ($tag != $closeTag)) {
			$this->eror("Неверный закрывающийся тег $closeTag. Ожидалось закрытие $tag");
			//$this->restoreState();
		}

		// Восстанавливаем предыдущее состояние и счетчик кавычек
		$this->state = $oldState;
		$this->noTypoMode = $oldNoTypoMode;
		//$this->quotesOpened = $oldQuotesopen;

		return true;
	}

	protected function preformatted(&$content = '', $insideTag = null){
		while($this->curChClass){
			if($this->curCh == '<'){
				$tag = '';
				$this->saveState();
				// Пытаемся найти закрывающийся тег
				$isClosedTag = $this->tagClose($tag);
				// Возвращаемся назад, если тег был найден
				if($isClosedTag) $this->restoreState();
				// Если закрылось то, что открылось - заканчиваем и возвращаем true
				if($isClosedTag && $tag == $insideTag) return;
			}
			$content.= isset($this->entities2[$this->curCh]) ? $this->entities2[$this->curCh] : $this->curCh;
			$this->getCh();
		}
	}

	protected function callback(&$content = '', $insideTag = null){
		while($this->curChClass){
			if($this->curCh == '<'){
				$tag = '';
				$this->saveState();
				// Пытаемся найти закрывающийся тег
				$isClosedTag = $this->tagClose($tag);
				// Возвращаемся назад, если тег был найден
				if($isClosedTag) $this->restoreState();
				// Если закрылось то, что открылось - заканчиваем и возвращаем true
				if($isClosedTag && $tag == $insideTag) {
					if ($callback = $this->tagsRules[$tag][self::TR_TAG_CALLBACK]) {
						$content = call_user_func($callback, $content);
					}
					return;
				}
			}
			$content.= $this->curCh;
			$this->getCh();
		}
	}

	protected function tagOpen(&$name, &$params, &$short = false){
		$restore = $this->saveState();

		// Открытие
		if(!$this->matchCh('<')) return false;
		$this->skipSpaces();
		if(!$this->name($name)){
			$this->restoreState();
			return false;
		}
		$name=mb_strtolower($name, 'UTF-8');
		// Пробуем получить список атрибутов тега
		if($this->curCh != '>' && $this->curCh != '/') $this->tagParams($params);

		// Короткая запись тега
		$short = !empty($this->tagsRules[$name][self::TR_TAG_SHORT]);

		// Short && XHTML && !Slash || Short && !XHTML && !Slash = ERROR
		$slash = $this->matchCh('/');
		//if(($short && $this->isXHTMLMode && !$slash) || (!$short && !$this->isXHTMLMode && $slash)){
		if(!$short && $slash){
			$this->restoreState();
			return false;
		}

		$this->skipSpaces();

		// Закрытие
		if(!$this->matchCh('>')) {
			$this->restoreState($restore);
			return false;
		}

		$this->skipSpaces();
		return true;
	}


	protected function tagParams(&$params = array()){
		$name = null;
		$value = null;
		while($this->tagParam($name, $value)){
			$params[$name] = $value;
			$name = ''; $value = '';
		}
		return count($params) > 0;
	}

	protected function tagParam(&$name, &$value){
		$this->saveState();
		if(!$this->name($name, true)) return false;

		if(!$this->matchCh('=', true)){
			// Стремная штука - параметр без значения <input type="checkbox" checked>, <td nowrap class=b>
			if(($this->curCh=='>' || ($this->curChClass & self::LAT) == self::LAT)){
				$value = $name;
				return true;
			} else {
				$this->restoreState();
				return false;
			}
		}

		$quote = $this->matchChClass(self::TAG_QUOTE, true);

		if(!$this->tagParamValue($value, $quote)){
			$this->restoreState();
			return false;
		}

		if($quote && !$this->matchCh($quote, true)){
			$this->restoreState();
			return false;
		}

		$this->skipSpaces();
		return true;
	}

	protected function tagParamValue(&$value, $quote){
		if($quote !== false){
			// Нормальный параметр с кавычкамию Получаем пока не кавычки и не конец
			$escape = false;
			while($this->curChClass && ($this->curCh != $quote || $escape)){
				$escape = false;
				// Экранируем символы HTML которые не могут быть в параметрах
				$value.=isset($this->entities1[$this->curCh]) ? $this->entities1[$this->curCh] : $this->curCh;
				// Символ ескейпа <a href="javascript::alert(\"hello\")">
				if($this->curCh == '\\') $escape = true;
				$this->getCh();
			}
		} else {
			// долбаный параметр без кавычек. получаем его пока не пробел и не > и не конец
			while($this->curChClass && !($this->curChClass & self::SPACE) && $this->curCh != '>'){
				// Экранируем символы HTML которые не могут быть в параметрах
				$value.=isset($this->entities1[$this->curCh]) ? $this->entities1[$this->curCh] : $this->curCh;
				$this->getCh();
			}
		}

		return true;
	}

	protected function tagClose(&$name){
		$this->saveState();
		if(!$this->matchCh('<')) return false;
		$this->skipSpaces();
		if(!$this->matchCh('/')) {
			$this->restoreState();
			return false;
		}
		$this->skipSpaces();
		if(!$this->name($name)){
			$this->restoreState();
			return false;
		}
		$name=mb_strtolower($name, 'UTF-8');
		$this->skipSpaces();
		if(!$this->matchCh('>')) {
			$this->restoreState();
			return false;
		}
		return true;
	}

	protected function makeTag($tag, $params, $content, $short, $parentTag = null){
		$this->curParentTag=$parentTag;
		$tag = mb_strtolower($tag, 'UTF-8');

		// Получаем правила фильтрации тега
		$tagRules = isset($this->tagsRules[$tag]) ? $this->tagsRules[$tag] : null;

		// Проверка - родительский тег - контейнер, содержащий только другие теги (ul, table, etc)
		$parentTagIsContainer = $parentTag && isset($this->tagsRules[$parentTag][self::TR_TAG_CONTAINER]);

		// Вырезать тег вместе с содержанием
		if($tagRules && isset($this->tagsRules[$tag][self::TR_TAG_CUT])) return '';

		// Позволен ли тег
		if(!$tagRules || empty($tagRules[self::TR_TAG_ALLOWED])) return $parentTagIsContainer ? '' : $content;

		// Если тег находится внутри другого - может ли он там находится?
		if($parentTagIsContainer){
			if(!isset($this->tagsRules[$parentTag][self::TR_TAG_CHILD_TAGS][$tag])) return '';
		}

		// Тег может находится только внтури другого тега
		if(isset($tagRules[self::TR_TAG_CHILD])){
			if(!isset($tagRules[self::TR_TAG_PARENT][$parentTag])) return $content;
		}


		$resParams = array();
		foreach($params as $param=>$value){
			$param = mb_strtolower($param, 'UTF-8');
			$value = trim($value);
			if($value == '') continue;

			// Атрибут тега разрешён? Какие возможны значения? Получаем список правил
			$paramAllowedValues = isset($tagRules[self::TR_PARAM_ALLOWED][$param]) ? $tagRules[self::TR_PARAM_ALLOWED][$param] : false;
			if(empty($paramAllowedValues)) continue;

			// Если есть список разрешённых параметров тега
			if(is_array($paramAllowedValues) && !in_array($value, $paramAllowedValues)) {
				$this->eror("Недопустимое значение для атрибута тега $tag $param=$value");
				continue;
			// Если атрибут тега помечен как разрешённый, но правила не указаны - смотрим в массив стандартных правил для атрибутов
			} elseif($paramAllowedValues === true && !empty($this->defaultTagParamRules[$param])){
				$paramAllowedValues = $this->defaultTagParamRules[$param];
			}

			if(is_string($paramAllowedValues)){
				switch($paramAllowedValues){
					case '#int':
						if(!is_numeric($value)) {
							$this->eror("Недопустимое значение для атрибута тега $tag $param=$value. Ожидалось число");
							continue(2);
						}
						break;

					case '#text':
						$value = htmlspecialchars($value);
						break;

					case '#link':
						// Ява-скрипт в ссылке
						if(preg_match('/javascript:/ui', $value)) {
							$this->eror('Попытка вставить JavaScript в URI');
							continue(2);
						}
						// Первый символ должен быть a-z0-9 или #!
						if(!preg_match('/^[a-z0-9\/\#]/ui', $value)) {
							$this->eror('URI: Первый символ адреса должен быть буквой или цифрой');
							continue(2);
						}
						// HTTP в начале если нет
						if(!preg_match('/^(http|https|ftp):\/\//ui', $value) && !preg_match('/^(\/|\#)/ui', $value) ) $value = 'http://'.$value;
						break;

					case '#image':
						// Ява-скрипт в пути к картинке
						if(preg_match('/javascript:/ui', $value)) {
							$this->eror('Попытка вставить JavaScript в пути к изображению');
							continue(2);
						}
						// HTTP в начале если нет
						if(!preg_match('/^http:\/\//ui', $value) && !preg_match('/^\//ui', $value)) $value = 'http://'.$value;
						break;

					default:
						$this->eror("Неверное описание атрибута тега в настройке Jevix: $param => $paramAllowedValues");
						continue(2);
						break;
				}
			}

			$resParams[$param] = $value;
		}

		// Проверка обязятельных параметров тега
		// Если нет обязательных параметров возвращаем только контент
		$requiredParams = isset($tagRules[self::TR_PARAM_REQUIRED]) ? array_keys($tagRules[self::TR_PARAM_REQUIRED]) : array();
		if($requiredParams){
			foreach($requiredParams as $requiredParam){
				if(!isset($resParams[$requiredParam])) return $content;
			}
		}

		// Автодобавляемые параметры
		if(!empty($tagRules[self::TR_PARAM_AUTO_ADD])){
		  foreach($tagRules[self::TR_PARAM_AUTO_ADD] as $name => $aValue) {
		      // If there isn't such attribute - setup it
		      if(!array_key_exists($name, $resParams) or ($aValue['rewrite'] and $resParams[$name] != $aValue['value'])) {
			  $resParams[$name] = $aValue['value'];
		      }
		  }
		}
		
		// Пустой некороткий тег удаляем кроме исключений
		if (!isset($tagRules[self::TR_TAG_IS_EMPTY]) or !$tagRules[self::TR_TAG_IS_EMPTY]) {
			if(!$short && $content == '') return '';
		}
		// Собираем тег
		$text='<'.$tag;

		// Параметры
		foreach($resParams as $param => $value) {
			if ($value != '') {
				$text.=' '.$param.'="'.$value.'"';
			}
		}
		
		// Закрытие тега (если короткий то без контента)
		$text.= $short && $this->isXHTMLMode ? '/>' : '>';
		if(isset($tagRules[self::TR_TAG_CONTAINER])) $text .= "\r\n";
		if(!$short) $text.= $content.'</'.$tag.'>';
		if($parentTagIsContainer) $text .= "\r\n";
		if($tag == 'br') $text.="\r\n";
		return $text;
	}

	protected function comment(){
		if(!$this->matchStr('<!--')) return false;
		return $this->skipUntilStr('-->');
	}

	protected function anyThing(&$content = '', $parentTag = null){
		$this->skipNL();
		while($this->curChClass){
			$tag = '';
			$params = null;
			$text = null;
			$shortTag = false;
			$name = null;

			// Если мы находимся в режиме тега без текста
			// пропускаем контент пока не встретится <
			if($this->state == self::STATE_INSIDE_NOTEXT_TAG && $this->curCh!='<'){
				$this->skipUntilCh('<');
			}

			// <Тег> кекст </Тег>
			if($this->curCh == '<' && $this->tag($tag, $params, $text, $shortTag)){
				// Преобразуем тег в текст
				$tagText = $this->makeTag($tag, $params, $text, $shortTag, $parentTag);
				$content.=$tagText;
				// Пропускаем пробелы после <br> и запрещённых тегов, которые вырезаются парсером
				if ($tag=='br') {
					$this->skipNL();
				} elseif ($tagText == ''){
					$this->skipSpaces();
				}

			// Коментарий <!-- -->
			} elseif($this->curCh == '<' && $this->comment()){
				continue;

			// Конец тега или символ <
			} elseif($this->curCh == '<') {
				// Если встречается <, но это не тег
				// то это либо закрывающийся тег либо знак <
				$this->saveState();
				if($this->tagClose($name)){
					// Если это закрывающийся тег, то мы делаем откат
					// и выходим из функции
					// Но если мы не внутри тега, то просто пропускаем его
					if($this->state == self::STATE_INSIDE_TAG || $this->state == self::STATE_INSIDE_NOTEXT_TAG) {
						$this->restoreState();
						return false;
					} else {
						$this->eror('Не ожидалось закрывающегося тега '.$name);
					}
				} else {
					if($this->state != self::STATE_INSIDE_NOTEXT_TAG) $content.=$this->entities2['<'];
					$this->getCh();
				}

			// Текст
			} elseif($this->text($text)){
				$content.=$text;
			}
		}

		return true;
	}

	/**
	 * Пропуск переводов строк подсчет кол-ва
	 *
	 * @param int $count ссылка для возвращения числа переводов строк
	 * @return boolean
	 */
	protected function skipNL(&$count = 0){
		if(!($this->curChClass & self::NL)) return false;
		$count++;
		$firstNL = $this->curCh;
		$nl = $this->getCh();
		while($this->curChClass & self::NL){
			// Если символ новый строки ткой же как и первый увеличиваем счетчик
			// новых строк. Это сработает при любых сочетаниях
			// \r\n\r\n, \r\r, \n\n - две перевода
			if($nl == $firstNL) $count++;
			$nl = $this->getCh();
			// Между переводами строки могут встречаться пробелы
			$this->skipSpaces();
		}
		return true;
	}

	protected function dash(&$dash){
		if($this->curCh != '-') return false;
		$dash = '';
		$this->saveState();
		$this->getCh();
		// Несколько подряд
		while($this->curCh == '-') $this->getCh();
		if(!$this->skipNL() && !$this->skipSpaces()){
			$this->restoreState();
			return false;
		}
		$dash = $this->dash;
		return true;
	}

	protected function punctuation(&$punctuation){
		if(!($this->curChClass & self::PUNCTUATUON)) return false;
		$this->saveState();
		$punctuation = $this->curCh;
		$this->getCh();

		// Проверяем ... и !!! и ?.. и !..
		if($punctuation == '.' && $this->curCh == '.'){
			while($this->curCh == '.') $this->getCh();
			$punctuation = $this->dotes;
		} elseif($punctuation == '!' && $this->curCh == '!'){
			while($this->curCh == '!') $this->getCh();
			$punctuation = '!!!';
		} elseif (($punctuation == '?' || $punctuation == '!') && $this->curCh == '.'){
			while($this->curCh == '.') $this->getCh();
			$punctuation.= '..';
		}

		// Далее идёт слово - добавляем пробел
		if($this->curChClass & self::RUS) {
			if($punctuation != '.') $punctuation.= ' ';
			return true;
		// Далее идёт пробел, перенос строки, конец текста
		} elseif(($this->curChClass & self::SPACE) || ($this->curChClass & self::NL) || !$this->curChClass){
			return true;
		} else {
			$this->restoreState();
			return false;
		}
	}

	protected function number(&$num){
		if(!(($this->curChClass & self::NUMERIC) == self::NUMERIC)) return false;
		$num = $this->curCh;
		$this->getCh();
		while(($this->curChClass & self::NUMERIC) == self::NUMERIC){
			$num.= $this->curCh;
			$this->getCh();
		}
		return true;
	}

	protected function htmlEntity(&$entityCh){
		if($this->curCh<>'&') return false;
		$this->saveState();
		$this->matchCh('&');
		if($this->matchCh('#')){
			$entityCode = 0;
			if(!$this->number($entityCode) || !$this->matchCh(';')){
				$this->restoreState();
				return false;
			}
			$entityCh = html_entity_decode("&#$entityCode;", ENT_COMPAT, 'UTF-8');
			return true;
		} else{
			$entityName = '';
			if(!$this->name($entityName) || !$this->matchCh(';')){
				$this->restoreState();
				return false;
			}
			$entityCh = html_entity_decode("&$entityName;", ENT_COMPAT, 'UTF-8');
			return true;
		}
	}

	/**
	 * Кавычка
	 *
	 * @param boolean $spacesBefore были до этого пробелы
	 * @param string $quote кавычка
	 * @param boolean $closed закрывающаяся
	 * @return boolean
	 */
	protected function quote($spacesBefore,  &$quote, &$closed){
		$this->saveState();
		$quote = $this->curCh;
		$this->getCh();
		// Если не одна кавычка ещё не была открыта и следующий символ - не буква - то это нифига не кавычка
		if($this->quotesOpened == 0 && !(($this->curChClass & self::ALPHA) || ($this->curChClass & self::NUMERIC))) {
			$this->restoreState();
			return false;
		}
		// Закрывается тогда, одна из кавычек была открыта и (до кавычки не было пробела или пробел или пунктуация есть после кавычки)
		// Или, если открыто больше двух кавычек - точно закрываем
		$closed =  ($this->quotesOpened >= 2) ||
			  (($this->quotesOpened >  0) &&
			   (!$spacesBefore || $this->curChClass & self::SPACE || $this->curChClass & self::PUNCTUATUON));
		return true;
	}

	protected function makeQuote($closed, $level){
		$levels = count($this->textQuotes);
		if($level > $levels) $level = $levels;
		return $this->textQuotes[$level][$closed ? 1 : 0];
	}


	protected function text(&$text){
		$text = '';
		//$punctuation = '';
		$dash = '';
		$newLine = true;
		$newWord = true; // Возможно начало нового слова
		$url = null;
		$href = null;

		// Включено типографирование?
		//$typoEnabled = true;
		$typoEnabled = !$this->noTypoMode;

		// Первый символ может быть <, это значит что tag() вернул false
		// и < к тагу не относится
		while(($this->curCh != '<') && $this->curChClass){
			$brCount = 0;
			$spCount = 0;
			$quote = null;
			$closed = false;
			$punctuation = null;
			$entity = null;

			$this->skipSpaces($spCount);

			// автопреобразование сущностей...
			if (!$spCount && $this->curCh == '&' && $this->htmlEntity($entity)){
				$text.= isset($this->entities2[$entity]) ? $this->entities2[$entity] : $entity;
			} elseif ($typoEnabled && ($this->curChClass & self::PUNCTUATUON) && $this->punctuation($punctuation)){
				// Автопунктуация выключена
				// Если встретилась пунктуация - добавляем ее
				// Сохраняем пробел перед точкой если класс следующий символ - латиница
				if($spCount && $punctuation == '.' && ($this->curChClass & self::LAT)) $punctuation = ' '.$punctuation;
				$text.=$punctuation;
				$newWord = true;
			} elseif ($typoEnabled && ($spCount || $newLine) && $this->curCh == '-' && $this->dash($dash)){
				// Тире
				$text.=$dash;
				$newWord = true;
			} elseif ($typoEnabled && ($this->curChClass & self::HTML_QUOTE) && $this->quote($spCount, $quote, $closed)){
				// Кавычки
				$this->quotesOpened+=$closed ? -1 : 1;
				// Исправляем ситуацию если кавычка закрыввается раньше чем открывается
				if($this->quotesOpened<0){
					$closed = false;
					$this->quotesOpened=1;
				}
				$quote = $this->makeQuote($closed, $closed ? $this->quotesOpened : $this->quotesOpened-1);
				if($spCount) $quote = ' '.$quote;
				$text.= $quote;
				$newWord = true;
			} elseif ($spCount>0){
				$text.=' ';
				// после пробелов снова возможно новое слово
				$newWord = true;
			} elseif ($this->isAutoBrMode && $this->skipNL($brCount)){
				// Перенос строки
				if ($this->curParentTag
				  and isset($this->tagsRules[$this->curParentTag])
				  and isset($this->tagsRules[$this->curParentTag][self::TR_TAG_NO_AUTO_BR])
				  and (is_null($this->openedTag) or isset($this->tagsRules[$this->openedTag][self::TR_TAG_NO_AUTO_BR]))
				  ) {
				  // пропускаем <br/>
				} else {
				  $br = $this->br.$this->nl;
				  $text.= $brCount == 1 ? $br : $br.$br;
				}
				// Помечаем что новая строка и новое слово
				$newLine = true;
				$newWord = true;
				// !!!Добавление слова
			} elseif ($newWord && $this->isAutoLinkMode && ($this->curChClass & self::LAT) && $this->openedTag!='a' && $this->url($url, $href)){
				// URL
				$text.= $this->makeTag('a' , array('href' => $href), $url, false);
			} elseif($this->curChClass & self::PRINATABLE){
				// Экранируем символы HTML которые нельзя сувать внутрь тега (но не те? которые не могут быть в параметрах)
				$text.=isset($this->entities2[$this->curCh]) ? $this->entities2[$this->curCh] : $this->curCh;
				$this->getCh();
				$newWord = false;
				$newLine = false;
				// !!!Добавление к слова
			} else {
				// Совершенно непечатаемые символы которые никуда не годятся
				$this->getCh();
			}
		}

		// Пробелы
		$this->skipSpaces();
		return $text != '';
	}

	protected function url(&$url, &$href){
		$this->saveState();
		$url = '';
		//$name = $this->name();
		//switch($name)
		$urlChMask = self::URL | self::ALPHA;

		if($this->matchStr('http://')){
			while($this->curChClass & $urlChMask){
				$url.= $this->curCh;
				$this->getCh();
			}

			if(!mb_strlen($url, 'UTF-8')) {
				$this->restoreState();
				return false;
			}

			$href = 'http://'.$url;
			return true;
		} elseif($this->matchStr('www.')){
			while($this->curChClass & $urlChMask){
				$url.= $this->curCh;
				$this->getCh();
			}

			if(!mb_strlen($url, 'UTF-8')) {
				$this->restoreState();
				return false;
			}

			$url = 'www.'.$url;
			$href = 'http://'.$url;
			return true;
		}
		$this->restoreState();
		return false;
	}

	protected function eror($message){
		$str = '';
		$strEnd = min($this->curPos + 8, $this->textLen);
		for($i = $this->curPos; $i < $strEnd; $i++){
			$str.=$this->textBuf[$i];
		}

		$this->errors[] = array(
			'message' => $message,
			'pos'     => $this->curPos,
			'ch'      => $this->curCh,
			'line'    => 0,
			'str'     => $str,
		);
	}
	/**
	 * Функция ord() для мультибайтовы строк
	 *
	 * @param string $c символ utf-8
	 * @return int код символа
	 */
	protected function uniord($c) {
	    $h = ord($c{0});
	    if ($h <= 0x7F) {
		return $h;
	    } else if ($h < 0xC2) {
		return false;
	    } else if ($h <= 0xDF) {
		return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
	    } else if ($h <= 0xEF) {
		return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
					 | (ord($c{2}) & 0x3F);
	    } else if ($h <= 0xF4) {
		return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
					 | (ord($c{2}) & 0x3F) << 6
					 | (ord($c{3}) & 0x3F);
	    } else {
		return false;
	    }
	}
	
	/**
	 * Функция chr() для мультибайтовы строк
	 *
	 * @param int $c код символа
	 * @return string символ utf-8
	 */
	protected function unichr($c) {
	    if ($c <= 0x7F) {
		return chr($c);
	    } else if ($c <= 0x7FF) {
		return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
	    } else if ($c <= 0xFFFF) {
		return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
					    . chr(0x80 | $c & 0x3F);
	    } else if ($c <= 0x10FFFF) {
		return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
					    . chr(0x80 | $c >> 6 & 0x3F)
					    . chr(0x80 | $c & 0x3F);
	    } else {
		return false;
	    }
	}
}