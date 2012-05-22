<?php

class Oembed {
	public static function get_autodiscover() {
		return Config::inst()->get('Oembed', 'autodiscover');
	}

	public static function get_providers() {
		return Config::inst()->get('Oembed', 'providers');
	}
	
	protected static function match_url($url) {
		foreach(self::get_providers() as $scheme=>$endpoint) {
			if(self::match_scheme($url, $scheme)) {
				return $endpoint;
			}
		}
		return false;
	}
	
	protected static function match_scheme($url, $scheme) {
		$urlInfo = parse_url($url);
		$schemeInfo = parse_url($scheme);
		foreach($schemeInfo as $k=>$v) {
			if(!array_key_exists($k, $urlInfo)) {
				return false;
			}
			if(strpos($v, '*') !== false) {
				$v = preg_quote($v, '/');
				$v = str_replace('\*', '.*', $v);
				if($k == 'host') {
					$v = str_replace('*\.', '*', $v);
				}
				if(!preg_match('/' . $v . '/', $urlInfo[$k])) {
					return false;
				}
			} elseif(strcasecmp($urlInfo[$k], $v)) {
				return false;
			}
		}
		return true;
	}
	
	protected static function autodiscover_from_url($url) {
		$service = new RestfulService($url);
		$body = $service->request();
		if(!$body || $body->isError()) {
			return false;
		}
		$body = $body->getBody();
		
		if(preg_match_all('#<link[^>]+?(?:href=[\'"](.+?)[\'"][^>]+?)?type=["\']application/json\+oembed["\'](?:[^>]+?href=[\'"](.+?)[\'"])?#', $body, $matches, PREG_SET_ORDER)) {
			$match = $matches[0];
			if(!empty($match[1])) {
				return html_entity_decode($match[1]);
			}
			if(!empty($match[2])) {
				return html_entity_decode($match[2]);
			}
		}
		return false;
	}
	
	public static function get_oembed_from_url($url, $type = false, Array $options = array()) {
		$endpoint = self::match_url($url);
		$ourl = false;
		if(!$endpoint) {
			if(self::get_autodiscover()) {
				$ourl = self::autodiscover_from_url($url);
			}
		} elseif($endpoint === true) {
			$ourl = self::autodiscover_from_url($url);
		} else {
			$ourl = Controller::join_links($endpoint, '?format=json&url=' . rawurlencode($url));
		}
		if($ourl) {
			if($options) {
				if(isset($options['width']) && !isset($options['maxwidth'])) {
					$options['maxwidth'] = $options['width'];
				}
				if(isset($options['height']) && !isset($options['maxheight'])) {
					$options['maxheight'] = $options['height'];
				}
				$ourl = Controller::join_links($ourl, '?' . http_build_query($options, '', '&'));
			}
			return new Oembed_Result($ourl, $url, $type, $options);
		}
		return false;
	}
	
	public static function handle_shortcode($arguments, $url, $parser, $shortcode) {
		if(isset($arguments['type'])) {
			$type = $arguments['type'];
			unset($arguments['type']);
		} else {
			$type = false;
		}
		$oembed = self::get_oembed_from_url($url, $type, $arguments);
		if($oembed && $oembed->exists()) {
			return $oembed->forTemplate();
		} else {
			return '<a href="' . $url . '">' . $url . '</a>';
		}
	}
}

class Oembed_Result extends ViewableData {
	protected $data = false;
	protected $origin = false;
	protected $type = false;
	protected $url;
	protected $extraClass;
	
	public static $casting = array(
		'html' => 'HTMLText',
	);
	
	public function __construct($url, $origin = false, $type = false, Array $options = array()) {
		$this->url = $url;
		$this->origin = $origin;
		$this->type = $type;

		if(isset($options['class'])) {
			$this->extraClass = $options['class'];
		}
		
		parent::__construct();
	}
	
	protected function loadData() {
		if($this->data !== false) {
			return;
		}
		$service = new RestfulService($this->url);
		$body = $service->request();
		if(!$body || $body->isError()) {
			$this->data = array();
			return;
		}
		$body = $body->getBody();
		$data = json_decode($body, true);
		if(!$data) {
			$data = array();
		}
		foreach($data as $k=>$v) {
			unset($data[$k]);
			$data[strtolower($k)] = $v;
		}
		if($this->type && $this->type != $data['type']) {
			$data = array();
		}
		$this->data = $data;
	}
	
	public function hasField($field) {
		$this->loadData();
		return array_key_exists(strtolower($field), $this->data);
	}
	
	public function getField($field) {
		$field = strtolower($field);
		if($this->hasField($field)) {
			return $this->data[$field];
		}
	}
	
	public function forTemplate() {
		$this->loadData();
		switch($this->Type) {
			case 'video':
			case 'rich':
				if($this->extraClass) {
					return "<div class='$this->extraClass'>$this->HTML</div>";
				} else {
					return $this->HTML;
				}
				break;
			case 'link':
				return '<a class="' . $this->extraClass . '" href="' . $this->origin . '">' . $this->Title . '</a>';
				break;
			case 'photo':
				return "<img src='$this->URL' width='$this->Width' height='$this->Height' class='$this->extraClass' />";
				break;
		}
	}
	
	public function exists() {
		$this->loadData();
		return count($this->data) > 0;
	}
}

