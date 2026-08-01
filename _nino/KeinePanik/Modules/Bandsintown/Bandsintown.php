<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Bandsintown					Server-side Bandsintown event feed for keine panik.
 *
 *											The band keeps maintaining its shows in Bandsintown (that
 *											is what feeds Spotify/Apple/the fan notifications), but the
 *											site renders them itself instead of embedding the official
 *											widget iframe. Two reasons:
 *
 *											1. Ninos default Content-Security-Policy is
 *												 "default-src 'self'" with no frame-src (see Http's
 *												 $_defaultResponse) - a bandsintown iframe is blocked
 *												 outright unless the CSP is opened up for it.
 *											2. An iframe makes every visitor talk to a US server on
 *												 page load, which needs a consent layer under GDPR.
 *												 Fetching server-side keeps the visitor's browser out
 *												 of it entirely - no third-party request, no consent
 *												 banner, and the markup ends up in the band's own
 *												 design instead of Bandsintown's.
 *
 *	@package						KeinePanik
 */
namespace KeinePanik\Modules {

	class Bandsintown {

		// Where the normalized feed is cached between requests. Same plain
		// php-array convention as /data/forms.<Y-m>.php and /data/newsletter.php,
		// so it stays readable/greppable and Filesystem handles (de)serialization.
		private const string CACHE_PATH = '/data/bandsintown.php';

		// How long a cached feed counts as fresh. Shows do not change by the
		// minute, and a band site is mostly read - an hour keeps the api call
		// off the critical path for all but one request per hour.
		private const int DEFAULT_TTL = 3600;

		// Hard ceiling on the api call. Deliberately short: a slow/unreachable
		// Bandsintown must never hold the whole page hostage - past this we fall
		// back to the last good cache (see _load()).
		private const int TIMEOUT = 4;

		private const string API_BASE = 'https://rest.bandsintown.com/artists/';

		// Month labels for the [[datelong]] fill. Deliberately a plain map
		// rather than IntlDateFormatter/strftime: intl is not guaranteed on
		// the shared hosting Nino targets, and strftime is deprecated as of
		// php 8.1.
		private const array MONTHS = [
			1 => 'JAN', 2 => 'FEB', 3 => 'MRZ', 4 => 'APR', 5 => 'MAI', 6 => 'JUN',
			7 => 'JUL', 8 => 'AUG', 9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DEZ',
		];

		private const array WEEKDAYS = [ 'SO', 'MO', 'DI', 'MI', 'DO', 'FR', 'SA' ];

		/**
		 *	Module initiating
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Html::addShortcode( $appData, 'gigs', 		[ self::class, 'doShortcodeGigs' ] );
			\Nino\Html::addShortcode( $appData, 'noshows',	[ self::class, 'doShortcodeNoshows' ] );
		}

		/**
		 *	Every upcoming show, soonest first. Cached, and resilient: a failing
		 *	api call falls back to the last good cache at any age rather than
		 *	blanking the live section.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											List of normalized events
		 */
		public static function getEvents( array &$appData ): array {

			// One fetch per request at most - the shortcode is used twice
			// ([gigs] and [noshows]), and renderHtml() may run over the same
			// template more than once through the recursive shortcode pass
			if( isset( $appData['./keinepanik/bandsintown/events'] ) === true )
				return $appData['./keinepanik/bandsintown/events'];

			$events = self::_load( $appData );

			// Filter to upcoming only, measured from the start of today rather
			// than "now" - a show tonight must not disappear from the list at
			// soundcheck
			$today = strtotime( 'today midnight' );
			$events = array_values( array_filter( $events, function( array $event ) use ( $today ): bool {
				return $event['timestamp'] >= $today;
			} ) );

			usort( $events, function( array $a, array $b ): int {
				return $a['timestamp'] <=> $b['timestamp'];
			} );

			$appData['./keinepanik/bandsintown/events'] = $events;

			return $events;
		}

		/**
		 *	Render the enclosed content once per upcoming show, substituting the
		 *	event's own [[key]] fills - same shape as the kernel's [elements]
		 *	shortcode, so a template author does not have to learn a second idiom.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string
		 */
		public static function doShortcodeGigs( array &$appData, array $args ): string {

			$content	= $args['content'] ?? '';
			$limit		= (int) ( $args['limit'] ?? -1 );
			$events		= self::getEvents( $appData );

			if( $content === '' || $events === [] )
				return '';

			if( $limit > 0 )
				$events = array_slice( $events, 0, $limit );

			$html	= '';
			$id		= 0;

			foreach( $events as $event ) {

				$keys		= [ '[[.id]]' ];
				$values	= [ (string) $id ];

				foreach( $event as $key => $value ) {
					if( is_scalar( $value ) === false )
						continue;
					$keys[]		= '[['. $key. ']]';
					$values[]	= self::_escape( (string) $value );
				}

				$html .= str_replace( $keys, $values, $content );
				$id++;
			}

			return $html;
		}

