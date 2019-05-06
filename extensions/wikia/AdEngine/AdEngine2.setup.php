<?php

$wgExtensionCredits['other'][] =
	array(
		'name' => 'Ad Engine',
		'author' => 'Wikia',
		'descriptionmsg' => 'adengine-desc',
		'version' => '2.0',
		'url' => 'https://github.com/Wikia/app/tree/dev/extensions/wikia/AdEngine',
	);

// Autoload
$wgAutoloadClasses['AdEngine2ContextService'] =  __DIR__ . '/AdEngine2ContextService.class.php';
$wgAutoloadClasses['AdEngine2Controller'] =  __DIR__ . '/AdEngine2Controller.class.php';
$wgAutoloadClasses['AdEngine2Hooks'] =  __DIR__ . '/AdEngine2Hooks.class.php';
$wgAutoloadClasses['AdEngine2PageTypeService'] = __DIR__ . '/AdEngine2PageTypeService.class.php';
$wgAutoloadClasses['AdEngine2Resource'] = __DIR__ . '/ResourceLoaders/AdEngine2Resource.class.php';
$wgAutoloadClasses['AdEngine2Service'] =  __DIR__ . '/AdEngine2Service.class.php';
$wgAutoloadClasses['AdTargeting'] =  __DIR__ . '/AdTargeting.class.php';
$wgAutoloadClasses['ResourceLoaderAdEngineBase'] = __DIR__ . '/ResourceLoaders/ResourceLoaderAdEngineBase.php';
$wgAutoloadClasses['ResourceLoaderScript'] = __DIR__ . '/ResourceLoaders/ResourceLoaderScript.php';
$wgAutoloadClasses['AdEngine2ApiController'] = __DIR__ . '/AdEngine2ApiController.class.php';

// Rec
$wgAutoloadClasses['ResourceLoaderAdEngineBTCode'] = __DIR__ . '/ResourceLoaders/ResourceLoaderAdEngineBTCode.php';
$wgAutoloadClasses['ResourceLoaderAdEngineHMDCode'] = __DIR__ . '/ResourceLoaders/ResourceLoaderAdEngineHMDCode.php';

// Hooks for AdEngine2
$wgHooks['AfterInitialize'][] = 'AdEngine2Hooks::onAfterInitialize';
$wgHooks['InstantGlobalsGetNewsAndStoriesVariables'][] = 'AdEngine2Hooks::onInstantGlobalsGetNewsAndStoriesVariables';
$wgHooks['InstantGlobalsGetFandomCreatorVariables'][] = 'AdEngine2Hooks::onInstantGlobalsGetFandomCreatorVariables';
$wgHooks['InstantGlobalsGetVariables'][] = 'AdEngine2Hooks::onInstantGlobalsGetVariables';

// i18n
$wgExtensionMessagesFiles['AdEngine'] = __DIR__ . '/AdEngine.i18n.php';
$wgExtensionFunctions[] = function() {
	JSMessages::registerPackage( 'AdEngine', [
		'adengine-*'
	] );
};

JSMessages::enqueuePackage('AdEngine', JSMessages::EXTERNAL);
