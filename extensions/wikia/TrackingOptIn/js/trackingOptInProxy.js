define('wikia.trackingOptIn', [
	'wikia.lazyqueue',
	'wikia.log',
	'wikia.window',
], function (lazyQueue, log, win) {
	var optIn,
		logGroup = 'wikia.trackingOptIn - proxy',
		queue;

	init();

	function init() {
		queue = isMobileWikiQueueExists() ? initPoxyQueue() : initFakeQueue();
	}

	function isMobileWikiQueueExists() {
		return typeof win.M !== 'undefined' && typeof win.M.trackingQueue !== 'undefined';
	}

	function initFakeQueue() {
		log('Init fake queue', log.levels.debug, logGroup);

		var fakeConsentQueue = [];
		lazyQueue.makeQueue(fakeConsentQueue, function (callback) {
			callback(optIn);
		});
		fakeConsentQueue.start();
		optIn = true;

		return fakeConsentQueue;
	}

	function initPoxyQueue() {
		log('Init proxy queue', log.levels.debug, logGroup);

		win.M.trackingQueue.push(function (isOptedIn) {
			optIn = isOptedIn;
			log('User opted ' + (optIn ? 'in' : 'out'), log.levels.debug, logGroup);
		});

		return win.M.trackingQueue;
	}

	function pushToUserConsentQueue(callback) {
		queue.push(callback);
	}

	function isOptedIn() {
		log(['isOptedIn - proxy', optIn], log.levels.info, logGroup);
		return optIn;
	}

	return {
		init: function () {
			log(['Placeholder for init', optIn], log.levels.info, logGroup);
		},
		isOptedIn: isOptedIn,
		pushToUserConsentQueue: pushToUserConsentQueue
	}
});
