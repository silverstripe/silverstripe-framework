<?php
/**
 * The most common kind if controller; effectively a controller linked to a {@link DataObject}.
 *
 * ContentControllers are most useful in the content-focused areas of a site.  This is generally
 * the bulk of a site; however, they may be less appropriate in, for example, the user management
 * section of an application.
 *
 * On its own, content controller does very little.  Its constructor is passed a {@link DataObject}
 * which is stored in $this->dataRecord.  Any unrecognised method calls, for example, Title() 
 * and Content(), will be passed along to the data record, 
 * 
 * Subclasses of ContentController are generally instantiated by ModelAsController; this will create
 * a controller based on the URLSegment action variable, by looking in the SiteTree table.
 */
class ContentController extends Controller {
	protected $dataRecord;

	/**
	 * The ContentController will take the URLSegment parameter from the URL and use that to look
	 * up a SiteTree record.
	 */
	public function __construct($dataRecord) {
		$this->dataRecord = $dataRecord;
		$this->failover = $this->dataRecord;

		parent::__construct();
	}
	
	public function Link($action = null) {
		return Director::baseURL() . $this->RelativeLink($action);
	}
	public function RelativeLink($action = null) {

		if($this->URLSegment){
			if($action == "index") $action = "";
			
			// '&' in a URL is apparently naughty
			$action = preg_replace('/&/', '&amp;', $action);
			return $this->URLSegment . "/$action";
		} else {
			user_error("ContentController::RelativeLink() No URLSegment given on a '$this->class' object.  Perhaps you should overload it?", E_USER_WARNING);
		}
	}
	
	//----------------------------------------------------------------------------------//
	// These flexible data methods remove the need for custom code to do simple stuff
	
	/*
	 * Return the children of the given page.
	 * $parentRef can be a page number or a URLSegment
	 */
	public function ChildrenOf($parentRef) {
		$SQL_parentRef = Convert::raw2sql($parentRef);
		$parent = DataObject::get_one('SiteTree', "URLSegment = '$SQL_parentRef'");

		if(!$parent && is_numeric($parentRef)) $parent = DataObject::get_by_id('SiteTree', $SQL_parentRef);
		if($parent) {
			return $parent->Children();
		} else {
			user_error("Error running <% control ChildrenOf($parentRef) %>: page '$parentRef' couldn't be found", E_USER_WARNING);
		}

	}
	
	public function Page($url) {
		$SQL_url = Convert::raw2sql($url);
		return DataObject::get_one('SiteTree', "URLSegment = '$SQL_url'");
	}
	
	public function init() {
		parent::init();
		
		// If we've accessed the homepage as /home/, then we should redirect to /.
		if($this->dataRecord && RootURLController::should_be_on_root($this->dataRecord) && !$this->urlParams['Action'] && !$_POST && !$_FILES) {
			$getVars = $_GET;
			unset($getVars['url']);
			if($getVars) $url = "?" . http_build_query($getVars);
			else $url = "";
			Director::redirect($url);
			return;
		}
		
		singleton('SiteTree')->extend('contentcontrollerInit', $this);
		
		Director::set_site_mode('site');
		
		// Check permissions
		if($this->dataRecord && !$this->dataRecord->can('View')) {
			Security::permissionFailure($this);
		}
	}
	
	/**
	 * Get the project name
	 *
	 * @return string
	 */
	function project() {
		global $project;
		return $project;
	}
	
	/**
	 * Returns the associated database record
	 */
	public function data() {
		return $this->dataRecord;
	}
	
	/*--------------------------------------------------------------------------------*/
	
	/**
	 * Returns a fixed navigation menu of the given level.
	 */
	public function getMenu($level) {
		if($level == 1) {
			$result = DataObject::get("SiteTree", "ShowInMenus = 1 AND ParentID = 0");

		} else {
			$parent = $this->data();
			$stack = array($parent);
			while($parent = $parent->Parent)
				array_unshift($stack, $parent);
			
			if(isset($stack[$level-2]))
				$result = $stack[$level-2]->Children();
		}

		$visible = array();

		// Remove all entries the can not be viewed by the current user
		// We might need to create a show in menu permission
 		if($result) {
			foreach($result as $page) {
				if($page->can('view')) {
					$visible[] = $page;
				}
			}
		}

		return new DataObjectSet($visible);
	}
	/**
	 * Returns the page in the current page stack of the given level.
	 * Level(1) will return the main menu item that we're currently inside, etc.
	 */
	
