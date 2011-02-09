<?php

/*!* !insert_autogen_warning */

/*!* !silent
This is the uncompiled parser for the SilverStripe template language, PHP with special comments that define the parser. 
It gets run through the php-peg parser compiler to have those comments turned into code that match parts of the template language,
producing the executable version SSTemplateParser.php

See the php-peg docs for more information on the parser format, and how to convert this file into SSTemplateParser.php

TODO:
	Template comments - <%-- --%>
	$Iteration
	Partial cache blocks
	i18n - we dont support then deprecated _t() or sprintf(_t()) methods; or the new <% t %> block yet
	Add with and loop blocks
	Add Up and Top 
	More error detection?
 
This comment will not appear in the output
*/

// We want this to work when run by hand too
if (defined(THIRDPARTY_PATH)) {
	require THIRDPARTY_PATH . '/php-peg/Parser.php' ;
}
else {
	$base = dirname(__FILE__);
	require $base.'/../thirdparty/php-peg/Parser.php';
}

/**
This is the exception raised when failing to parse a template. Note that we don't currently do any static analysis, so we can't know
if the template will run, just if it's malformed. It also won't catch mistakes that still look valid.
*/
class SSTemplateParseException extends Exception {
	
	function __construct($message, $parser) {
		$prior = substr($parser->string, 0, $parser->pos);
		
		preg_match_all('/\r\n|\r|\n/', $prior, $matches);
		$line = count($matches[0])+1;
		
		parent::__construct("Parse error in template on line $line. Error was: $message");
	}
	
}

/**
This is the parser for the SilverStripe template language. It gets called on a string and uses a php-peg parser to match
that string against the language structure, building up the PHP code to execute that structure as it parses
*/
class SSTemplateParser extends Parser {

	protected $includeDebuggingComments = false;
	
	function construct($name) {
		$result = parent::construct($name);
		$result['tags'] = array();
		return $result;
	}
	
	function DLRBlockName() {
		return '-none-';
	}
	
	/*!* SSTemplateParser

	Word: / [A-Za-z_] [A-Za-z0-9_]* /
	Number: / [0-9]+ /
	Value: / [A-Za-z0-9_]+ /

	Arguments: :Argument ( < "," < :Argument )*
    */

