<?php 
class Synchronise extends Controller {
	
	public function update() {
		Synchronised::update();
	}
	
	public function receive() {
		Synchronised::receive();
	}
	
	public function send() {
		Synchronised::send();
	}
	
	public function map() {
		Synchronised::map();
	}
}
?>