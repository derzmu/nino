[template /templates/kp-head]

<section class="ui-section">
	<div class="ui-grid-row">
		<div class="ui-grid-100">
			<h2 class="ui-section-title">[[/webpage/datenschutz/name]]</h2>

			<!--
				BEWUSST LEER GELASSEN - siehe page-impressum.tpl.

				Zwei Punkte, die sich gegenüber der alten Seite ÄNDERN und in der
				Erklärung angepasst gehören:

				1. Bandsintown taucht nicht mehr als eingebundener Dritt-Dienst
				   auf. Die Termine werden jetzt serverseitig geholt (siehe
				   _nino/KeinePanik/Modules/Bandsintown) - der Browser des
				   Besuchers verbindet sich nicht mehr zu Bandsintown, es werden
				   also auch keine IP-Adressen dorthin übertragen. Der
				   entsprechende Absatz zum Widget kann raus.

				2. NEU dazu kommt der Newsletter: Double-Opt-in, gespeichert
				   werden E-Mail-Adresse und Zeitpunkt der Bestätigung in
				   /data/newsletter.php auf eurem eigenen Server. Braucht einen
				   eigenen Absatz (Zweck, Rechtsgrundlage Art. 6 Abs. 1 lit. a
				   DSGVO, Widerruf über den Abmeldelink in jeder Mail).

				Dasselbe gilt für das Kontakt-/Booking-Formular, falls ihr es
				zusätzlich zur mailto-Adresse aktiviert: Einsendungen landen unter
				/data/forms.<Jahr-Monat>.php und sind im _admin einsehbar.
			-->
			<p>TODO: Datenschutzerklärung von keinepanikmusik.de/datenschutz übernehmen und um die beiden Punkte oben ergänzen.</p>

		</div>
	</div>
</section>

[template /templates/kp-foot]
