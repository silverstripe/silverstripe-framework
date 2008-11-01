<?php
/**
 * This is part of the developer tools, and is responsible for installing and uninstalling modules.
 *
 * NOTE: This is experimental and the interface may change in the future.
 * Also, it requires that your site is checked into SVN, and that SVN is installed.
 *
 * For best results, use the "sake" command line tool:
 *
 * cd /my/project/
 * sake dev/modules/add ecommerce
 * 
 * @package sapphire
 * @subpackage dev
 */
class ModuleManager extends RequestHandler {
	protected $moduleBase = "http://svn.silverstripe.com/open/modules/";
	
	static $allowed_actions = array(
		'add',
		'remove',
	);
	
	/**
	 * Add a module to this project.
	 * This is designed to be called from sake.
	 * 
	 * Usage: sake dev/modules/add ecommerce forum/tags/0.1
	 */
	function add() {
		if(!Director::is_cli()) return new HTTPResponse('ModuleManager only currently works in command-line mode.', 403);	

		if(isset($_GET['args'])) {
			$modules = $_GET['args'];
			foreach($modules as $module) {
				if(preg_match('/^[a-zA-Z0-9\/_-]+$/', $module)) {
					// Default to trunk version of a module
					if(strpos($module,'/') === false) $module = "$module/trunk";

					$svnURL = $this->moduleBase . $module;
					
					if($this->svnUrlExists($svnURL)) {
						$moduleDir = strtok($module,'/');
						echo "Linking directory '$moduleDir' to '$svnURL' using svn:externals...\n";
						$this->svnAddExternal(Director::baseFolder(), $moduleDir, $svnURL);

						echo "Calling SVN update to get the new code...\n";
						$this->svnUpdate(Director::baseFolder());
						
						// We call this through sake so that the _config.php files get reprocessed
						echo "Rebuilding...\n";
						$CLI_baseFolder = Director::baseFolder();
						`cd $CLI_baseFolder; ./sapphire/sake dev/build`;
						
					} else {
						echo "Can't find '$svnURL' in SVN\n";
					}
					
				} else {
					echo "Bad module '$module'\n";
				}
				
			}
			
		}
	}

	/**
	 * Remove a module from this project.
	 * This is designed to be called from sake.
	 * 
	 * Usage: sake dev/modules/remove ecommerce othermodule
	 */
	function remove() {
		if(!Director::is_cli()) return new HTTPResponse('ModuleManager only currently works in command-line mode.', 403);	

		if(isset($_GET['args'])) {
			$modules = $_GET['args'];
			foreach($modules as $module) {
				if(preg_match('/^[a-zA-Z0-9\/_-]+$/', $module)) {
					$moduleDir = strtok($module,'/');
					
					if(is_dir(Director::baseFolder() . '/' . $moduleDir)) {
						$moduleDir = strtok($module,'/');
						echo "Removing directory '$moduleDir' from svn:externals...\n";
						if($this->svnRemoveExternal(Director::baseFolder(), $moduleDir)) {
							$CLI_moduleDir = escapeshellarg(Director::baseFolder() . '/' . $moduleDir);
							echo "Removing the physical directory $CLI_moduleDir...\n";
							`rm -rf $CLI_moduleDir`;
						
							echo "Calling SVN update...\n";
							$this->svnUpdate(Director::baseFolder());
						
							// We call this through sake so that the _config.php files get reprocessed
							echo "Rebuilding...\n";
							$CLI_baseFolder = Director::baseFolder();
							`cd $CLI_baseFolder; ./sapphire/sake dev/build`;
						} else {
							echo "Directory '$moduleDir' didn't seem to be an svn external\n";
						}
						
					} else {
						echo "Can't find the '$moduleDir' directory.\n";
					}
					
				} else {
					echo "Bad module '$module'\n";
				}
				
			}
			
		}
	}


	/**
	 * Calls svn update
	 */
	protected function svnUpdate($baseDir) {
		$CLI_baseDir = escapeshellarg($baseDir);
		`svn update $CLI_baseDir`;
	}
	
	/**
	 * Returns true if the given SVN url exists
	 */
	protected function svnUrlExists($svnURL) {
		$CLI_svnURL = escapeshellarg($svnURL);
		$info = `svn info --xml $CLI_svnURL`;
		$xmlInfo = new SimpleXmlElement($info);
		
		return $xmlInfo->entry ? true : false;
	}

	/**
	 * Add a new entry to the svn externals
	 */
	protected function svnAddExternal($baseDir, $externalDir, $externalURL) {
		$CLI_baseDir = escapeshellarg($baseDir);
		$oldExternals = trim(`svn propget svn:externals $CLI_baseDir`);
		$newExternals = "$oldExternals\n$externalDir/	$externalURL";
		
		$CLI_newExternals = escapeshellarg($newExternals);
		`svn propset svn:externals $CLI_newExternals $CLI_baseDir`;
	}

	/**
	 * Remove an entry from the svn externals.
	 * @return boolean True if it identified and removed the line, and false if the line couldn't be found
	 */
	protected function svnRemoveExternal($baseDir, $externalDir) {
		$CLI_baseDir = escapeshellarg($baseDir);
		$oldExternalsArray = explode("\n", trim(`svn propget svn:externals $CLI_baseDir`));
		foreach($oldExternalsArray as $i => $line) {
			if(preg_match("/^$externalDir\/?[\t ]/", $line)) {
				unset($oldExternalsArray[$i]);
				$newExternals = implode("\n", $oldExternalsArray);
				$CLI_newExternals = escapeshellarg($newExternals);
				`svn propset svn:externals $CLI_newExternals $CLI_baseDir`;
				return true;
			}
		}
		// If we got here, we never found the applicable line
		return false;
	}
	
}

?>