	public function Level($level) {
		$parent = $this->data();
		$stack = array($parent);
		while($parent = $parent->Parent) {
			array_unshift($stack, $parent);
		}

		return isset($stack[$level-1]) ? $stack[$level-1] : null;
	}
	
	public function Menu($level) {
		return $this->getMenu($level);
	}
	
	public function Section2() {
		return $this->Level(2)->URLSegment;
	}
	
	/**
	 * Returns the default log-in form.
	 */ 
	public function LoginForm() {
		return Object::create('LoginForm', $this, "LoginForm");
	}
	
	public function SilverStripeNavigator() {
		$member = Member::currentUser();
		
		if(Director::isDev() || ($member && $member->isCMSUser())) {
			Requirements::css('sapphire/css/SilverStripeNavigator.css');

			Requirements::javascript('jsparty/behaviour.js');
			// Requirements::javascript('jsparty/prototype.js');
			Requirements::customScript(<<<JS
				Behaviour.register({
					'#switchView a' :  {
						onclick : function() {
							var w = window.open(this.href,windowName(this.target));
							w.focus();
							return false;
						}						
					}					
				});

				function windowName(suffix) {
					var base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
					return base + suffix;
				}
				window.name = windowName('site');
JS
			);

			if($this->dataRecord){
				$thisPage = $this->dataRecord->Link();
				$cmsLink = 'admin/show/' . $this->dataRecord->ID;
				$cmsLink = "<a href=\"$cmsLink\" target=\"cms\">CMS</a>";
			} else {
				/**
				 * HGS: If this variable is missing a notice is raised. Subclasses of ContentController
				 * are required to implement RelativeLink anyway, so this should work even if the
				 * dataRecord isn't set.
				 */
				$thisPage = $this->Link();
				$cmsLink = '';
			}
			
			$archiveLink = "";
			
			if($date = Versioned::current_archived_date()) {
				$dateObj = Object::create('Datetime', $date, null);
				// $dateObj->setVal($date);

				$archiveLink = "<a class=\"current\">Archived Site</a>";
				$liveLink = "<a href=\"$thisPage?stage=Live\" target=\"site\" style=\"left : -3px;\">Published Site</a>";
				$stageLink = "<a href=\"$thisPage?stage=Stage\" target=\"site\" style=\"left : -1px;\">Draft Site</a>";
				$message = "<div id=\"SilverStripeNavigatorMessage\">Archived site from<br>" . $dateObj->Nice() . "</div>";
				
			} else if(Versioned::current_stage() == 'Stage') {
				$stageLink = "<a class=\"current\">Draft Site</a>";
				$liveLink = "<a href=\"$thisPage?stage=Live\" target=\"site\" style=\"left : -3px;\">Published Site</a>";
				$message = "<div id=\"SilverStripeNavigatorMessage\">DRAFT SITE</div>";

			} else {
				$liveLink = "<a class=\"current\">Published Site</a>";
				$stageLink = "<a href=\"$thisPage?stage=Stage\" target=\"site\" style=\"left : -1px;\">Draft Site</a>";
				$message = "<div id=\"SilverStripeNavigatorMessage\">PUBLISHED SITE</div>";
			}
			
			if($member) {
				$firstname = Convert::raw2xml($member->FirstName);
				$surname = Convert::raw2xml($member->Surame);
				$logInMessage = "Logged in as {$firstname} {$surname} - <a href=\"Security/logout\">log out</a>";
			} else {
				$logInMessage = "Not logged in - <a href=\"Security/login\">log in</a>";
			}
			
			/**
			 * HGS: cmsLink is now only set if there is a dataRecord. You can't view the page in the
			 * CMS if there is no dataRecord
			 */
			return <<<HTML
				<div id="SilverStripeNavigator">
					<div class="holder">
					<div id="logInStatus">
						$logInMessage
					</div>
			
					<div id="switchView" class="bottomTabs">
						<div class="blank"> View page in: </div>
						$cmsLink
						$stageLink
						$liveLink
						$archiveLink
					</div>
					</div>
				</div>
					$message
HTML;

		// On live sites we should still see the archived message
		} else {
			if($date = Versioned::current_archived_date()) {
				Requirements::css('sapphire/css/SilverStripeNavigator.css');
				$dateObj = Object::create('Datetime', $date, null);
				// $dateObj->setVal($date);
				return "<div id=\"SilverStripeNavigatorMessage\">Archived site from<br>" . $dateObj->Nice() . "</div>";
			}
		}
	}

