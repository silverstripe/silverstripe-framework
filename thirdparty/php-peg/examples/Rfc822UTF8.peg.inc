<?php

require 'Rfc822.php';

/**
 * This parser extends the RFC822 standard to allow XML entities and UTF-8 characters in atoms and quoted-strings
 * @author Hamish Friedlander
 */
class Rfc822UTF8 extends Rfc822 {

/*!* Rfc822UTF8

crlf: /\r\n/u

atom: /((&[A-Za-z]+;)|(&#(xX)?[A-Fa-f0-9]+;)|([^\x00-\x1F\x20()<>@,;:\\".\[\]]))+/u

qtext-chars: /[^"\\\x0D]+/u

quoted-pair: /\\./u

*/

}

/**
 * Some trial code. Remove soon
 */
$p = new Rfc822UTF8( 'JØhn ByØrgsØn <byorn@again.com>, "アキラ" <akira@neotokyo.com>' ) ;
print_r( $p->match_address_header() ) ;
 /* */
