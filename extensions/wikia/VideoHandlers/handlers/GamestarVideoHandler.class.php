<?php

class GamestarVideoHandler extends VideoHandler {
	
	protected $apiName = 'GameStarApiWrapper';
	protected static $urlTemplate = 'http://www.gamestar.de/jw5/player.swf?config=http://www.gamestar.de/emb/getVideoData5.cfm?vid=$1';
	protected static $providerDetailUrlTemplate = 'http://www.gamestar.de/index.cfm?pid=1589&pk=$1';
	protected static $providerHomeUrl = 'http://www.gamestar.de/';
	
	public function getEmbed( $articleId, $width, $autoplay = false, $isAjax = false, $postOnload = false ) {
		$height =  $this->getHeight( $width );
		$params = array('rel'=>0);
		$url = str_replace('$1', $this->getEmbedVideoId(), static::$urlTemplate);
		
		$html = <<<EOT
<object width="$width" height="$height">
	<param name="movie" value="$url"></param>
	<param name="allowFullScreen" value="true"></param>
	<param name="allowscriptaccess" value="always"></param>
	<embed src="$url" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="$width" height="$height"></embed>
</object>
EOT;
		return $html;
	}

}