	/**
	 * Returns a page comment system
	 */
	function PageComments() {
		if($this->data()->ProvideComments) {
			return new PageCommentInterface($this, 'PageComments', $this->data());
		} else {
			if(isset($_REQUEST['executeForm']) && $_REQUEST['executeForm'] == 'PageComments.PostCommentForm') {
				echo "Comments have been disabled for this page";
				die();
			}
		}
	}
	
	
	/**
	 * Throw an error to test the error system
	 */
	function throwerror() {
		user_error("This is a test of the error handler - nothing to worry about.", E_USER_ERROR);
	}
		
	/**
	 * Throw a warning to test the error system
	 */
	function throwwarning() {
		user_error("This is a test of the warning handler - nothing to worry about.", E_USER_WARNING);
	}
	
	
	/**
	 * This action is called by the installation system
	 */
	function successfullyinstalled() {
		// The manifest should be built by now, so it's safe to publish the 404 page
		$fourohfour = Versioned::get_one_by_stage('ErrorPage', 'Stage', 'ErrorCode = 404');
		if($fourohfour) {
			$fourohfour->Status = "Published";
			$fourohfour->write();
			$fourohfour->publish("Stage", "Live");
		}
		
		if(isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
			$url = 'http://ss2stat.silverstripe.com/Installation/installed?ID=' . $_SESSION['StatsID'];
			@file_get_contents($url);
		}
		
		$title = new Varchar("Title");
		$content = new HTMLText("Content");
		$username = Session::get('username');
		$password = Session::get('password');
		$title->setValue("Installation Successful");
		global $project;
		$tutorialOnly = ($project == 'tutorial') ? "<p>This website is a simplistic version of a SilverStripe 2 site. To extend this, please take a look at <a href=\"http://doc.silverstripe.com/doku.php?id=tutorial:1-building-a-basic-site\">our new tutorials</a>.</p>" : '';
		$content->setValue(<<<HTML
			<p style="margin: 1em 0"><b>Congratulations, SilverStripe has been successfully installed.</b></p>
			
			$tutorialOnly
			<p>You can start editing your site's content by opening <a href="admin/">the CMS</a>. <br />
				&nbsp; &nbsp; Email: $username<br />
				&nbsp; &nbsp; Password: $password<br />
			</p>
			<div style="background:#ddd; border:1px solid #ccc; padding:5px; margin:5px;"><img src="cms/images/dialogs/alert.gif" style="border: none; margin-right: 10px; float: left;" /><p style="color:red;">For security reasons you should now delete the install files, unless you are planning to reinstall later. The web server also now only needs write access to the "assets" folder, you can remove write access from all other folders.</p>
					<div style="margin-left: auto; margin-right: auto; width: 50%;"><p><a href="home/deleteinstallfiles" style="text-align: center;">Click here to delete the install files.</a></p></div></div>
HTML
);
		
		return array(
			"Title" => $title,
			"Content" => $content,
		);
	}
	
	function deleteinstallfiles() {
		$title = new Varchar("Title");
		$content = new HTMLText("Content");
		$tempcontent = '';
		$username = Session::get('username');
		$password = Session::get('password');
		
		$installfiles = array(
			'index.php',
			'install.php',
			'rewritetest.php',
			'check-php.php',
			'config-form.css',
			'config-form.html',
			'index.html'
		);
		
		foreach($installfiles as $installfile) {
			if(file_exists('../' . $installfile)) {
				unlink('../' . $installfile);
			}
			
			if(file_exists('../' . $installfile)) {
				$unsuccessful[] = $installfile;
			}
		}
				
		if(isset($unsuccessful)) {
			$title->setValue("Unable to delete installation files");
			$tempcontent = "<p style=\"margin: 1em 0\">Unable to delete installation files. Please delete the files below manually:</p><ul>";
			foreach($unsuccessful as $unsuccessfulFile) {
				$tempcontent .= "<li>$unsuccessfulFile</li>";
			}
			$tempcontent .= "</ul>";
		} else {
			$title->setValue("Deleted installation files");
			$tempcontent = <<<HTML
<p style="margin: 1em 0">Installation files have been successfully deleted.</p>
HTML
			;
		}

		$tempcontent .= <<<HTML
			<p style="margin: 1em 0">You can start editing your site's content by opening <a href="admin/">the CMS</a>. <br />
				&nbsp; &nbsp; Email: $username<br />
				&nbsp; &nbsp; Password: $password<br />
			</p>
HTML
		;
		$content->setValue($tempcontent);
		
		return array(
			"Title" => $title,
			"Content" => $content,
		);
	}
}


?>