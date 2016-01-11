<?php

/*!* !insert_autogen_warning */

/*!* !silent
This is the uncompiled parser for the SilverStripe template language, PHP with special comments that define the
parser.

It gets run through the php-peg parser compiler to have those comments turned into code that match parts of the
template language, producing the executable version SSTemplateParser.php

To recompile after changing this file, run this from the 'framework/view' directory via command line (in most cases
this is: sapphire/view):

	php ../thirdparty/php-peg/cli.php SSTemplateParser.php.inc > SSTemplateParser.php

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
	require_once(THIRDPARTY_PATH . '/php-peg/Parser.php');
}
else {
	$base = dirname(__FILE__);
	require_once($base.'/../thirdparty/php-peg/Parser.php');
}

/**
 * This is the exception raised when failing to parse a template. Note that we don't currently do any static analysis,
 * so we can't know if the template will run, just if it's malformed. It also won't catch mistakes that still look
 * valid.
 *
 * @package framework
 * @subpackage view
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
  * This is the parser for the SilverStripe template language. It gets called on a string and uses a php-peg parser
  * to match that string against the language structure, building up the PHP code to execute that structure as it
  * parses
  *
  * The $result array that is built up as part of the parsing (see thirdparty/php-peg/README.md for more on how
  * parsers build results) has one special member, 'php', which contains the php equivalent of that part of the
  * template tree.
  *
  * Some match rules generate alternate php, or other variations, so check the per-match documentation too.
  *
  * Terms used:
  *
  * Marked: A string or lookup in the template that has been explictly marked as such - lookups by prepending with
  * "$" (like $Foo.Bar), strings by wrapping with single or double quotes ('Foo' or "Foo")
  *
  * Bare: The opposite of marked. An argument that has to has it's type inferred by usage and 2.4 defaults.
  *
  * Example of using a bare argument for a loop block: <% loop Foo %>
  *
  * Block: One of two SS template structures. The special characters "<%" and "%>" are used to wrap the opening and
  * (required or forbidden depending on which block exactly) closing block marks.
  *
  * Open Block: An SS template block that doesn't wrap any content or have a closing end tag (in fact, a closing end
  * tag is forbidden)
  *
  * Closed Block: An SS template block that wraps content, and requires a counterpart <% end_blockname %> tag
  *
  * Angle Bracket: angle brackets "<" and ">" are used to eat whitespace between template elements
  * N: eats white space including newlines (using in legacy _t support)
  *
  * @package framework
  * @subpackage view
  */
class SSTemplateParser extends Parser implements TemplateParser {

	/**
	 * @var bool - Set true by SSTemplateParser::compileString if the template should include comments intended
	 * for debugging (template source, included files, etc)
	 */
	protected $includeDebuggingComments = false;

	/**
	 * Stores the user-supplied closed block extension rules in the form:
	 * array(
	 *   'name' => function (&$res) {}
	 * )
	 * See SSTemplateParser::ClosedBlock_Handle_Loop for an example of what the callable should look like
	 * @var array
	 */
	protected $closedBlocks = array();

	/**
	 * Stores the user-supplied open block extension rules in the form:
	 * array(
	 *   'name' => function (&$res) {}
	 * )
	 * See SSTemplateParser::OpenBlock_Handle_Base_tag for an example of what the callable should look like
	 * @var array
	 */
	protected $openBlocks = array();

	/**
	 * Allow the injection of new closed & open block callables
	 * @param array $closedBlocks
	 * @param array $openBlocks
	 */
	public function __construct($closedBlocks = array(), $openBlocks = array()) {
		$this->setClosedBlocks($closedBlocks);
		$this->setOpenBlocks($openBlocks);
	}

	/**
	 * Override the function that constructs the result arrays to also prepare a 'php' item in the array
	 */
	function construct($matchrule, $name, $arguments = null) {
		$res = parent::construct($matchrule, $name, $arguments);
		if (!isset($res['php'])) $res['php'] = '';
		return $res;
	}

	/**
	 * Set the closed blocks that the template parser should use
	 *
	 * This method will delete any existing closed blocks, please use addClosedBlock if you don't
	 * want to overwrite
	 * @param array $closedBlocks
	 * @throws InvalidArgumentException
	 */
	public function setClosedBlocks($closedBlocks) {
		$this->closedBlocks = array();
		foreach ((array) $closedBlocks as $name => $callable) {
			$this->addClosedBlock($name, $callable);
		}
	}

	/**
	 * Set the open blocks that the template parser should use
	 *
	 * This method will delete any existing open blocks, please use addOpenBlock if you don't
	 * want to overwrite
	 * @param array $openBlocks
	 * @throws InvalidArgumentException
	 */
	public function setOpenBlocks($openBlocks) {
		$this->openBlocks = array();
		foreach ((array) $openBlocks as $name => $callable) {
			$this->addOpenBlock($name, $callable);
		}
	}