		/**
		 *	Render the enclosed content only while there is no upcoming show -
		 *	the empty state (booking call to action + newsletter signup) lives in
		 *	the template, not in here, so it stays editable without touching php.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string
		 */
		public static function doShortcodeNoshows( array &$appData, array $args ): string {

			return ( self::getEvents( $appData ) === [] ) ? ( $args['content'] ?? '' ) : '';
		}

		/**
		 *	Return the cached feed, refreshing it when stale. On a failed refresh
		 *	the stale cache is returned as-is (better a week-old show list than an
		 *	empty one), and the cache timestamp is bumped anyway so a dead api is
		 *	not re-hit on every single request.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		private static function _load( array &$appData ): array {

			$cache	= \Nino\Filesystem::getFileContent( $appData, self::CACHE_PATH, [] );
			$cache	= ( is_array( $cache ) === true ) ? $cache : [];
			$ttl		= (int) ( $appData['/keinepanik/bandsintown/ttl'] ?? self::DEFAULT_TTL );

			if( ( ( $cache['time'] ?? 0 ) + $ttl ) > time() && isset( $cache['events'] ) === true )
				return $cache['events'];

			$fresh = self::_fetch( $appData );

			// Fetch failed - keep serving what we last had, but record the
			// attempt so the next request does not immediately retry
			if( $fresh === null ) {
				$cache['time'] = time();
				\Nino\Filesystem::putFileContent( $appData, self::CACHE_PATH, $cache );
				return $cache['events'] ?? [];
			}

			\Nino\Filesystem::putFileContent( $appData, self::CACHE_PATH, [
				'time'		=> time(),
				'events'	=> $fresh,
			] );

			return $fresh;
		}

		/**
		 *	Call the Bandsintown artist events endpoint. Returns null (not [])
		 *	on any failure, so _load() can tell "no shows" apart from "could not
		 *	ask" - the two must not look the same to the caller.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array | null
		 */
		private static function _fetch( array &$appData ): ?array {

			$artist	= (string) ( $appData['/keinepanik/bandsintown/artist'] ?? '' );
			$appId	= (string) ( $appData['/keinepanik/bandsintown/appid'] ?? '' );

			if( $artist === '' || $appId === '' )
				return null;

			// rawurlencode, not urlencode - the artist name is a path segment,
			// and a literal "+" for a space would be read as a plus sign there
			$url = self::API_BASE. rawurlencode( $artist ). '/events?app_id='. rawurlencode( $appId );

			$raw = self::_request( $url );
			if( $raw === null )
				return null;

			$decoded = json_decode( $raw, true );

			// Bandsintown answers a valid-but-unknown artist with a json object
			// carrying an error/warning key rather than an array of events -
			// treat anything that is not a list as a failed fetch
			if( is_array( $decoded ) === false || array_is_list( $decoded ) === false )
				return null;

			$events = [];
			foreach( $decoded as $entry )
				if( is_array( $entry ) === true ) {
					$normalized = self::_normalize( $entry );
					if( $normalized !== null )
						$events[] = $normalized;
				}

			return $events;
		}

