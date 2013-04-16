<?php

/* Don't actually define these, since it'd clutter up the namespace.
define('1',E_ERROR);
define('2',E_WARNING);
define('4',E_PARSE);
define('8',E_NOTICE);
define('16',E_CORE_ERROR);
define('32',E_CORE_WARNING);
define('64',E_COMPILE_ERROR);
define('128',E_COMPILE_WARNING);
define('256',E_USER_ERROR);
define('512',E_USER_WARNING);
define('1024',E_USER_NOTICE);
define('2048',E_STRICT);
define('4096',E_RECOVERABLE_ERROR);
define('8192',E_DEPRECATED);
define('16384',E_USER_DEPRECATED);
define('30719',E_ALL);
*/
/**
 * @package framework
 * @subpackage dev
 */
class SapphireREPL extends Controller {
	
	private static $allowed_actions = array(
		'index'
	);

	public function error_handler( $errno, $errstr, $errfile, $errline, $errctx ) {
		// Ignore unless important error
		if ( ($errno & ~( 2048 | 8192 | 16384 )) == 0 ) return ;
		// Otherwise throw exception to handle in REPL loop
		throw new Exception(sprintf("%s:%d\r\n%s", $errfile, $errline, $errstr));
	}

	public function index() {
		if(!Director::is_cli()) {
			return "The SilverStripe Interactive Command-line doesn't work in a web browser."
				. " Use 'sake interactive' from the command-line to run.";
		}


		/* Try using PHP_Shell if it exists */
		@include 'php-shell-cmd.php' ;

		/* Fall back to our simpler interface */
		if( empty( $__shell ) ) {
			set_error_handler(array($this, 'error_handler'));

			echo "SilverStripe Interactive Command-line (REPL interface). Type help for hints.\n\n";
			while(true) {
				echo SS_Cli::text("?> ", "cyan");
				echo SS_Cli::start_colour("yellow");
				$command = trim(fgets(STDIN, 4096));
				echo SS_Cli::end_colour();

				if ( $command == 'help' || $command == '?' ) {
					print "help or ? to exit\n" ;
					print "quit or \q to exit\n" ;
					print "install PHP_Shell for a more advanced interface with"
						. " auto-completion and readline support\n\n" ;
					continue ;
				}

				if ( $command == 'quit' || $command == '\q' ) break ;

				// Simple command processing
				if(substr($command,-1) == ';') $command = substr($command,0,-1);
				$is_print = preg_match('/^\s*print/i', $command);
				$is_return = preg_match('/^\s*return/i', $command);
				if(!$is_print && !$is_return) $command = "return ($command)";
				$command .= ";";

				try {
					$result = eval($command);
					if(!$is_print) print_r($result);
					echo "\n";
				}
				catch( Exception $__repl_exception ) {
					echo SS_Cli::start_colour("red");
					printf( '%s (code: %d) got thrown'.PHP_EOL, 
						get_class($__repl_exception), 
						$__repl_exception->getCode() );
					print $__repl_exception;
					echo "\n";
				}
			}
		}
	}
}

