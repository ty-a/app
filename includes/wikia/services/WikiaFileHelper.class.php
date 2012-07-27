<?php
/*
 * Helper service to maintain new video logic / old video logic
 */
class WikiaFileHelper extends Service {

	const maxWideoWidth = 1200;

	/*
	 * Checks if videos on the wiki are converted to new format (File namespace)
	 * @return boolean
	 */
	public static function isVideoStoredAsFile() {
		
		$convertedVar = F::app()->wg->videoHandlersVideosMigrated;
		return !empty( $convertedVar );
	}	

	/*
	 * Checks if given File is video
	 * @param $file WikiaLocalFile object or Title object eventually
	 * @return boolean
	 */
	public static function isFileTypeVideo( $file ) {
		
		if ( self::isVideoStoredAsFile() ) {
			// File can be video only when new video logic is enabled for the wiki
			if ( $file instanceof Title ) {
				$file = wfFindFile( $file );
			}
			return self::isVideoFile( $file );
		}
		return false;
	}

	public static function isVideoFile( $file ) {
		return ( $file instanceof LocalFile && $file->getHandler() instanceof VideoHandler);
	}

	/*
	 * Checks if given Title is video 
	 * @return boolean
	 */
	public static function isTitleVideo( $title, $allowOld = true ) {
		
		$title = self::getTitle( $title );
		
		if ( empty($title) ) {
			return false;
		}
		
		if ( self::isVideoStoredAsFile() ) {

			// video-as-file logic
			if ( self::isFileTypeVideo($title) ) {
			
				return true;
			}
			return false;

		} elseif ( ( $title->getNamespace() == NS_VIDEO ) && $allowOld ) {

			return true;
		}
		
		return false;
	}


	public static function getTitle( $mTitle ){

		if ( !( $mTitle instanceof Title ) ) {

			$mTitle = Title::newFromText( $mTitle );
			if ( !($mTitle instanceof Title) ) {
				return false;
			}
		}

		return $mTitle;
	}

	/*
	 * Looks up videos with same provider and videoId
	 * as specified inside currently uploaded videos on wiki
	 * (searches Image table)
	 */
	public static function findVideoDuplicates( $provider, $videoId ) {
		//print "Looking for duplicaes of $provider $videoId\n";
		$dbr = wfGetDB(DB_MASTER); // has to be master otherwise there's a chance of getting duplicates

		$videoStr = (string)$videoId;
		$rows = $dbr->select(
			'image',
			'*',
			"img_media_type='VIDEO' AND img_minor_mime='$provider' " .
			"AND img_metadata LIKE '%s:7:\"videoId\";s:".strlen($videoStr).':"'.$videoStr."\";%'"
		);

		$result = array();

		while($row = $dbr->fetchRow($rows)) {
			$result[] = $row;
		}

		$dbr->freeResult($rows);

		return $result;
	}

	public static function videoPlayButtonOverlay( $width, $height ) {

		$sizeClass = 'mid';
		if ( $width <= 170 ) {
			$sizeClass = 'min';
		}
		if ( $width > 360 ) {
			$sizeClass = 'max';
		}

		$playButton = array(
			"class"		=> "Wikia-video-play-button ".$sizeClass,
			"style"		=> "width: {$width}px; height: ".($height)."px;"
		);
		return  Xml::element( 'span', $playButton, '', false );
	}

	public static function videoInfoOverlay( $width, $title = null ) {
		$html = '';
		if ( $width > 230 && !empty($title) ) {
			if ( is_string($title) ) {
				$media = F::build('Title', array($title, NS_FILE), 'newFromText');
			} else {
				$media = $title;
			}

			$file = wfFindFile( $media );
			if ( !empty($file) ) {
				// video title
				$width = $width - 60;
				$videoTitle = $media->getText();
				$content = self::videoOverlayTitle( $videoTitle, $width );

				// video duration
				$duration = $file->getHandler()->getFormattedDuration();
				$content .= self::videoOverlayDuration( $duration );
				$content .= '<br />';

				// video views
				$views = DataMartService::getVideoViewsByTitleTotal( $videoTitle );
				$content .= self::videoOverlayViews( $views );

				// info
				$attribs = array(
					"class" => "info-overlay",
				);

				$html = Xml::tags( 'span', $attribs, $content );
			}
		}

		return $html;
	}

	// get html for title for video overlay
	public static function videoOverlayTitle( $title, $width ) {
		$attribs = array(
			'class' => 'info-overlay-title',
			'style' => 'max-width:'.$width.'px;',
		);

		return Xml::element( 'span', $attribs, $title, false );
	}

	// get html for duration for video overlay
	public static function videoOverlayDuration( $duration ) {
		$html = '';
		if ( !empty($duration) ) {
			$attribs = array(
				'class' => 'info-overlay-duration',
			);

			$html = Xml::element( 'span', $attribs, "($duration)", false );
		}

		return $html;
	}

	// get html for views for video overlay
	public static function videoOverlayViews( $views ) {
		$app = F::app();

		$attribs = array(
			'class' => 'info-overlay-views',
		);

		$views = $app->wf->Msg( 'videohandler-video-views', $app->wg->Lang->formatNum($views) );

		return Xml::element( 'span', $attribs, $views, false );
	}

	/*
	 * Checks if user wants to have old image bahaviour
	 * @return boolean
	 */
	public static function preserveOldImageBehaviour() {
		
		return false;
	}

	/**
	 * Can WikiaVideo extension be used to ingest video
	 * @return boolean 
	 */
	public static function useWikiaVideoExtForIngestion() {
		return !empty(F::app()->wg->ingestVideosUseWikiaVideoExt);
	}

