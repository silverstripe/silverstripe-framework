<?php

class SapphireREPL extends Controller {
	function index() {
		if(!Director::is_cli()) return "The Sapphire Interactive Command-line doesn't work in a web browser.  Use 'sake interactive' from the command-line to run.";
		
		echo "Sapphire Interactive Command-line (REPL interface)\n\n";
		while(true) {
			echo SSCli::text("?> ", "cyan");
			echo SSCli::start_colour("yellow");
			$command = trim(fgets(STDIN, 4096));
			echo SSCli::end_colour();
			
			// Simple processing
			if(substr($command,-1) == ';') $command = substr($command,0,-1);
			if(!preg_match('/^return/i', $command)) $command = "return ($command)";
			$command .= ";";
			$result = eval($command);
			print_r($result);
			echo "\n";
		}
	}
}

?>