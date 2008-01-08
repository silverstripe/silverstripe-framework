<?php 

/**
 * @package sapphire
 * @subpackage synchronisation
 */

/**
 * Synchroniser controller - used to let two servers communicate
 */
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