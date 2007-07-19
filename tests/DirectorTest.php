<?php

class DirectorTest extends UnitTestCase {
	public $whatsBeingTested = "URL direction and URL generation are being tested";
	public $testComplete = "green";
	
	function testURLGeneration() {
			// Test baseURL			
			$this->assertEqual(Director::baseURL() . 'sapphire/main.php', $_SERVER['SCRIPT_NAME']);
			$this->assertEqual(Director::baseFolder() . '/sapphire/main.php', $_SERVER['SCRIPT_FILENAME']);

			// Tets fileExists
			$this->assertTrue(Director::fileExists('sapphire/main.php'));
			
			// Test make relative
			$testURL = Director::absoluteBaseURL() . 'someRandomUrl/yes';
			$this->assertEqual(Director::makeRelative($testURL), 'someRandomUrl/yes', "Director::makeRelative($testURL) broken");
			// Throw an extra slash in there and see what happens
			$testURL = Director::absoluteBaseURL() . '/someRandomUrl/yes';
			$this->assertEqual(Director::makeRelative($testURL), 'someRandomUrl/yes', "Director::makeRelative($testURL) broken");
			

			// Test mucking with HTTP host stuff
			$oldServer = $_SERVER;
			$_SERVER['HTTP_HOST'] = "www.testsomething.com";
			$_SERVER['PORT'] = "www.testsomething.com";
			$_SERVER['SCRIPT_NAME'] = "/auntBetty/sapphire/main.php5";
			$_SERVER['SCRIPT_FILENAME'] = "/home/shares/other/auntBetty/sapphire/main.php5";
			$_SERVER['SSL'] = true;
			
			$this->assertEqual(Director::absoluteBaseURL(), 'https://www.testsomething.com/auntBetty/');
			$this->assertEqual(Director::baseURL(), '/auntBetty/');
			$this->assertEqual(Director::baseFolder(), '/home/shares/other/auntBetty');
			$_SERVER = $oldServer;
	}
	
	/**
	 * Test that isLive(), isTest() and isDev() work properly.
	 */
	function testLiveChecking() {
		$urls = array(
			'test' =>  'isTest',
			'test.totallydigital.co.nz' => 'isTest',
			'manu.test.totallydigital.co.nz' => 'isTest',
			'manu.test.silverstripe.com' => 'isTest',
			'test.transport.govt.nz' => 'isTest',
			'dev/internaltest' => 'isTest',
			'dev/internaltest/mainsite' => 'isTest',

			'dev' => 'isDev',
			'dev.totallydigital.co.nz' => 'isDev',
			'dev.silverstripe.com' => 'isDev',
			'manu.dev.silverstripe.com' => 'isDev',
			'dev.transport.govt.nz' => 'isDev',
			
			'www.transport.govt.nz' => 'isLive',
			'testsevices.co.nz' => 'isLive',
			'dev-experts.com' => 'isLive',
			'www.testing.co.nz' => 'isLive',
			'nationaltesting.co.nz' => 'isLive',
			'www.perweek.co.nz' => 'isLive',
		);
		
		$oldServer = $_SERVER;
		foreach($urls as $url => $siteType) {
			list($_SERVER['HTTP_HOST'],$uri) = explode('/',$url,2);
			$_SERVER['REQUEST_URI'] = '/' . $uri;

			$this->assertEqual(Director::isTest(), $siteType == 'isTest', "Director::isTest() $url not $siteType");
			$this->assertEqual(Director::isDev(), $siteType == 'isDev', "Director::isDev() $url not $siteType");
			$this->assertEqual(Director::isLive(), $siteType == 'isLive', "Director::isLive() $url not $siteType");
		}
		$_SERVER = $oldServer;
	}
	
	function testGetControllerForURL()  {
			$d = new Director();

			$base = Director::baseURL();
			
			// Test a bunnch of different URLs
			
			
			$this->assertEqual($d->getControllerForURL(""), "redirect:{$base}home/");
			$this->assertEqual($d->getControllerForURL("home")->class, "ModelAsController");

			$this->assertEqual($d->getControllerForURL("Security")->class, "Security");
			$this->assertEqual($d->getControllerForURL("Security/")->class, "Security");
			
			$controller = $d->getControllerForURL("Security/testAction/asdfa/dsfasdf");
			$urlParams = $controller->getURLParams();
			$this->assertEqual($controller->class, "Security");
			$this->assertEqual($urlParams['Action'], "testAction");

			$this->assertEqual($d->getControllerForURL("images")->class, "Image_Uploader");
			$this->assertEqual($d->getControllerForURL("Security/")->class, "Security");
			$this->assertEqual($d->getControllerForURL("Security/asdf/asdfa/dsfasdf")->class, "Security");
	}
	
	function testRules() {
		$d = new Director();

		// Test adding rules of different priorities
		Director::addRules(100, array(
			'directorTest/$Action' => 'Security'
		));			
		$this->assertEqual($d->getControllerForURL("directorTest/rar")->class, 'Security');

		Director::addRules(200, array(
			'directorTest/$Action' => 'Image_Uploader'
		));
		$this->assertEqual($d->getControllerForURL("directorTest/rar")->class, 'Image_Uploader');		
		
		
	}

}

?>