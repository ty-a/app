<?php

use Wikia\Service\Gateway\KubernetesExternalUrlProvider;

class UserProfilePageHooks {

	/**
	 * @brief remove User:: from back link
	 *
	 * @author Tomek Odrobny
	 *
	 * @param Title $title
	 * @param String $ptext
	 *
	 * @return Boolean
	 */
	static public function onSkinSubPageSubtitleAfterTitle( $title, &$ptext ) {
		if ( !empty( $title ) && $title->getNamespace() == NS_USER ) {
			$ptext = $title->getText();
		}

		return true;
	}

	/**
	 * @brief adds wiki id to cache and fav wikis instantly
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	static public function onArticleSaveComplete(
		WikiPage $article, User $user, $text, $summary, $minoredit, $watchthis,
		$sectionanchor, $flags, $revision, Status &$status, $baseRevId
	): bool {
		global $wgCityId;
		if ( $revision !== NULL ) { // do not count null edits
			$wikiId = intval( $wgCityId );

			if ( $user instanceof User && $wikiId > 0 ) {
				$userIdentityBox = new UserIdentityBox( $user );
				$userIdentityBox->addTopWiki( $wikiId );
			}
		}
		return true;
	}

	/**
	 * Exposes variables required by profile edit modal to JS
	 * 
	 * @param array $vars
	 * @param OutputPage $out
	 */
	public static function onMakeGlobalVariablesScript( array &$vars, OutputPage $out ) {
		if ( BodyController::showUserPagesHeader( $out->getTitle() ) ) {
			$urlProvider = new KubernetesExternalUrlProvider();

			$vars['wgUserAvatarServiceUrl'] = $urlProvider->getUrl( 'user-avatar' );
			$vars['wgUserId'] = $out->getUser()->getId();
		}
	}
}