		/**
		 *	Do the actual http call - curl when available, stream wrapper
		 *	otherwise. Both paths are timeout-bounded and error-suppressed:
		 *	Runtime's error handler terminates the whole request on an
		 *	unsuppressed warning, so a network hiccup here would take the page
		 *	down with it.
		 *
		 *	@param		string		$url					Absolute https url
		 *
		 *	@return 	string | null								Response body, or null on any failure
		 */
		private static function _request( string $url ): ?string {

			if( function_exists( 'curl_init' ) === true ) {

				$curl = curl_init( $url );
				curl_setopt_array( $curl, [
					CURLOPT_RETURNTRANSFER	=> true,
					CURLOPT_TIMEOUT					=> self::TIMEOUT,
					CURLOPT_CONNECTTIMEOUT	=> self::TIMEOUT,
					CURLOPT_FOLLOWLOCATION	=> true,
					CURLOPT_MAXREDIRS				=> 3,
					CURLOPT_SSL_VERIFYPEER	=> true,
					CURLOPT_SSL_VERIFYHOST	=> 2,
					CURLOPT_USERAGENT				=> 'keinepanik-website/1.0',
					CURLOPT_HTTPHEADER			=> [ 'Accept: application/json' ],
				] );

				$body		= curl_exec( $curl );
				$status	= (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
				curl_close( $curl );

				return ( is_string( $body ) === true && $status >= 200 && $status < 300 ) ? $body : null;
			}

			if( ini_get( 'allow_url_fopen' ) !== '1' )
				return null;

			$context = stream_context_create( [
				'http' => [
					'method'	=> 'GET',
					'timeout'	=> self::TIMEOUT,
					'header'	=> "Accept: application/json\r\nUser-Agent: keinepanik-website/1.0\r\n",
				],
			] );

			$body = @file_get_contents( $url, false, $context );

			return ( is_string( $body ) === true && $body !== '' ) ? $body : null;
		}

		/**
		 *	Flatten one raw api event into the flat scalar shape the shortcode
		 *	substitutes. Every field access is guarded - the api's exact response
		 *	shape is not contractually stable, and one unexpected entry must
		 *	degrade to "skip this show", never to a fatal.
		 *
		 *	@param		array 		$entry				One raw api event
		 *
		 *	@return 	array | null								Normalized event, or null if unusable
		 */
		private static function _normalize( array $entry ): ?array {

			$datetime	= (string) ( $entry['datetime'] ?? '' );
			$timestamp	= ( $datetime !== '' ) ? strtotime( $datetime ) : false;

			// No parseable date means the entry cannot be sorted or filtered,
			// which is the only thing the live section actually needs
			if( $timestamp === false )
				return null;

			$venue		= ( is_array( $entry['venue'] ?? null ) === true ) ? $entry['venue'] : [];
			$offers		= ( is_array( $entry['offers'] ?? null ) === true ) ? $entry['offers'] : [];

			$tickets	= '';
			$soldout	= false;

			foreach( $offers as $offer ) {

				if( is_array( $offer ) === false )
					continue;

				$status = strtolower( (string) ( $offer['status'] ?? '' ) );

				if( $status === 'sold out' || $status === 'sold_out' )
					$soldout = true;

				if( $tickets === '' && is_string( $offer['url'] ?? null ) === true )
					$tickets = $offer['url'];
			}

			// No dedicated ticket offer - fall back to the event page itself,
			// which at least lets a visitor RSVP/track the show
			if( $tickets === '' )
				$tickets = (string) ( $entry['url'] ?? '' );

			return [
				'timestamp'	=> $timestamp,
				'date'			=> date( 'd.m.Y', $timestamp ),
				'day'				=> date( 'd', $timestamp ),
				'month'			=> self::MONTHS[ (int) date( 'n', $timestamp ) ],
				'year'			=> date( 'Y', $timestamp ),
				'weekday'		=> self::WEEKDAYS[ (int) date( 'w', $timestamp ) ],
				'time'			=> date( 'H:i', $timestamp ),
				'datelong'	=> self::WEEKDAYS[ (int) date( 'w', $timestamp ) ]. ' '. date( 'd', $timestamp ). '. '. self::MONTHS[ (int) date( 'n', $timestamp ) ],
				'venue'			=> (string) ( $venue['name'] ?? '' ),
				'city'			=> (string) ( $venue['city'] ?? '' ),
				'region'		=> (string) ( $venue['region'] ?? '' ),
				'country'		=> (string) ( $venue['country'] ?? '' ),
				'tickets'		=> $tickets,
				'url'				=> (string) ( $entry['url'] ?? '' ),
				'soldout'		=> ( $soldout === true ) ? '1' : '',
				'title'			=> (string) ( $entry['title'] ?? '' ),
			];
		}

		/**
		 *	Escape a value for substitution into html. Mirrors the kernel's own
		 *	Modules\Elements::_escapeFieldValue(), including the "[" escape -
		 *	_doShortcode() runs renderHtml() over every shortcode's return value
		 *	again, so an unescaped bracket in a venue name would be read as a
		 *	shortcode on that second pass.
		 *
		 *	@param		string		$value				Raw value
		 *
		 *	@return 	string
		 */
		private static function _escape( string $value ): string {

			return str_replace( '[', '&#91;', htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) );
		}
	}
}
