<?php

$app = F::app();

$app->registerClass('AdEngine2Controller', __DIR__ . '/AdEngine2Controller.class.php');

$app->registerHook('WikiaSkinTopScripts', 'AdEngine2Controller', 'onWikiaSkinTopScripts');
$app->registerHook('OasisSkinAssetGroupsBlocking', 'AdEngine2Controller', 'onOasisSkinAssetGroupsBlocking');
$app->registerHook('OasisSkinAssetGroups', 'AdEngine2Controller', 'onOasisSkinAssetGroups');
$app->registerHook('LinkerMakeExternalLink', 'AdEngine2Controller', 'onLinkerMakeExternalLink');
$app->registerHook('LinkEnd', 'AdEngine2Controller', 'onLinkEnd');

$app->registerExtensionMessageFile('AdEngine', __DIR__ . '/AdEngine.i18n.php');

$wgExtensionFunctions[] = function() {
	F::build('JSMessages')->registerPackage('AdEngine', array('adengine-*'));
};