	/** Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert strings to numbers when needed */
	function Arguments_Argument(&$res, $sub) {
		if (isset($res['php'])) $res['php'] .= ', ';
		else $res['php'] = '';
		
		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] : $sub['php'];
	}

	/*!*
	Call: Method:Word ( "(" < :Arguments? > ")" )?

	LookupStep: :Call &"."
	LastLookupStep: :Call

	Lookup: LookupStep ("." LookupStep)* "." LastLookupStep | LastLookupStep
	*/
	
	function Lookup__construct(&$res) {
		$res['php'] = '$item';
		$res['LookupSteps'] = array();
	}
	
	function Lookup_AddLookupStep(&$res, $sub, $method) {
		$res['LookupSteps'][] = $sub;
		
		$property = $sub['Call']['Method']['text'];
		
		if (isset($sub['Call']['Arguments']) && $arguments = $sub['Call']['Arguments']['php']) {
			$res['php'] .= "->$method('$property', array($arguments), true)";
		}
		else {
			$res['php'] .= "->$method('$property', null, true)";
		}
	}

	function Lookup_LookupStep(&$res, $sub) {
		$this->Lookup_AddLookupStep($res, $sub, 'obj');
	}

	function Lookup_LastLookupStep(&$res, $sub) {
		$this->Lookup_AddLookupStep($res, $sub, 'XML_val');
	}

	/*!*
	SimpleInjection: '$' :Lookup
	BracketInjection: '{$' :Lookup "}"
	Injection: BracketInjection | SimpleInjection
	*/
	function Injection_STR(&$res, $sub) {
		$res['php'] = '$val .= '. $sub['Lookup']['php'] . ';';
	}

	/*!*
	DollarMarkedLookup: BracketInjection | SimpleInjection
	*/
	function DollarMarkedLookup_STR(&$res, $sub) {
		$res['Lookup'] = $sub['Lookup'];
	}

	/*!*
	QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q'
	
	FreeString: /[^,)%!=|&]+/
	
	Argument:
		:DollarMarkedLookup |
		:QuotedString |
		:Lookup !(< FreeString)|
		:FreeString
	*/
	function Argument_DollarMarkedLookup(&$res, $sub) {
		$res['ArgumentMode'] = 'lookup';
		$res['php'] = $sub['Lookup']['php'];
	}

	function Argument_QuotedString(&$res, $sub) {
		$res['ArgumentMode'] = 'string';
		$res['php'] = "'" . $sub['String']['text'] . "'";
	}

	function Argument_Lookup(&$res, $sub) {
		if (count($sub['LookupSteps']) == 1 && !isset($sub['LookupSteps'][0]['Call']['Arguments'])) {
			$res['ArgumentMode'] = 'default';
			$res['lookup_php'] = $sub['php'];
			$res['string_php'] = "'".$sub['LookupSteps'][0]['Call']['Method']['text']."'";
		}
		else {
			$res['ArgumentMode'] = 'lookup';
			$res['php'] = $sub['php'];
		}
	}
	
	function Argument_FreeString(&$res, $sub) {
		$res['ArgumentMode'] = 'string';
		$res['php'] = "'" . $sub['text'] . "'";
	}
	
	/*!*
	ComparisonOperator: "==" | "!=" | "="

	Comparison: Argument < ComparisonOperator > Argument
	*/
	function Comparison_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'default') {
			if (isset($res['php'])) $res['php'] .= $sub['string_php'];
			else $res['php'] = $sub['lookup_php'];
		}	
		else {
			if (!isset($res['php'])) $res['php'] = '';
			$res['php'] .= $sub['php'];
		}
	}

	function Comparison_ComparisonOperator(&$res, $sub) {
		$res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
	}

	/*!*
	PresenceCheck: Argument
	*/
	function PresenceCheck_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'string') {
			$res['php'] = '((bool)'.$sub['php'].')';
		}
		else {
			$php = ($sub['ArgumentMode'] == 'default' ? $sub['lookup_php'] : $sub['php']);
			// TODO: kinda hacky - maybe we need a way to pass state down the parse chain so
			// Lookup_LastLookupStep and Argument_BareWord can produce hasValue instead of XML_val
			$res['php'] = str_replace('->XML_val', '->hasValue', $php);
		}
	}

	/*!*	
	IfArgumentPortion: Comparison | PresenceCheck
	*/
	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/*!*	
	BooleanOperator: "||" | "&&"
	
	IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )*
	*/
	function IfArgument__construct(&$res){
		$res['php'] = '';
	}
	function IfArgument_IfArgumentPortion(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function IfArgument_BooleanOperator(&$res, $sub) {
		$res['php'] .= $sub['text'];
	}

	/*!*	
	IfPart: '<%' < 'if' < :IfArgument > '%>' :Template?
	ElseIfPart: '<%' < 'else_if' < :IfArgument > '%>' :Template?
	ElsePart: '<%' < 'else' > '%>' :Template?

	If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>'
	*/
	function If__construct(&$res) {
		$res['BlockName'] = 'if';
	}
	
	function If_IfPart(&$res, $sub) {
		$res['php'] = 
			'if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				$sub['Template']['php'] . PHP_EOL .
			'}';
	} 

	function If_ElseIfPart(&$res, $sub) {
		$res['php'] .= 
			'else if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				$sub['Template']['php'] . PHP_EOL .
			'}';
	}

	function If_ElsePart(&$res, $sub) {
		$res['php'] .= 
			'else { ' . PHP_EOL . 
				$sub['Template']['php'] . PHP_EOL . 
			'}';
	}

	/*!*
	Require: '<%' < 'require' [ Call:(Method:Word "(" < :Arguments  > ")") > '%>'
	*/
	function Require_Call(&$res, $sub) {
		$res['php'] = "Requirements::".$sub['Method']['text'].'('.$sub['Arguments']['php'].');';
	}
	
	/*!*
	BlockArguments: :Argument ( < "," < :Argument)* 
	
	NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require") ] )
	
	ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' :Template? '<%' < 'end_' '$BlockName' > '%>'
	*/
	function ClosedBlock__construct(&$res) {
		$res['ArgumentCount'] = 0;
	}
	
	function ClosedBlock_BlockArguments(&$res, $sub) {
		if (isset($sub['Argument']['ArgumentMode'])) {
			$res['Arguments'] = array($sub['Argument']);
			$res['ArgumentCount'] = 1;
		}
		else {
			$res['Arguments'] = $sub['Argument'];
			$res['ArgumentCount'] = count($res['Arguments']);
		}
	}
	
	function ClosedBlock__finalise(&$res) {
		$blockname = $res['BlockName']['text'];
		
		$method = 'ClosedBlock_Handle_'.ucfirst(strtolower($blockname));
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown closed block "'.$blockname.'" encountered. Perhaps you are not supposed to close this block, or have mis-spelled it?', $this);
		}
	}

	function ClosedBlock_Handle_Control(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one argument only.', $this);
		}
		
		$arg = $res['Arguments'][0];
		if ($arg['ArgumentMode'] == 'string') {
			throw new SSTemplateParseException('Control block cant take string as argument.', $this);
		}
		
		$on = str_replace('->XML_val', '->obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		return 
			'array_push($itemStack, $item); if($loop = '.$on.') foreach($loop as $key => $item) {' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'} $item = array_pop($itemStack); ';
	}
	
	/*!*
	OpenBlock: '<%' < !NotBlockTag OpenBlockName:Word ( [ :BlockArguments ] )? > '%>'
	*/
	function OpenBlock__construct(&$res) {
		$res['ArgumentCount'] = 0;
	}
	
	function OpenBlock_BlockArguments(&$res, $sub) {
		if (isset($sub['Argument']['ArgumentMode'])) {
			$res['Arguments'] = array($sub['Argument']);
			$res['ArgumentCount'] = 1;
		}
		else {
			$res['Arguments'] = $sub['Argument'];
			$res['ArgumentCount'] = count($res['Arguments']);
		}
	}
	
	function OpenBlock__finalise(&$res) {
		$blockname = $res['OpenBlockName']['text'];
	
		$method = 'OpenBlock_Handle_'.ucfirst(strtolower($blockname));
		if (method_exists($this, $method)) $res['php'] = $this->$method($res);
		else {
			throw new SSTemplateParseException('Unknown open block "'.$blockname.'" encountered. Perhaps you missed the closing tag or have mis-spelled it?', $this);
		}
	}
	
	function OpenBlock_Handle_Include(&$res) {
		if ($res['ArgumentCount'] != 1) throw new SSTemplateParseException('Include takes exactly one argument', $this);
		
		$arg = $res['Arguments'][0];
		$php = ($arg['ArgumentMode'] == 'default') ? $arg['string_php'] : $arg['php'];
		
		if($this->includeDebuggingComments) { // Add include filename comments on dev sites
			return 
				'$val .= \'<!-- include '.$php.' -->\';'. "\n".
				'$val .= SSViewer::parse_template('.$php.', $item);'. "\n".
				'$val .= \'<!-- end include '.$php.' -->\';'. "\n";
		}
		else {
			return 
				'$val .= SSViewer::execute_template('.$php.', $item);'. "\n";
		}
	}
	
	function OpenBlock_Handle_Debug(&$res) {
		if ($res['ArgumentCount'] == 0) return 'Debug::show($item);';
		else if ($res['ArgumentCount'] == 1) {
			$arg = $res['Arguments'][0];
			
			if ($arg['ArgumentMode'] == 'string') return 'Debug::show('.$arg['php'].');';
			
			$php = ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php'];
			return '$val .= Debug::show('.str_replace('FINALGET!', 'cachedCall', $php).');';
		}
		else {
			throw new SSTemplateParseException('Debug takes 0 or 1 argument only.', $this);
		}
	}

	function OpenBlock_Handle_Base_tag(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Base_tag takes no arguments', $this);
		return '$val .= SSViewer::get_base_tag($val);';
	}

	function OpenBlock_Handle_Current_page(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Current_page takes no arguments', $this);
		return '$val .= $_SERVER[SCRIPT_URL];';
	}
	
	/*!*
	MismatchedEndBlock: '<%' < 'end_' !'$BlockName' :Word > '%>'
	*/
	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_'.$blockname.' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/*!*	
	MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' )
	*/
	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?", $this);
	}
	
	/*!*
	MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' )
	*/
	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an argument to one?", $this);
	}
	
	/*!*
	MalformedBlock: MalformedOpenTag | MalformedCloseTag
	*/

	/*!*
	Comment: "<%--" (!"--%>" /./)+ "--%>"
	*/
	function Comment__construct(&$res) {
		$res['php'] = '';
	}
	
	/*!*
	Text: /
		(
			(\\.) |              # Any escaped character
			([^<${]) |           # Any character that isn't <, $ or {
			(<[^%]) |            # < if not followed by %
			($[^A-Za-z_]) |      # $ if not followed by A-Z, a-z or _
			({[^$]) |            # { if not followed by $
			({$[^A-Za-z_])       # {$ if not followed A-Z, a-z or _
		)+
		/

	Template: (Comment | If | Require | ClosedBlock | OpenBlock | MalformedBlock | Injection | Text)+
	*/
	function Template__construct(&$res) {
		$res['php'] = '';
	}

	function Template_Text(&$res, $sub) {
		$text = $sub['text'];
		$text = preg_replace(
			'/href\s*\=\s*\"\#/', 
			'href="<?= SSViewer::{dlr}options[\'rewriteHashlinks\'] ? Convert::raw2att( {dlr}_SERVER[\'REQUEST_URI\'] ) : "" ?>#', 
			$text
		);

		// TODO: using heredocs means any left over $ symbols will trigger PHP lookups, as will any escapes
		// Will it break backwards compatibility to use ' quoted strings, and escape just the ' characters?
		
		$res['php'] .=
			'$val .= <<<SSVIEWER' . PHP_EOL .
				$text . PHP_EOL .
			'SSVIEWER;' . PHP_EOL ;				
	}
	
	function Template_STR(&$res, $sub) {
		$res['php'] .= $sub['php'] . PHP_EOL ;
	}
	
	/*!*
	TopTemplate: (Comment | If | Require | ClosedBlock | OpenBlock | MalformedBlock | MismatchedEndBlock | Injection | Text)+
	*/
	function TopTemplate__construct(&$res) {
		$res['php'] = "<?php" . PHP_EOL;
	}

	function TopTemplate_Text(&$res, $sub) { return $this->Template_Text($res, $sub); }
	function TopTemplate_STR(&$res, $sub) { return $this->Template_STR($res, $sub); }

	static function compileString($string, $templateName = "", $includeDebuggingComments=false) {
		$parser = new SSTemplateParser($string);
		$parser->includeDebuggingComments = $includeDebuggingComments;

		// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF (and other encodings) properly
		if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $parser->pos = 3;
		
		$result =  $parser->match_TopTemplate();
		if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $parser);

		$code = $result['php'];
		
		if($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
			// If this template is a full HTML page, then put the comments just inside the HTML tag to prevent any IE glitches
			if(stripos($code, "<html") !== false) {
				$code = preg_replace('/(<html[^>]*>)/i', "\\1<!-- template $templateName -->", $code);
				$code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->\\1", $code);
			} else {
				$code = "<!-- template $templateName -->\n" . $code . "\n<!-- end template $templateName -->";
			}
		}	
		
		return $code;
	}
	
	static function compileFile($template) {
		return self::compileString(file_get_contents($template));
	}
}