	/**
	 * Add a closed block callable to allow <% name %><% end_name %> syntax
	 * @param string $name The name of the token to be used in the syntax <% name %><% end_name %>
	 * @param callable $callable The function that modifies the generation of template code
	 * @throws InvalidArgumentException
	 */
	public function addClosedBlock($name, $callable) {
		$this->validateExtensionBlock($name, $callable, 'Closed block');
		$this->closedBlocks[$name] = $callable;
	}

	/**
	 * Add a closed block callable to allow <% name %> syntax
	 * @param string $name The name of the token to be used in the syntax <% name %>
	 * @param callable $callable The function that modifies the generation of template code
	 * @throws InvalidArgumentException
	 */
	public function addOpenBlock($name, $callable) {
		$this->validateExtensionBlock($name, $callable, 'Open block');
		$this->openBlocks[$name] = $callable;
	}

	/**
	 * Ensures that the arguments to addOpenBlock and addClosedBlock are valid
	 * @param $name
	 * @param $callable
	 * @param $type
	 * @throws InvalidArgumentException
	 */
	protected function validateExtensionBlock($name, $callable, $type) {
		if (!is_string($name)) {
			throw new InvalidArgumentException(
				sprintf(
					"Name argument for %s must be a string",
					$type
				)
			);
		} elseif (!is_callable($callable)) {
			throw new InvalidArgumentException(
				sprintf(
					"Callable %s argument named '%s' is not callable",
					$type,
					$name
				)
			);
		}
	}

	/*!* SSTemplateParser

	# Template is any structurally-complete portion of template (a full nested level in other words). It's the
	# primary matcher, and is used by all enclosing blocks, as well as a base for the top level.
	# Any new template elements need to be included in this list, if they are to work.

	Template: (Comment | Translate | If | Require | CacheBlock | UncachedBlock | OldI18NTag | Include | ClosedBlock |
		OpenBlock | MalformedBlock | Injection | Text)+
	*/
	function Template_STR(&$res, $sub) {
		$res['php'] .= $sub['php'] . PHP_EOL ;
	}

	/*!*

	Word: / [A-Za-z_] [A-Za-z0-9_]* /
	Number: / [0-9]+ /
	Value: / [A-Za-z0-9_]+ /

	# CallArguments is a list of one or more comma seperated "arguments" (lookups or strings, either bare or marked)
	# as passed to a Call within brackets

	CallArguments: :Argument ( < "," < :Argument )*
	*/

	/**
	 * Values are bare words in templates, but strings in PHP. We rely on PHP's type conversion to back-convert
	 * strings to numbers when needed.
	 */
	function CallArguments_Argument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ', ';

		$res['php'] .= ($sub['ArgumentMode'] == 'default') ? $sub['string_php'] :
			str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/*!*

	# Call is a php-style function call, e.g. Method(Argument, ...). Unlike PHP, the brackets are optional if no
	# arguments are passed

	Call: Method:Word ( "(" < :CallArguments? > ")" )?

	# A lookup is a lookup of a value on the current scope object. It's a sequence of calls seperated by "."
	# characters. This final call in the sequence needs handling specially, as different structures need different
	# sorts of values, which require a different final method to be called to get the right return value

	LookupStep: :Call &"."
	LastLookupStep: :Call

	Lookup: LookupStep ("." LookupStep)* "." LastLookupStep | LastLookupStep
	*/

	function Lookup__construct(&$res) {
		$res['php'] = '$scope->locally()';
		$res['LookupSteps'] = array();
	}

