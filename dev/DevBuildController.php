<?php

class DevBuildController extends Controller {

	private static $url_handlers = array(
		'' => 'build'
	);

	private static $allowed_actions = array(
		'build'
	);


	public function build($request) {
		if(Director::is_cli()) {
			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);
		} else {
			if(Director::isDev() || Permission::check("ADMIN")) {
				$renderer = DebugView::create();
				$renderer->writeHeader();
				$renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
				echo "<div class=\"build\">";
			}

			$da = DatabaseAdmin::create();
			return $da->handleRequest($request, $this->model);
		}
	}

}
