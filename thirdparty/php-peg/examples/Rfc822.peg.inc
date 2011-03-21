<?php

require '../Parser.php';

/**
 * This parser strictly matches the RFC822 standard. No characters outside the ASCII range 0-127 are allowed
 * @author Hamish Friedlander
 */
class Rfc822 extends Parser {

/*!* Rfc822

crlf: /\r\n/

lwsp-char: " " | "\t"

linear-white-space: (crlf? lwsp-char)+

atom: /[^\x00-\x1F\x20()<>@,;:\\".\[\]\x80-\xFF]+/

qtext-chars: /[^"\\\x0D]+/

qtext: linear-white-space | qtext-chars

quoted-pair: /\\[\x00-\x7F]/

quoted-string: .'"' ( quoted-pair | qtext )* .'"'

word: atom | quoted-string

phrase: (word >)+

dtext-chars: /[^\[\]\\\r]+/

dtext: linear-white-space | dtext-chars

domain-literal: "[" ( dtext | quoted-pair )* "]"

domain-ref: atom

sub-domain: domain-ref | domain-literal

domain: sub-domain ("." sub-domain)*

route: "@" domain ("," "@" domain)* ":"

route-addr: "<" route? addr-spec ">"
	function addr_spec ( &$self, $sub ) {
		$self['addr_spec'] = $sub['text'] ;
	}

local-part: word ("." word)*

addr-spec: local-part "@" domain

mailbox: ( addr-spec | phrase route-addr ) >
	function __construct( &$self ) {
		$self['phrase'] = NULL ;
		$self['address'] = NULL ;
	}
	function phrase ( &$self, $sub ) {
		$self['phrase'] = $sub['text'] ;
	}
	function addr_spec ( &$self, $sub ) {
		$self['address'] = $sub['text'] ;
	}
	function route_addr ( &$self, $sub ) {
		$self['address'] = $sub['addr_spec'] ;
	}

group: phrase ":" ( mailbox ("," mailbox)* )? ";"

address: :mailbox | group

address-header: address (<","> address)*
	function __construct( &$self ) {
		$self['addresses'] = array() ;
	}
	function address( &$self, $sub ) {
		$self['addresses'][] = $sub['mailbox'] ;
	}

*/

}

$p = new Rfc822( 'John Byorgson <byorn@again.com>, "Akira \"Bad Boy\" Kenada" <akira@neotokyo.com>' ) ;
print_r( $p->match_address_header() ) ;
