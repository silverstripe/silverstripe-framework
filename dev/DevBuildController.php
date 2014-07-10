<?php 

class DevBuildController extends Controller {
	
	private static $url_handlers = array(
			'' => 'build',			
			'defaults' => 'buildDefaults',
	);
	
	private static $allowed_actions = array(
			'build',
			'buildDefaults'
	);
	
	
	public function build($request) {
		if(Director::is_cli()) {
			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);
		} else {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
			echo "<div class=\"build\">";
	
			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);
	
			echo "</div>";
			$renderer->writeFooter();
		}
	}
	
}