	/**
	 * The basic generated PHP of LookupStep and LastLookupStep is the same, except that LookupStep calls 'obj' to
	 * get the next ViewableData in the sequence, and LastLookupStep calls different methods (XML_val, hasValue, obj)
	 * depending on the context the lookup is used in.
	 */
	function Lookup_AddLookupStep(&$res, $sub, $method) {
		$res['LookupSteps'][] = $sub;

		$property = $sub['Call']['Method']['text'];

		if (isset($sub['Call']['CallArguments']) && $arguments = $sub['Call']['CallArguments']['php']) {
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
		$this->Lookup_AddLookupStep($res, $sub, '$$FINAL');
	}


	/*!*

	# New Translatable Syntax
	# <%t Entity DefaultString is Context name1=string name2=$functionCall
	# (This is a new way to call translatable strings. The parser transforms this into a call to the _t() method)

	Translate: "<%t" < Entity < (Default:QuotedString)? < (!("is" "=") < "is" < Context:QuotedString)? <
		(InjectionVariables)? > "%>"
	InjectionVariables: (< InjectionName:Word "=" Argument)+
	Entity: / [A-Za-z_] [\w\.]* /
	*/

	function Translate__construct(&$res) {
		$res['php'] = '$val .= _t(';
	}

	function Translate_Entity(&$res, $sub) {
		$res['php'] .= "'$sub[text]'";
	}

	function Translate_Default(&$res, $sub) {
		$res['php'] .= ",$sub[text]";
	}

	function Translate_Context(&$res, $sub) {
		$res['php'] .= ",$sub[text]";
	}

	function Translate_InjectionVariables(&$res, $sub) {
		$res['php'] .= ",$sub[php]";
	}

	function Translate__finalise(&$res) {
		$res['php'] .= ');';
	}

	function InjectionVariables__construct(&$res) {
		$res['php'] = "array(";
	}

	function InjectionVariables_InjectionName(&$res, $sub) {
		$res['php'] .= "'$sub[text]'=>";
	}

	function InjectionVariables_Argument(&$res, $sub) {
		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']) . ',';
	}

	function InjectionVariables__finalise(&$res) {
		if (substr($res['php'], -1) == ',') $res['php'] = substr($res['php'], 0, -1); //remove last comma in the array
		$res['php'] .= ')';
	}


	/*!*

	# Injections are where, outside of a block, a value needs to be inserted into the output. You can either
	# just do $Foo, or {$Foo} if the surrounding text would cause a problem (e.g. {$Foo}Bar)

	SimpleInjection: '$' :Lookup
	BracketInjection: '{$' :Lookup "}"
	Injection: BracketInjection | SimpleInjection
	*/
	function Injection_STR(&$res, $sub) {
		$res['php'] = '$val .= '. str_replace('$$FINAL', 'XML_val', $sub['Lookup']['php']) . ';';
	}

	/*!*

	# Inside a block's arguments you can still use the same format as a simple injection ($Foo). In this case
	# it marks the argument as being a lookup, not a string (if it was bare it might still be used as a lookup,
	# but that depends on where it's used, a la 2.4)

	DollarMarkedLookup: SimpleInjection
	*/
	function DollarMarkedLookup_STR(&$res, $sub) {
		$res['Lookup'] = $sub['Lookup'];
	}

	/*!*

	# Inside a block's arguments you can explictly mark a string by surrounding it with quotes (single or double,
	# but they must be matching). If you do, inside the quote you can escape any character, but the only character
	# that _needs_ escaping is the matching closing quote

	QuotedString: q:/['"]/   String:/ (\\\\ | \\. | [^$q\\])* /   '$q'

	# In order to support 2.4's base syntax, we also need to detect free strings - strings not surrounded by
	# quotes, and containing spaces or punctuation, but supported as a single string. We support almost as flexible
	# a string as 2.4 - we don't attempt to determine the closing character by context, but just break on any
	# character which, in some context, would indicate the end of a free string, regardless of if we're actually in
	# that context or not

	FreeString: /[^,)%!=><|&]+/

	# An argument - either a marked value, or a bare value, prefering lookup matching on the bare value over
	# freestring matching as long as that would give a successful parse

	Argument:
		:DollarMarkedLookup |
		:QuotedString |
		:Lookup !(< FreeString)|
		:FreeString
	*/

	/**
	 * If we get a bare value, we don't know enough to determine exactly what php would be the translation, because
	 * we don't know if the position of use indicates a lookup or a string argument.
	 *
	 * Instead, we record 'ArgumentMode' as a member of this matches results node, which can be:
	 *   - lookup if this argument was unambiguously a lookup (marked as such)
	 *   - string is this argument was unambiguously a string (marked as such, or impossible to parse as lookup)
	 *   - default if this argument needs to be handled as per 2.4
	 *
	 * In the case of 'default', there is no php member of the results node, but instead 'lookup_php', which
	 * should be used by the parent if the context indicates a lookup, and 'string_php' which should be used
	 * if the context indicates a string
	 */

	function Argument_DollarMarkedLookup(&$res, $sub) {
		$res['ArgumentMode'] = 'lookup';
		$res['php'] = $sub['Lookup']['php'];
	}

	function Argument_QuotedString(&$res, $sub) {
		$res['ArgumentMode'] = 'string';
		$res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
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
		$res['php'] = "'" . str_replace("'", "\\'", trim($sub['text'])) . "'";
	}

	/*!*

	# if and else_if blocks allow basic comparisons between arguments

	ComparisonOperator: "!=" | "==" | ">=" | ">" | "<=" | "<" | "="

	Comparison: Argument < ComparisonOperator > Argument
	*/
	function Comparison_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'default') {
			if (!empty($res['php'])) $res['php'] .= $sub['string_php'];
			else $res['php'] = str_replace('$$FINAL', 'XML_val', $sub['lookup_php']);
		}
		else {
			$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
		}
	}

	function Comparison_ComparisonOperator(&$res, $sub) {
		$res['php'] .= ($sub['text'] == '=' ? '==' : $sub['text']);
	}

	/*!*

	# If a comparison operator is not used in an if or else_if block, then the statement is a 'presence check',
	# which checks if the argument given is present or not. For explicit strings (which were not allowed in 2.4)
	# this falls back to simple truthiness check

	PresenceCheck: (Not:'not' <)? Argument
	*/
	function PresenceCheck_Not(&$res, $sub) {
		$res['php'] = '!';
	}

	function PresenceCheck_Argument(&$res, $sub) {
		if ($sub['ArgumentMode'] == 'string') {
			$res['php'] .= '((bool)'.$sub['php'].')';
		}
		else {
			$php = ($sub['ArgumentMode'] == 'default' ? $sub['lookup_php'] : $sub['php']);
			// TODO: kinda hacky - maybe we need a way to pass state down the parse chain so
			// Lookup_LastLookupStep and Argument_BareWord can produce hasValue instead of XML_val
			$res['php'] .= str_replace('$$FINAL', 'hasValue', $php);
		}
	}

	/*!*

	# if and else_if arguments are a series of presence checks and comparisons, optionally seperated by boolean
	# operators

	IfArgumentPortion: Comparison | PresenceCheck
	*/
	function IfArgumentPortion_STR(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/*!*

	# if and else_if arguments can be combined via these two boolean operators. No precendence overriding is
	# supported

	BooleanOperator: "||" | "&&"

	# This is the combination of the previous if and else_if argument portions

	IfArgument: :IfArgumentPortion ( < :BooleanOperator < :IfArgumentPortion )*
	*/
	function IfArgument_IfArgumentPortion(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function IfArgument_BooleanOperator(&$res, $sub) {
		$res['php'] .= $sub['text'];
	}

	/*!*

	# ifs are handled seperately from other closed block tags, because (A) their structure is different - they
	# can have else_if and else tags in between the if tag and the end_if tag, and (B) they have a different
	# argument structure to every other block

	IfPart: '<%' < 'if' [ :IfArgument > '%>' Template:$TemplateMatcher?
	ElseIfPart: '<%' < 'else_if' [ :IfArgument > '%>' Template:$TemplateMatcher?
	ElsePart: '<%' < 'else' > '%>' Template:$TemplateMatcher?

	If: IfPart ElseIfPart* ElsePart? '<%' < 'end_if' > '%>'
	*/
	function If_IfPart(&$res, $sub) {
		$res['php'] =
			'if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	}

	function If_ElseIfPart(&$res, $sub) {
		$res['php'] .=
			'else if (' . $sub['IfArgument']['php'] . ') { ' . PHP_EOL .
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	}

	function If_ElsePart(&$res, $sub) {
		$res['php'] .=
			'else { ' . PHP_EOL .
				(isset($sub['Template']) ? $sub['Template']['php'] : '') . PHP_EOL .
			'}';
	}

	/*!*

	# The require block is handled seperately to the other open blocks as the argument syntax is different
	# - must have one call style argument, must pass arguments to that call style argument

	Require: '<%' < 'require' [ Call:(Method:Word "(" < :CallArguments  > ")") > '%>'
	*/
	function Require_Call(&$res, $sub) {
		$res['php'] = "Requirements::".$sub['Method']['text'].'('.$sub['CallArguments']['php'].');';
	}


	/*!*

	# Cache block arguments don't support free strings

	CacheBlockArgument:
	   !( "if " | "unless " )
		(
			:DollarMarkedLookup |
			:QuotedString |
			:Lookup
		)
	*/
	function CacheBlockArgument_DollarMarkedLookup(&$res, $sub) {
		$res['php'] = $sub['Lookup']['php'];
	}

	function CacheBlockArgument_QuotedString(&$res, $sub) {
		$res['php'] = "'" . str_replace("'", "\\'", $sub['String']['text']) . "'";
	}

	function CacheBlockArgument_Lookup(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/*!*

	# Collects the arguments passed in to be part of the key of a cacheblock

	CacheBlockArguments: CacheBlockArgument ( < "," < CacheBlockArgument )*

	*/
	function CacheBlockArguments_CacheBlockArgument(&$res, $sub) {
		if (!empty($res['php'])) $res['php'] .= ".'_'.";
		else $res['php'] = '';

		$res['php'] .= str_replace('$$FINAL', 'XML_val', $sub['php']);
	}

	/*!*
	# CacheBlockTemplate is the same as Template, but doesn't include cache blocks (because they're handled seperately)

	CacheBlockTemplate extends Template (TemplateMatcher = CacheRestrictedTemplate); CacheBlock | UncachedBlock | => ''
	*/

	/*!*

	UncachedBlock:
		'<%' < "uncached" < CacheBlockArguments? ( < Conditional:("if"|"unless") > Condition:IfArgument )? > '%>'
			Template:$TemplateMatcher?
			'<%' < 'end_' ("uncached"|"cached"|"cacheblock") > '%>'
	*/
	function UncachedBlock_Template(&$res, $sub){
		$res['php'] = $sub['php'];
	}

	/*!*

	# CacheRestrictedTemplate is the same as Template, but doesn't allow cache blocks

	CacheRestrictedTemplate extends Template
	*/
	function CacheRestrictedTemplate_CacheBlock(&$res, $sub) {
		throw new SSTemplateParseException('You cant have cache blocks nested within with, loop or control blocks ' .
			'that are within cache blocks', $this);
	}

	function CacheRestrictedTemplate_UncachedBlock(&$res, $sub) {
		throw new SSTemplateParseException('You cant have uncache blocks nested within with, loop or control blocks ' .
			'that are within cache blocks', $this);
	}

	/*!*
	# The partial caching block

	CacheBlock:
		'<%' < CacheTag:("cached"|"cacheblock") < (CacheBlockArguments)? ( < Conditional:("if"|"unless") >
		Condition:IfArgument )? > '%>'
			(CacheBlock | UncachedBlock | CacheBlockTemplate)*
		'<%' < 'end_' ("cached"|"uncached"|"cacheblock") > '%>'

	*/
	function CacheBlock__construct(&$res){
		$res['subblocks'] = 0;
	}

	function CacheBlock_CacheBlockArguments(&$res, $sub){
		$res['key'] = !empty($sub['php']) ? $sub['php'] : '';
	}

	function CacheBlock_Condition(&$res, $sub){
		$res['condition'] = ($res['Conditional']['text'] == 'if' ? '(' : '!(') . $sub['php'] . ') && ';
	}

	function CacheBlock_CacheBlock(&$res, $sub){
		$res['php'] .= $sub['php'];
	}

	function CacheBlock_UncachedBlock(&$res, $sub){
		$res['php'] .= $sub['php'];
	}

	function CacheBlock_CacheBlockTemplate(&$res, $sub){
		// Get the block counter
		$block = ++$res['subblocks'];
		// Build the key for this block from the global key (evaluated in a closure within the template),
		// the passed cache key, the block index, and the sha hash of the template.
		$res['php'] .= '$keyExpression = function() use ($scope, $cache) {' . PHP_EOL;
		$res['php'] .= '$val = \'\';' . PHP_EOL;
		if($globalKey = Config::inst()->get('SSViewer', 'global_key')) {
			// Embed the code necessary to evaluate the globalKey directly into the template,
			// so that SSTemplateParser only needs to be called during template regeneration.
			// Warning: If the global key is changed, it's necessary to flush the template cache.
			$parser = Injector::inst()->get('SSTemplateParser', false);
			$result = $parser->compileString($globalKey, '', false, false);
			if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $parser);
			$res['php'] .= $result . PHP_EOL;
		}
		$res['php'] .= 'return $val;' . PHP_EOL;
		$res['php'] .= '};' . PHP_EOL;
		$key = 'sha1($keyExpression())' // Global key
			. '.\'_' . sha1($sub['php']) // sha of template
			. (isset($res['key']) && $res['key'] ? "_'.sha1(".$res['key'].")" : "'") // Passed key
			. ".'_$block'"; // block index
		// Get any condition
		$condition = isset($res['condition']) ? $res['condition'] : '';

		$res['php'] .= 'if ('.$condition.'($partial = $cache->load('.$key.'))) $val .= $partial;' . PHP_EOL;
		$res['php'] .= 'else { $oldval = $val; $val = "";' . PHP_EOL;
		$res['php'] .= $sub['php'] . PHP_EOL;
		$res['php'] .= $condition . ' $cache->save($val); $val = $oldval . $val;' . PHP_EOL;
		$res['php'] .= '}';
	}

	/*!*

	# Deprecated old-style i18n _t and sprintf(_t block tags. We support a slightly more flexible version than we used
	# to, but just because it's easier to do so. It's strongly recommended to use the new syntax

	# This is the core used by both syntaxes, without the block start & end tags

	OldTPart: "_t" N "(" N QuotedString (N "," N CallArguments)? N ")" N (";")?

	# whitespace with a newline
	N: / [\s\n]* /
	*/
	function OldTPart__construct(&$res) {
		$res['php'] = "_t(";
	}

	function OldTPart_QuotedString(&$res, $sub) {
		$entity = $sub['String']['text'];
		if (strpos($entity, '.') === false) {
			$res['php'] .= "\$scope->XML_val('I18NNamespace').'.$entity'";
		}
		else {
			$res['php'] .= "'$entity'";
		}
	}

	function OldTPart_CallArguments(&$res, $sub) {
		$res['php'] .= ',' . $sub['php'];
	}

	function OldTPart__finalise(&$res) {
		$res['php'] .= ')';
	}

	/*!*

	# This is the old <% _t() %> tag

	OldTTag: "<%" < OldTPart > "%>"

	*/
	function OldTTag_OldTPart(&$res, $sub) {
		$res['php'] = $sub['php'];
	}

	/*!*

	# This is the old <% sprintf(_t()) %> tag

	OldSprintfTag: "<%" < "sprintf" < "(" < OldTPart < "," < CallArguments > ")" > "%>"

	*/
	function OldSprintfTag__construct(&$res) {
		$res['php'] = "sprintf(";
	}

	function OldSprintfTag_OldTPart(&$res, $sub) {
		$res['php'] .= $sub['php'];
	}

	function OldSprintfTag_CallArguments(&$res, $sub) {
		$res['php'] .= ',' . $sub['php'] . ')';
	}

	/*!*

	# This matches either the old style sprintf(_t()) or _t() tags. As well as including the output portion of the
	# php, this rule combines all the old i18n stuff into a single match rule to make it easy to not support these
	# tags later

	OldI18NTag: OldSprintfTag | OldTTag

	*/
	function OldI18NTag_STR(&$res, $sub) {
		$res['php'] = '$val .= ' . $sub['php'] . ';';
	}

	/*!*

	# An argument that can be passed through to an included template

	NamedArgument: Name:Word "=" Value:Argument

	*/
	function NamedArgument_Name(&$res, $sub) {
		$res['php'] = "'" . $sub['text'] . "' => ";
	}

	function NamedArgument_Value(&$res, $sub) {
		switch($sub['ArgumentMode']) {
			case 'string':
				$res['php'] .= $sub['php'];
				break;

			case 'default':
				$res['php'] .= $sub['string_php'];
				break;

			default:
				$res['php'] .= str_replace('$$FINAL', 'obj', $sub['php']) . '->self()';
				break;
		}
	}

	/*!*

	# The include tag

	Include: "<%" < "include" < Template:Word < (NamedArgument ( < "," < NamedArgument )*)? > "%>"

	*/
	function Include__construct(&$res){
		$res['arguments'] = array();
	}

	function Include_Template(&$res, $sub){
		$res['template'] = "'" . $sub['text'] . "'";
	}

	function Include_NamedArgument(&$res, $sub){
		$res['arguments'][] = $sub['php'];
	}

	function Include__finalise(&$res){
		$template = $res['template'];
		$arguments = $res['arguments'];

		$res['php'] = '$val .= SSViewer::execute_template('.$template.', $scope->getItem(), array(' .
			implode(',', $arguments)."), \$scope);\n";

		if($this->includeDebuggingComments) { // Add include filename comments on dev sites
			$res['php'] =
				'$val .= \'<!-- include '.addslashes($template).' -->\';'. "\n".
				$res['php'].
				'$val .= \'<!-- end include '.addslashes($template).' -->\';'. "\n";
		}
	}

	/*!*

	# To make the block support reasonably extendable, we don't explicitly define each closed block and it's structure,
	# but instead match against a generic <% block_name argument, ... %> pattern. Each argument is left as per the
	# output of the Argument matcher, and the handler (see the PHPDoc block later for more on this) is responsible
	# for pulling out the info required

	BlockArguments: :Argument ( < "," < :Argument)*

	# NotBlockTag matches against any word that might come after a "<%" that the generic open and closed block handlers
	# shouldn't attempt to match against, because they're handled by more explicit matchers

	NotBlockTag: "end_" | (("if" | "else_if" | "else" | "require" | "cached" | "uncached" | "cacheblock" | "include")])

	# Match against closed blocks - blocks with an opening and a closing tag that surround some internal portion of
	# template

	ClosedBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > Zap:'%>' Template:$TemplateMatcher?
		'<%' < 'end_' '$BlockName' > '%>'
	*/

	/**
	 * As mentioned in the parser comment, block handling is kept fairly generic for extensibility. The match rule
	 * builds up two important elements in the match result array:
	 *   'ArgumentCount' - how many arguments were passed in the opening tag
	 *   'Arguments' an array of the Argument match rule result arrays
	 *
	 * Once a block has successfully been matched against, it will then look for the actual handler, which should
	 * be on this class (either defined or extended on) as ClosedBlock_Handler_Name(&$res), where Name is the
	 * tag name, first letter captialized (i.e Control, Loop, With, etc).
	 *
	 * This function will be called with the match rule result array as it's first argument. It should return
	 * the php result of this block as it's return value, or throw an error if incorrect arguments were passed.
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

		$method = 'ClosedBlock_Handle_'.$blockname;
		if (method_exists($this, $method)) {
			$res['php'] = $this->$method($res);
		} else if (isset($this->closedBlocks[$blockname])) {
			$res['php'] = call_user_func($this->closedBlocks[$blockname], $res);
		} else {
			throw new SSTemplateParseException('Unknown closed block "'.$blockname.'" encountered. Perhaps you are ' .
			'not supposed to close this block, or have mis-spelled it?', $this);
		}
	}

	/**
	 * This is an example of a block handler function. This one handles the loop tag.
	 */
	function ClosedBlock_Handle_Loop(&$res) {
		if ($res['ArgumentCount'] > 1) {
			throw new SSTemplateParseException('Either no or too many arguments in control block. Must be one ' .
				'argument only.', $this);
		}

		//loop without arguments loops on the current scope
		if ($res['ArgumentCount'] == 0) {
			$on = '$scope->obj(\'Up\', null, true)->obj(\'Foo\', null, true)';
		} else {    //loop in the normal way
			$arg = $res['Arguments'][0];
			if ($arg['ArgumentMode'] == 'string') {
				throw new SSTemplateParseException('Control block cant take string as argument.', $this);
			}
			$on = str_replace('$$FINAL', 'obj',
				($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		}

		return
			$on . '; $scope->pushScope(); while (($key = $scope->next()) !== false) {' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'}; $scope->popScope(); ';
	}

	/**
	 * The deprecated closed block handler for control blocks
	 * @deprecated
	 */
	function ClosedBlock_Handle_Control(&$res) {
		Deprecation::notice('4.0', '<% control %> is deprecated. Use <% with %> or <% loop %> instead.');
		return $this->ClosedBlock_Handle_Loop($res);
	}

	/**
	 * The closed block handler for with blocks
	 */
	function ClosedBlock_Handle_With(&$res) {
		if ($res['ArgumentCount'] != 1) {
			throw new SSTemplateParseException('Either no or too many arguments in with block. Must be one ' .
				'argument only.', $this);
		}

		$arg = $res['Arguments'][0];
		if ($arg['ArgumentMode'] == 'string') {
			throw new SSTemplateParseException('Control block cant take string as argument.', $this);
		}

		$on = str_replace('$$FINAL', 'obj', ($arg['ArgumentMode'] == 'default') ? $arg['lookup_php'] : $arg['php']);
		return
			$on . '; $scope->pushScope();' . PHP_EOL .
				$res['Template']['php'] . PHP_EOL .
			'; $scope->popScope(); ';
	}

	/*!*

	# Open blocks are handled in the same generic manner as closed blocks. There is no need to define which blocks
	# are which - closed is tried first, and if no matching end tag is found, open is tried next

	OpenBlock: '<%' < !NotBlockTag BlockName:Word ( [ :BlockArguments ] )? > '%>'
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
		$blockname = $res['BlockName']['text'];

		$method = 'OpenBlock_Handle_'.$blockname;
		if (method_exists($this, $method)) {
			$res['php'] = $this->$method($res);
		} elseif (isset($this->openBlocks[$blockname])) {
			$res['php'] = call_user_func($this->openBlocks[$blockname], $res);
		} else {
			throw new SSTemplateParseException('Unknown open block "'.$blockname.'" encountered. Perhaps you missed ' .
			' the closing tag or have mis-spelled it?', $this);
		}
	}

	/**
	 * This is an open block handler, for the <% debug %> utility tag
	 */
	function OpenBlock_Handle_Debug(&$res) {
		if ($res['ArgumentCount'] == 0) return '$scope->debug();';
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

	/**
	 * This is an open block handler, for the <% base_tag %> tag
	 */
	function OpenBlock_Handle_Base_tag(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Base_tag takes no arguments', $this);
		return '$val .= SSViewer::get_base_tag($val);';
	}

	/**
	 * This is an open block handler, for the <% current_page %> tag
	 */
	function OpenBlock_Handle_Current_page(&$res) {
		if ($res['ArgumentCount'] != 0) throw new SSTemplateParseException('Current_page takes no arguments', $this);
		return '$val .= $_SERVER[SCRIPT_URL];';
	}

	/*!*

	# This is used to detect when we have a mismatched closing tag (i.e., one with no equivilent opening tag)
	# Because of parser limitations, this can only be used at the top nesting level of a template. Other mismatched
	# closing tags are detected as an invalid open tag

	MismatchedEndBlock: '<%' < 'end_' :Word > '%>'
	*/
	function MismatchedEndBlock__finalise(&$res) {
		$blockname = $res['Word']['text'];
		throw new SSTemplateParseException('Unexpected close tag end_' . $blockname .
			' encountered. Perhaps you have mis-nested blocks, or have mis-spelled a tag?', $this);
	}

	/*!*

	# This is used to detect a malformed opening tag - one where the tag is opened with the "<%" characters, but
	# the tag is not structured properly

	MalformedOpenTag: '<%' < !NotBlockTag Tag:Word  !( ( [ :BlockArguments ] )? > '%>' )
	*/
	function MalformedOpenTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed opening block tag $tag. Perhaps you have tried to use operators?"
			, $this);
	}

	/*!*

	# This is used to detect a malformed end tag - one where the tag is opened with the "<%" characters, but
	# the tag is not structured properly

	MalformedCloseTag: '<%' < Tag:('end_' :Word ) !( > '%>' )
	*/
	function MalformedCloseTag__finalise(&$res) {
		$tag = $res['Tag']['text'];
		throw new SSTemplateParseException("Malformed closing block tag $tag. Perhaps you have tried to pass an " .
			"argument to one?", $this);
	}

	/*!*

	# This is used to detect a malformed tag. It's mostly to keep the Template match rule a bit shorter

	MalformedBlock: MalformedOpenTag | MalformedCloseTag
	*/

	/*!*

	# This is used to remove template comments

	Comment: "<%--" (!"--%>" /(?s)./)+ "--%>"
	*/
	function Comment__construct(&$res) {
		$res['php'] = '';
	}

	/*!*

	# TopTemplate is the same as Template, but should only be used at the top level (not nested), as it includes
	# MismatchedEndBlock detection, which only works at the top level

	TopTemplate extends Template (TemplateMatcher = Template); MalformedBlock => MalformedBlock | MismatchedEndBlock
	*/

	/**
	 * The TopTemplate also includes the opening stanza to start off the template
	 */
	function TopTemplate__construct(&$res) {
		$res['php'] = "<?php" . PHP_EOL;
	}

	/*!*

	# Text matches anything that isn't a template command (not an injection, block of any kind or comment)

	Text: (
			# Any set of characters that aren't potentially a control mark or an escaped character
			/ [^<${\\]+ / |
			# An escaped character
			/ (\\.) / |
			# A '<' that isn't the start of a block tag
			'<' !'%' |
			# A '$' that isn't the start of an injection
			'$' !(/[A-Za-z_]/) |
			# A '{' that isn't the start of an injection
			'{' !'$' |
			# A '{$' that isn't the start of an injection
			'{$' !(/[A-Za-z_]/)
		)+
	*/

	/**
	 * We convert text
	 */
	function Text__finalise(&$res) {
		$text = $res['text'];

		// Unescape any escaped characters in the text, then put back escapes for any single quotes and backslashes
		$text = stripslashes($text);
		$text = addcslashes($text, '\'\\');

		// TODO: This is pretty ugly & gets applied on all files not just html. I wonder if we can make this
		// non-dynamically calculated
		$code = <<<'EOC'
(\Config::inst()->get('SSViewer', 'rewrite_hash_links')
	? \Convert::raw2att( preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI'] ) )
	: "")
EOC;
		// Because preg_replace replacement requires escaped slashes, addcslashes here
		$text = preg_replace(
			'/(<a[^>]+href *= *)"#/i',
			'\\1"\' . ' . addcslashes($code, '\\')  . ' . \'#',
			$text
		);

		$res['php'] .= '$val .= \'' . $text . '\';' . PHP_EOL;
	}

	/******************
	 * Here ends the parser itself. Below are utility methods to use the parser
	 */

	/**
	 * Compiles some passed template source code into the php code that will execute as per the template source.
	 *
	 * @throws SSTemplateParseException
	 * @param  $string The source of the template
	 * @param string $templateName The name of the template, normally the filename the template source was loaded from
	 * @param bool $includeDebuggingComments True is debugging comments should be included in the output
	 * @param bool $topTemplate True if this is a top template, false if it's just a template
	 * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
	 */
	public function compileString($string, $templateName = "", $includeDebuggingComments=false, $topTemplate = true) {
		if (!trim($string)) {
			$code = '';
		}
		else {
			parent::__construct($string);

			$this->includeDebuggingComments = $includeDebuggingComments;

			// Ignore UTF8 BOM at begining of string. TODO: Confirm this is needed, make sure SSViewer handles UTF
			// (and other encodings) properly
			if(substr($string, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $this->pos = 3;

			// Match the source against the parser
			if ($topTemplate) {
				$result = $this->match_TopTemplate();
			} else {
				$result = $this->match_Template();
			}
			if(!$result) throw new SSTemplateParseException('Unexpected problem parsing template', $this);

			// Get the result
			$code = $result['php'];
		}

		// Include top level debugging comments if desired
		if($includeDebuggingComments && $templateName && stripos($code, "<?xml") === false) {
			$code = $this->includeDebuggingComments($code, $templateName);
		}

		return $code;
	}

	/**
	 * @param string $code
	 * @return string $code
	 */
	protected function includeDebuggingComments($code, $templateName) {
		// If this template contains a doctype, put it right after it,
		// if not, put it after the <html> tag to avoid IE glitches
		if(stripos($code, "<!doctype") !== false) {
			$code = preg_replace('/(<!doctype[^>]*("[^"]")*[^>]*>)/im', "$1\r\n<!-- template $templateName -->", $code);
			$code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
		} elseif(stripos($code, "<html") !== false) {
			$code = preg_replace_callback('/(.*)(<html[^>]*>)(.*)/i', function($matches) use ($templateName) {
				if (stripos($matches[3], '<!--') === false && stripos($matches[3], '-->') !== false) {
					// after this <html> tag there is a comment close but no comment has been opened
					// this most likely means that this <html> tag is inside a comment
					// we should not add a comment inside a comment (invalid html)
					// lets append it at the end of the comment
					// an example case for this is the html5boilerplate: <!--[if IE]><html class="ie"><![endif]-->
					return $matches[0];
				} else {
					// all other cases, add the comment and return it
					return "{$matches[1]}{$matches[2]}<!-- template $templateName -->{$matches[3]}";
				}
			}, $code);
			$code = preg_replace('/(<\/html[^>]*>)/i', "<!-- end template $templateName -->$1", $code);
		} else {
			$code = str_replace('<?php' . PHP_EOL, '<?php' . PHP_EOL . '$val .= \'<!-- template ' . $templateName .
				' -->\';' . "\r\n", $code);
			$code .= "\r\n" . '$val .= \'<!-- end template ' . $templateName . ' -->\';';
		}
		return $code;
	}

	/**
	 * Compiles some file that contains template source code, and returns the php code that will execute as per that
	 * source
	 *
	 * @static
	 * @param  $template - A file path that contains template source code
	 * @return mixed|string - The php that, when executed (via include or exec) will behave as per the template source
	 */
	public function compileFile($template) {
		return $this->compileString(file_get_contents($template), $template);
	}
}
