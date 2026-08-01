[template /templates/kp-head]

<!-- ==========================================================================
     Release
     Zieht den Eintrag aus /releases, der auf status=aktuell steht. Neue Single?
     Im _admin einen neuen Release anlegen, den alten auf "archiv" stellen -
     dieser Block tauscht sich dann von selbst aus, ohne Code-Änderung.
     ========================================================================== -->
<section class="kp-release">
	[elements /releases query="status=aktuell" limit="1"]
		<img class="kp-release-art" src="[[/nino/dir]]/images/[[art]]" alt="[[title]]" width="800" height="800">
		<h2 class="kp-release-title">[[title]]</h2>
		<p class="kp-release-claim">[[claim]]</p>
		<ul class="kp-streams">
			<li>
				<a href="[[spotify]]" target="_blank" rel="noopener" aria-label="[[title]] auf Spotify">
					<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.5 17.31a.75.75 0 0 1-1.03.25c-2.82-1.72-6.37-2.11-10.55-1.16a.75.75 0 1 1-.33-1.46c4.57-1.04 8.5-.59 11.66 1.34.35.22.46.68.25 1.03zm1.47-3.27a.94.94 0 0 1-1.29.31c-3.23-1.98-8.15-2.56-11.97-1.4a.94.94 0 1 1-.54-1.8c4.36-1.32 9.78-.68 13.49 1.6.44.27.58.85.31 1.29zm.13-3.4C15.23 8.34 8.9 8.13 5.2 9.25a1.12 1.12 0 1 1-.65-2.15c4.25-1.29 11.24-1.04 15.67 1.59a1.12 1.12 0 1 1-1.15 1.93z"/></svg>
				</a>
			</li>
			<li>
				<a href="[[apple]]" target="_blank" rel="noopener" aria-label="[[title]] auf Apple Music">
					<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M17.5 0h-11A6.5 6.5 0 0 0 0 6.5v11A6.5 6.5 0 0 0 6.5 24h11a6.5 6.5 0 0 0 6.5-6.5v-11A6.5 6.5 0 0 0 17.5 0zm-.44 5.62v9.34c0 .53-.05.98-.28 1.4-.34.63-.9.99-1.6 1.13l-.63.13c-1.03.2-1.7-.4-1.78-1.32-.07-.76.4-1.44 1.22-1.63l1.06-.24c.4-.1.62-.32.62-.75V8.3c0-.2-.09-.26-.28-.22l-5.2 1.05c-.2.05-.27.13-.27.35v7.7c0 .53-.04.98-.27 1.4-.34.63-.9.99-1.6 1.13l-.64.13c-1.02.2-1.7-.4-1.77-1.32-.07-.76.4-1.44 1.21-1.63l1.07-.24c.4-.1.61-.32.61-.75V7.03c0-.6.32-.94.9-1.06l6.5-1.31c.6-.12 1.13.23 1.13.85z"/></svg>
				</a>
			</li>
			<li>
				<a href="[[deezer]]" target="_blank" rel="noopener" aria-label="[[title]] auf Deezer">
					<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><rect x="18.6" y="2.6" width="5.4" height="3.9" rx=".5"/><rect x="12.4" y="8.5" width="5.4" height="3.9" rx=".5"/><rect x="18.6" y="8.5" width="5.4" height="3.9" rx=".5"/><rect x="6.2" y="14.4" width="5.4" height="3.9" rx=".5"/><rect x="12.4" y="14.4" width="5.4" height="3.9" rx=".5"/><rect x="18.6" y="14.4" width="5.4" height="3.9" rx=".5"/><rect x="0" y="20.3" width="5.4" height="3.9" rx=".5"/><rect x="6.2" y="20.3" width="5.4" height="3.9" rx=".5"/><rect x="12.4" y="20.3" width="5.4" height="3.9" rx=".5"/><rect x="18.6" y="20.3" width="5.4" height="3.9" rx=".5"/></svg>
				</a>
			</li>
		</ul>
	[/elements]