	/**
	 * Can VideoHandlers extensions be used to ingest video
	 * @return boolean
	 */
	public static function useVideoHandlersExtForIngestion() {
		return static::isVideoStoredAsFile() || !empty(F::app()->wg->ingestVideosUseVideoHandlersExt);
	}

	/**
	 * Can VideoHandlers extension be used to embed video
	 * @return boolean
	 */
	public static function useWikiaVideoExtForEmbed() {
		return !static::isVideoStoredAsFile() && !empty(F::app()->wg->embedVideosUseWikiaVideoExt);		
	}

	/**
	 * Can VideoHandlers extension be used to embed video
	 * @return boolean
	 */
	public static function useVideoHandlersExtForEmbed() {
		return static::isVideoStoredAsFile() || !empty(F::app()->wg->embedVideosUseVideoHandlersExt);
	}

	/**
	 * Could the given URL exist on this wiki? Does not actually check if
	 * video exists.
	 * @param string $url
	 * @return boolean
	 */
	public static function isUrlMatchThisWiki($url) {
		return stripos( $url, F::app()->wg->server ) !== false;
	}

	/**
	 * Could the given URL exist on the Wikia video repository? Does not
	 * actually check if video exists.
	 * @param string $url
	 * @return boolean
	 */
	public static function isUrlMatchWikiaVideoRepo($url) {
		return stripos( $url, F::app()->wg->wikiaVideoRepoPath ) !== false;
	}

	public static function getMediaDetailConfig( $config = array() ) {

		$configDefaults = array(
			'contextWidth'          => false,
			'contextHeight'         => false,
			'imageMaxWidth'         => 1000,
			'userAvatarWidth'       => 16
		);

		foreach ( $configDefaults as $key => $val ) {

			if ( empty( $config[$key] ) ) {
				$config[$key] = $val;
			}
		}

		return $config;
	}

	/**
	 * @static
	 * @param Title $fileTitle
	 * @param array $config ( contextWidth, contextHeight, imageMaxWidth, userAvatarWidth )
	 * TODO - this method is very specific to lightbox.  This needs to be refactored back out to lightbox, and return just the basic objects (file, user, tect)
	 */
	public static function getMediaDetail( $fileTitle, $config = array() ) {

		$data = array(
			'mediaType' => '',
			'videoEmbedCode' => '',
			'playerAsset' => '',
			'imageUrl' => '',
			'fileUrl' => '',
			'rawImageUrl' => '',
			'description' => '',
			'userThumbUrl' => '',
			'userId' => '',
			'userName' => '',
			'userPageUrl' => '',
			'articles' => array(),
			'providerName' => '',
			'videoViews' => 0,
			'exists' => false
		);

		if ( !empty($fileTitle) ) {

			if ( $fileTitle->getNamespace() != NS_FILE ) {
				$fileTitle = F::build('Title', array($fileTitle->getDBKey(), NS_FILE), 'newFromText');
			}

			$file = wfFindFile( $fileTitle );

			if ( !empty( $file ) ) {
				$config = self::getMediaDetailConfig( $config );

				$data['exists'] = true;

				$data['mediaType'] = self::isFileTypeVideo( $file ) ? 'video' : 'image';

				$width = $file->getWidth();
				$height = $file->getHeight();

				if ( $data['mediaType'] == 'video' ) {

					$width  = $config['contextWidth']  ? $config['contextWidth']  : $width;
					$height = $config['contextHeight'] ? $config['contextHeight'] : $height;
					if ( isset( $config['maxHeight'] ) ) {
						$file->setEmbedCodeMaxHeight( $config['maxHeight'] );
					}
					$data['videoEmbedCode'] = $file->getEmbedCode( $width, true, true);
					$data['playerAsset'] = $file->getPlayerAssetUrl();
					$data['videoViews'] = DataMartService::getVideoViewsByTitleTotal( $fileTitle->getText() );

					$mediaPage = F::build( 'WikiaVideoPage', array($fileTitle) );

				} else {

					$width = $width > $config['imageMaxWidth'] ? $config['imageMaxWidth'] : $width;
					$mediaPage = F::build( 'ImagePage', array($fileTitle) );
				}

				$thumb = $file->transform( array('width'=>$width, 'height'=>$height), 0 );
				$user = F::build('User', array( $file->getUser('id') ), 'newFromId' );

				$data['imageUrl'] = $thumb->getUrl();
				$data['fileUrl'] = $fileTitle->getLocalUrl();
				$data['rawImageUrl'] = $file->getUrl();
				$data['userId'] = $user->getId();
				$data['userName'] = $user->getName();
				$data['userThumbUrl'] = F::build( 'AvatarService', array($user, $config['userAvatarWidth'] ), 'getAvatarUrl' );
				$data['userPageUrl'] = $user->getUserPage()->getFullURL();
				$data['description']  = $mediaPage->getContent();

				if ( WikiaFileHelper::isFileTypeVideo($file) ) {
					$data['providerName'] = $file->getProviderName();
				}

				$mediaQuery =  F::build( 'ArticlesUsingMediaQuery' , array( $fileTitle ) );
				$articlesData = $mediaQuery->getArticleList();

				if ( is_array($articlesData) ) {
					foreach ( $articlesData as $art ) {
						$data['articles'][] = array( 'articleUrl' => $art['url'], 'articleTitle' => $art['title'], 'articleNS' => $art['ns'] );
					}
				}

			}
		}

		return $data;

	}

}
