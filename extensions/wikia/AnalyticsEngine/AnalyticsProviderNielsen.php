<?php

class AnalyticsProviderNielsen implements iAnalyticsProvider {

	private static $apid = 'T26086A07-C7FB-4124-A679-8AC404198BA7';
	private static $libraryUrl = 'http://secure-dcr-cert.imrworldwide.com/novms/js/2/ggcmb500.js';
	private static $template = 'extensions/wikia/AnalyticsEngine/templates/nielsen.mustache';

	function getSetupHtml( $params=array() ) {
		return null;
	}

	public function trackEvent($event, $eventDetails=array()){
		return '';
	}

	static function onWikiaSkinTopScripts(&$vars, &$scripts, $skin) {
		global $wgCityId, $wgDBname;

		if (!self::isEnabled()) {
			$scripts .= '<!-- Nielsen is disabled -->';
			return true;
		}

		$scripts .= \MustacheService::getInstance()->render(
			self::$template,
			[
				'url' => self::$libraryUrl,
				'appId' => self::$apid,
				'section' => HubService::getVerticalNameForComscore( $wgCityId ),
				'dbName' => $wgDBname
			]
		);

		return true;
	}

	static public function isEnabled() {
		global $wgEnableNielsen, $wgNoExternals;

		return $wgEnableNielsen && !$wgNoExternals;
	}
}