</section>


<!-- ==========================================================================
     Live
     Die beiden Shortcodes unten kommen aus dem eigenen Bandsintown-Modul
     (_nino/KeinePanik/Modules/Bandsintown). Die Termine werden serverseitig
     geholt und hier gerendert - der Besucher spricht nie mit Bandsintown,
     also kein iframe, kein Consent-Banner, kein fremdes Design.

     ACHTUNG beim Bearbeiten: niemals einen Shortcode-Namen in eckigen
     Klammern in einen HTML-Kommentar schreiben. Ninos Shortcode-Parser ist
     eine Regex und kennt keine Kommentare - ein erwaehnter Shortcode wird
     ausgefuehrt und frisst im schlimmsten Fall das halbe Template.
     ========================================================================== -->
<section class="kp-live">
	<h2 class="kp-title">[[/page-home/live/title]]</h2>

	[gigs limit="8"]
		<ul class="kp-gigs"><li class="kp-gig">
			<span class="kp-gig-date">[[datelong]] [[year]]</span>
			<span class="kp-gig-where">
				<span class="kp-gig-city">[[city]]</span>
				<span class="kp-gig-venue">[[venue]]</span>
			</span>
			<span class="kp-gig-action"><a href="[[tickets]]" target="_blank" rel="noopener">[[/page-home/live/tickets]]</a></span>
		</li></ul>
	[/gigs]

	[noshows]
		<p class="kp-noshows-text">[[/page-home/live/empty]]</p>
		<a href="mailto:[[/company/email]]" class="ui-btn">[[/page-home/live/booking]]</a>

		<form class="kp-notify js-kp-notify">
			<span class="kp-notify-label">[[/page-home/live/notify]]</span>
			<div class="kp-notify-row">
				<input type="email" name="email" placeholder="[[/page-home/live/mailplaceholder]]" autocomplete="email" required>
				<button type="submit" class="ui-btn">[[/page-home/live/notifybtn]]</button>
			</div>
			<div class="kp-hp"><input type="text" name="location" tabindex="-1" autocomplete="off" aria-hidden="true"></div>
			<p class="kp-notify-msg" role="status" aria-live="polite"></p>
		</form>
	[/noshows]
</section>


<!-- Bandfoto - Bildslot, damit ihr es im _admin tauschen könnt -->
<div class="kp-photo">
	[image /bandfoto alt="[[/company/name]]"]
</div>


<section class="kp-social">
	<h2 class="kp-title">[[/page-home/social/title]]</h2>
	<ul class="kp-socials">
		<li>
			<a href="[[/social/facebook]]" target="_blank" rel="noopener" aria-label="[[/company/name]] auf Facebook">
				<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M14.6 24V13.1h3.7l.55-4.25h-4.25V6.14c0-1.23.34-2.07 2.11-2.07h2.25V.27C18.58.19 17.25.06 15.7.06c-3.23 0-5.45 1.97-5.45 5.6v3.19H6.5v4.25h3.76V24z"/></svg>
			</a>
		</li>
		<li>
			<a href="[[/social/instagram]]" target="_blank" rel="noopener" aria-label="[[/company/name]] auf Instagram">
				<svg viewBox="0 0 24 24" role="img" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.12 1.38C1.35 2.68.94 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.12.66.66 1.33 1.08 2.12 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.12-1.38.66-.66 1.08-1.33 1.38-2.12.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.12C21.32 1.35 20.65.94 19.86.63 19.1.33 18.22.13 16.95.07 15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm7.85-10.4a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>
			</a>
		</li>
	</ul>
</section>


<section class="kp-contact">
	<h2 class="kp-title">[[/page-home/contact/title]]</h2>
	<a class="kp-contact-mail" href="mailto:[[/company/email]]">[[/company/email]]</a>
</section>

[template /templates/kp-foot]
