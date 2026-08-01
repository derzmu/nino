<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Bandsintown probe		Verify the live Bandsintown api against what the
 *											Bandsintown module expects.
 *
 *											This exists because the module was written without
 *											network access - the response shape below is what the
 *											normalizer assumes, and this script is how you confirm
 *											it against the real endpoint before trusting the live
 *											section.
 *
 *											Usage:  php tests/bandsintown-probe.php
 *															php tests/bandsintown-probe.php --raw
 *
 *	@package						KeinePanik
 */

$root		= dirname( __DIR__ );
$config	= require $root. '/config.php';

$artist	= (string) ( $config['/keinepanik/bandsintown/artist'] ?? '' );
$appId	= (string) ( $config['/keinepanik/bandsintown/appid'] ?? '' );

if( $artist === '' || $appId === '' ) {
	fwrite( STDERR, "config.php is missing /keinepanik/bandsintown/artist or /appid\n" );
	exit( 1 );
}

$url = 'https://rest.bandsintown.com/artists/'. rawurlencode( $artist ). '/events?app_id='. rawurlencode( $appId );

echo "GET ". $url. "\n\n";

$curl = curl_init( $url );
curl_setopt_array( $curl, [
	CURLOPT_RETURNTRANSFER	=> true,
	CURLOPT_TIMEOUT					=> 10,
	CURLOPT_FOLLOWLOCATION	=> true,
	CURLOPT_HTTPHEADER			=> [ 'Accept: application/json' ],
	CURLOPT_USERAGENT				=> 'keinepanik-website/1.0',
] );

$body		= curl_exec( $curl );
$status	= (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
$error	= curl_error( $curl );
curl_close( $curl );

if( is_string( $body ) === false ) {
	fwrite( STDERR, "request failed: ". $error. "\n" );
	exit( 1 );
}

echo "HTTP ". $status. ", ". strlen( $body ). " bytes\n\n";

if( in_array( '--raw', $argv, true ) === true ) {
	echo $body. "\n";
	exit( 0 );
}

$decoded = json_decode( $body, true );

if( is_array( $decoded ) === false ) {
	fwrite( STDERR, "response is not json:\n". substr( $body, 0, 500 ). "\n" );
	exit( 1 );
}

// The module treats "not a list" as a failed fetch - an unknown artist or a
// bad app_id answers with an object carrying an error/warning key instead
if( array_is_list( $decoded ) === false ) {
	echo "NOT a list - the module would treat this as a failed fetch:\n";
	print_r( $decoded );
	exit( 1 );
}

echo count( $decoded ). " event(s)\n\n";

if( $decoded === [] ) {
	echo "No upcoming shows. That is a valid answer - the [noshows] block renders.\n";
	exit( 0 );
}

// Check the first event against every field the normalizer reads
$first		= $decoded[0];
$expected	= [
	'datetime'			=> isset( $first['datetime'] ),
	'url'						=> isset( $first['url'] ),
	'title'					=> isset( $first['title'] ),
	'venue'					=> is_array( $first['venue'] ?? null ),
	'venue.name'		=> isset( $first['venue']['name'] ),
	'venue.city'		=> isset( $first['venue']['city'] ),
	'venue.region'	=> isset( $first['venue']['region'] ),
	'venue.country'	=> isset( $first['venue']['country'] ),
	'offers'				=> is_array( $first['offers'] ?? null ),
];

echo "Fields the module reads:\n";
foreach( $expected as $key => $present )
	echo '  '. ( ( $present === true ) ? 'ok  ' : 'MISSING  ' ). $key. "\n";

echo "\nAll keys actually present on event[0]:\n  ". implode( ', ', array_keys( $first ) ). "\n";

if( is_array( $first['venue'] ?? null ) === true )
	echo "\nvenue keys:\n  ". implode( ', ', array_keys( $first['venue'] ) ). "\n";

if( is_array( $first['offers'] ?? null ) === true && isset( $first['offers'][0] ) === true )
	echo "\noffers[0] keys:\n  ". implode( ', ', array_keys( $first['offers'][0] ) ). "\n";

echo "\nFirst event as the module would render it:\n";
$timestamp = strtotime( (string) ( $first['datetime'] ?? '' ) );
echo '  date     '. ( ( $timestamp !== false ) ? date( 'd.m.Y H:i', $timestamp ) : 'UNPARSEABLE' ). "\n";
echo '  venue    '. ( $first['venue']['name'] ?? '' ). "\n";
echo '  city     '. ( $first['venue']['city'] ?? '' ). "\n";
echo '  tickets  '. ( $first['offers'][0]['url'] ?? ( $first['url'] ?? '' ) ). "\n";
