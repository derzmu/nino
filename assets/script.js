

/**
 *	Nino										A compact filesystembased php framework
 *	keine panik.						Projekt-Skript
 *
 *	script.js								Newsletter-Anmeldung im leeren Live-Zustand.
 *
 *													Bewusst hier und nicht inline im Template: Ninos
 *													Default-CSP ist "default-src 'self'" ohne
 *													script-src 'unsafe-inline', ein <script>-Block im
 *													Template würde vom Browser blockiert. Diese Datei
 *													wird über /nino/html/assets in /.cache/script.js
 *													gebündelt und damit von 'self' geladen.
 *
 *	@package								KeinePanik
 */

( function(wn,dc,dE,bd) {

	const script = {

		/**
		 *	Newsletter-Anmeldung ("sag mir Bescheid, wenn ihr spielt").
		 *
		 *	Das Nino-Newsletter-Modul antwortet auf POST /.newsletter:
		 *	  200 + { status: 'new' }       Bestätigungsmail ist raus
		 *	  200 + { status: 'existing' }  war schon angemeldet, keine neue Mail
		 *	  400  E-Mail fehlt oder ungültig
		 *	  418  Honeypot ausgefüllt (Bot)
		 *	  403  CSRF-Token fehlt oder falsch
		 *
		 *	Den CSRF-Token hängt Nino.http.sendRequest() selbst an - es liest
		 *	dafür das versteckte Feld, das der [csrf]-Shortcode rendert.
		 *
		 *	@return		void
		 */
		initNotify : function() {

			const form = dc.querySelector( '.js-kp-notify' );
			if( form === null )
				return;

			const input		= form.querySelector( 'input[type="email"]' );
			const honeypot	= form.querySelector( 'input[name="location"]' );
			const message	= form.querySelector( '.kp-notify-msg' );
			const button	= form.querySelector( 'button' );

			if( input === null || message === null )
				return;

			const say = function( key ) {
				message.textContent = Nino.content.getText( key ) || '';
			};

			form.addEventListener( 'submit', function( event ) {

				event.preventDefault();

				if( input.value.trim() === '' ) {
					say( '/newsletter/info/email' );
					return;
				}

				// Doppelklicks während der laufenden Anfrage abfangen
				if( button !== null )
					button.disabled = true;

				say( '/newsletter/info/sending' );

				Nino.http.sendRequest( '/.newsletter', 'POST', function( xhr ) {

					if( button !== null )
						button.disabled = false;

					if( xhr.status === 200 ) {
						// 'existing' bekommt bewusst dieselbe Rückmeldung wie 'new' -
						// eine abweichende Antwort würde verraten, welche Adressen
						// bereits in der Liste stehen
						input.value = '';
						say( '/newsletter/info/success' );
						return;
					}

					if( xhr.status === 400 ) {
						say( '/newsletter/info/email' );
						return;
					}

					// 418 (Honeypot) absichtlich wie ein Erfolg behandeln - ein Bot
					// soll nicht lernen, dass er aufgeflogen ist
					if( xhr.status === 418 ) {
						say( '/newsletter/info/success' );
						return;
					}

					say( '/newsletter/info/error' );

				}, {
					email		: input.value.trim(),
					location	: ( honeypot !== null ) ? honeypot.value : ''
				} );
			} );
		},

		/**
		 *	Site-specific ready hook
		 *
		 *	@return		void
		 */
		onReady : function() {

			script.initNotify();
		},


		/**
		 *	Site-specific resize hook (currently unused)
		 *
		 *	@return		void
		 */
		onResize : function() {

		},

		/**
		 *	Site-specific scroll hook (currently unused)
		 *
		 *	@return		void
		 */
		onScroll : function() {

		},
	};

	Nino.events.bindCallback( 'ready', script.onReady );
	Nino.events.bindCallback( 'scroll', script.onScroll );
	Nino.events.bindCallback( 'resize', script.onResize );

})(window, document, document.documentElement, document.body);
