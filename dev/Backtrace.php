<?php
/**
 * @package sapphire
 * @subpackage dev
 */
class SS_Backtrace {
	
	/**
	 * Return debug_backtrace() results with functions filtered
	 * specific to the debugging system, and not the trace.
	 * 
	 * @param null|array $ignoredFunctions If an array, filter these functions out of the trace
	 * @return array
	 */
	static function filtered_backtrace($ignoredFunctions = null) {
		return self::filter_backtrace(debug_backtrace(), $ignoredFunctions);
	}
	
	/**
	 * Filter a backtrace so that it doesn't show the calls to the
	 * debugging system, which is useless information.
	 * 
	 * @param array $bt Backtrace to filter
	 * @param null|array $ignoredFunctions List of extra functions to filter out
	 * @return array
	 */
	static function filter_backtrace($bt, $ignoredFunctions = null) {
		$defaultIgnoredFunctions = array(
			'SS_Log::log',
			'SS_Backtrace::backtrace',
			'SS_Backtrace::filtered_backtrace',
			'Zend_Log_Writer_Abstract->write',
			'Zend_Log->log',
			'Zend_Log->__call',
			'Zend_Log->err',
			'DebugView->writeTrace',
			'CliDebugView->writeTrace',
			'Debug::emailError',
			'Debug::warningHandler',
			'Debug::noticeHandler',
			'Debug::fatalHandler',
			'errorHandler',
			'Debug::showError',
			'Debug::backtrace',
			'exceptionHandler'
		);
		
		if($ignoredFunctions) foreach($ignoredFunctions as $ignoredFunction) {
			$defaultIgnoredFunctions[] = $ignoredFunction;
		}
		
		while($bt && in_array(self::full_func_name($bt[0]), $defaultIgnoredFunctions)) {
			array_shift($bt);
		}
		
		return $bt;	
	}
	
	/**
	 * Render or return a backtrace from the given scope.
	 *
	 * @param unknown_type $returnVal
	 * @param unknown_type $ignoreAjax
	 * @return unknown
	 */
	static function backtrace($returnVal = false, $ignoreAjax = false, $ignoredFunctions = null) {
		$plainText = Director::is_cli() || (Director::is_ajax() && !$ignoreAjax);
		$result = self::get_rendered_backtrace(debug_backtrace(), $plainText, $ignoredFunctions);
		if($returnVal) {
			return $result;
		} else {
			echo $result;
		}
	}
	
	/**
	 * Return the full function name.  If showArgs is set to true, a string representation of the arguments will be shown
	 * 
	 * @param Object $item
	 * @param boolean $showArg
	 * @param Int $argCharLimit
	 * @return String
	 */
	static function full_func_name($item, $showArgs = false, $argCharLimit = 10000) {
		$funcName = '';
		if(isset($item['class'])) $funcName .= $item['class'];
		if(isset($item['type'])) $funcName .= $item['type'];
		if(isset($item['function'])) $funcName .= $item['function'];
		
		if($showArgs && isset($item['args'])) {
			$args = array();
			foreach($item['args'] as $arg) {
				if(!is_object($arg) || method_exists($arg, '__toString')) {
					$args[] = (strlen((string)$arg) > $argCharLimit) ? substr((string)$arg, 0, $argCharLimit) . '...' : (string)$arg;
				} else {
					$args[] = get_class($arg);
				}
			}
		
			$funcName .= "(" . implode(",", $args)  .")";
		}
		
		return $funcName;
	}
	
	/**
	 * Render a backtrace array into an appropriate plain-text or HTML string.
	 * 
	 * @param string $bt The trace array, as returned by debug_backtrace() or Exception::getTrace()
	 * @param boolean $plainText Set to false for HTML output, or true for plain-text output
	 * @param array List of functions that should be ignored. If not set, a default is provided
	 * @return string The rendered backtrace
	 */
	static function get_rendered_backtrace($bt, $plainText = false, $ignoredFunctions = null) {
		$bt = self::filter_backtrace($bt, $ignoredFunctions);
		$result = "<ul>";
		foreach($bt as $item) {
			if($plainText) {
				$result .= self::full_func_name($item,true) . "\n";
				if(isset($item['line']) && isset($item['file'])) $result .= "line $item[line] of " . basename($item['file']) . "\n";
				$result .= "\n";
			} else {
				if ($item['function'] == 'user_error') {
					$name = $item['args'][0];
				} else {
					$name = self::full_func_name($item,true);
				}
				$result .= "<li><b>" . htmlentities($name, ENT_COMPAT, 'UTF-8') . "</b>\n<br />\n";
				$result .= isset($item['line']) ? "Line $item[line] of " : '';
				$result .=  isset($item['file']) ? htmlentities(basename($item['file'])) : ''; 
				$result .= "</li>\n";
			}
		}
		$result .= "</ul>";
		return $result;
	}
	
}