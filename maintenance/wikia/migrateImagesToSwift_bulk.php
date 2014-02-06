<?php

/**
 * Script that copies files from file system to distributed storage
 *
 * @see http://www.mediawiki.org/wiki/Manual:Image_administration#Data_storage
 *
 * @author Macbre
 * @ingroup Maintenance
 */

require_once( dirname( __FILE__ ) . '/../Maintenance.php' );
require_once( __DIR__ . '/../../includes/wikia/swift/all.php' );

/**
 * Maintenance script class
 */
class MigrateImagesToSwiftBulk2 extends Maintenance {

	const REASON = 'Images migration script';

	const FILES_PER_SEC = 10;
	const KB_PER_SEC = 800;

	const SWIFT_BUCKET_NAME_MIN_LENGTH = 3;

	// log groups
	const LOG_MIGRATION_PROGRESS = 'swift-migration-progress';
	const LOG_MIGRATION_ERRORS = 'swift-migration-errors';

	const LOCAL_PATH = '/raid/images/by_id/';

	// connections to Swift backends images will be migrated to
	/* @var \Wikia\SwiftStorage[] $swiftBackends */
	private $swiftBackends;
	private $timePerDC = [];

	private $shortBucketNameFixed = false;

	// stats
	private $imagesCnt = 0;
	private $imagesSize = 0;
	private $migratedImagesCnt = 0;
	private $migratedImagesFailedCnt = 0;
	private $migratedImagesSize = 0;

	private $time = 0;

	private $pathPrefix = null;
	private $useDiff = false;
	private $useLocalFiles = false;
	private $threads = 10;
	private $hammer = null;

	private $debug = false;
	/** @var Wikia\Swift\Logger\Logger $logger */
	private $logger;

	/**
	 * Set script options
	 */
	public function __construct() {
		parent::__construct();
		$this->addOption( 'force', 'Perform the migration even if $wgEnableSwiftFileBackend = true' );
		$this->addOption( 'stats-only', 'Show stats (number of files and total size) and then exit' );
		$this->addOption( 'dry-run', 'Migrate images, but don\'t disable uploads and don\'t switch wiki to Swift backend' );
		$this->addOption( 'dc', 'Comma separated list of DCs to migrate images to (defaults to "sjc,res")' );
		$this->addOption( 'bucket', 'Force a different bucket name than the default one (for testing only!)' );
		$this->addOption( 'debug', 'Show a lot of debugging stuff.' );
		$this->addOption( 'diff', 'Use incremental strategy while uploading files.' );
		$this->addOption( 'threads', 'Total number of threads (gets split across DCs) (default: 10, max. 400).' );
		$this->addOption( 'local', 'Read files from local file system (uses: /raid/images/by_id/).' );
		$this->addOption( 'wait', 'Add en extra 180 seconds sleep after disabling uploads.' );
		$this->addOption( 'hammer', 'Hammer a single host.' );
		$this->mDescription = 'Copies files from file system to distributed storage';
	}

	/**
	 * Set up the config variables
	 */
	private function init() {
		global $wgUploadDirectory, $wgDBname, $wgCityId;
		$this->shortBucketNameFixed = $this->fixShortBucketName();

		$bucketName = $this->getOption('bucket', false);
		$dcs = explode(',', $this->getOption('dc', 'sjc,res'));

		foreach($dcs as $dc) {
			if (!is_string($bucketName)) {
				// use bucket name taken from wiki upload path
				$swiftBackend = \Wikia\SwiftStorage::newFromWiki( $wgCityId, $dc );
			}
			else {
				// force a different bucket name via --bucket
				$swiftBackend = \Wikia\SwiftStorage::newFromContainer( $bucketName, '/images', $dc );
			}

			$remotePath = $swiftBackend->getUrl( '' );
			$this->output( "Migrating images on {$wgDBname} - <{$wgUploadDirectory}> -> <{$remotePath}> [dc: {$dc}]...\n" );

			$this->swiftBackends[$dc] = $swiftBackend;
			$this->timePerDC[$dc] = 0;

			$this->pathPrefix = $swiftBackend->getPathPrefix();
		}
	}

	/**
	 * Try to "fix" wikis with too short (less than 3 characters) bucket names
	 *
	 * $wgUploadDirectory = '/images/v/vs/pl/images'
	 * wgUploadPath = 'http://images.wikia.com/vs/pl/images'
	 *
	 * Migrate to:
	 *
	 * $wgUploadDirectory = '/images/v/vs_/pl/images'
	 * wgUploadPath = 'http://images.wikia.com/vs_/pl/images'
	 *
	 * Container name: "vs_"
	 *
	 * This method sets $wgUploadDirectoryNFS WF variable.
	 * It is used when synchronizing Swift operations back to NFS storage.
	 *
	 * @return boolean true if the "fix" was applied
	 */
	private function fixShortBucketName() {
		global $wgUploadDirectory, $wgUploadPath, $wgUploadDirectoryNFS;

		$nfsUploadDirectory = !empty($wgUploadDirectoryNFS) ? $wgUploadDirectoryNFS : $wgUploadDirectory;

		$parts = explode('/', trim($nfsUploadDirectory, '/')); // images, first bucket name letter, <bucket name>, ...
		$bucketName = $parts[2];

		// bucket name is fine, leave now
		if (strlen($bucketName) >= self::SWIFT_BUCKET_NAME_MIN_LENGTH) {
			if (!$this->hasOption('force')) return false;
		}

		// keep the old path
		$wgUploadDirectoryNFS = $nfsUploadDirectory;

		// fill with underscores
		$bucketName = str_pad($bucketName, self::SWIFT_BUCKET_NAME_MIN_LENGTH, '_', STR_PAD_RIGHT);

		// update $wgUploadDirectory and wgUploadPath accordingly
		$parts[2] = $bucketName;
		$wgUploadDirectory = '/' . join('/', $parts);
		$wgUploadPath = 'http://images.wikia.com/' . join('/', array_slice($parts, 2)); // remove /images/ and first letter parts

		self::log( __CLASS__, "short bucket name fix applied - '{$bucketName}', <{$wgUploadPath}>, <{$wgUploadDirectory}>, NFS: <{$wgUploadDirectoryNFS}>" , self::LOG_MIGRATION_PROGRESS );
		return true;
	}

	/**
	 * Get local path to an image
	 *
	 * Example: /6/6b/DSCN0906.JPG
	 *
	 * Add $wgUploadDirectory as a prefix to get full local path to an image
	 *
	 * @param $$row array image data
	 * @return string image path
	 */
	private function getImagePath( Array $row ) {
		$hash = md5( $row['name'] );

		return sprintf(
			'%s/%s%s/%s',
			$hash { 0 } ,
			$hash { 0 } ,
			$hash { 1 } ,
			$row['name']
		);
	}

	/**
	 * Get local path to old image
	 *
	 * Example: /archive/0/0a/20120924125348!UploadTest.png
	 *
	 * Add $wgUploadDirectory as a prefix to get full local path to archived image
	 *
	 * @param $$row array image data
	 * @return string image path
	 */
	private function getOldImagePath( Array $row ) {
		// /0/0a/UploadTest.png -> /archive/0/0a/20120924125348!UploadTest.png
		$hash = md5( $row['name'] );

		return sprintf(
			'archive/%s/%s%s/%s',
			$hash { 0 } ,
			$hash { 0 } ,
			$hash { 1 } ,
			$row['archived_name']
		);
	}

	/**
	 * Get local path to removed image
	 *
	 * Example: /deleted/4/u/m/4um19gqt6qjuq1m8qgqwyf04zgmtk2s.png
	 *
	 * Add $wgUploadDirectory as a prefix to get full local path to archived image
	 *
	 * @param $$row array image data
	 * @return string|bool image path or false if storage_key is empty
	 */
	private function getRemovedImagePath( Array $row ) {
		$hash = $row['storage_key'];

		if ( $hash === '' ) return false;

		return sprintf(
			'deleted/%s/%s/%s/%s',
			$hash { 0 } ,
			$hash { 1 } ,
			$hash { 2 } ,
			$row['storage_key']
		);
	}

	protected $allFiles = array();

	/**
	 * Copy given file to Swift storage
	 *
	 * @param $path string full file path to be migrated
	 * @param $path array image info
	 */
	private function queueFile( $path, Array $row ) {
		global $wgUploadDirectory, $wgUploadDirectoryNFS;

		if ( $path === false ) return;

		$uploadDir = !empty($wgUploadDirectoryNFS) ? $wgUploadDirectoryNFS : $wgUploadDirectory;

		// set metadata
		$metadata = [];
		$mime = "{$row['major_mime']}/{$row['minor_mime']}";

		if ( !empty( $row['hash'] ) ) {
			$metadata['Sha1Base36'] = $row['hash'];
		}

		$src = $uploadDir . '/' . $path;
		if ( $this->useLocalFiles ) {
			$src = preg_replace("#^/images/#",self::LOCAL_PATH,$src);
		}

		$this->allFiles[] = array(
			'src' => $src,
			'dest' => ltrim( $this->pathPrefix . '/' . $path, '/'),
			'metadata' => $metadata,
			'mime' => $mime,
		);
	}

	/**
	 * Log to /var/log/private file
	 *
	 * @param $method string method
	 * @param $msg string message to log
	 * @param $group string file to log to
	 */
	private static function log($method, $msg, $group) {
		\Wikia::log($group . '-WIKIA', false, $method . ': ' . $msg, true /* $force */);
	}

	private function fatal($method, $msg, $group) {
		\Wikia::log($group . '-WIKIA', false, $method . ': ' . $msg, true /* $force */);
		$this->logger->log(0,$msg);
		die($msg . PHP_EOL);
	}

	public function execute() {
		global $wgDBname, $wgCityId, $wgExternalSharedDB, $wgUploadDirectory, $wgUploadDirectoryNFS;

		$this->debug = $this->hasOption( 'debug' );
		$this->logger = new \Wikia\Swift\Logger\Logger($this->debug ? 5 : 0,-1,10);
		$this->logger->setFile('/var/log/migration/'.$wgDBname.'.log');
		$this->logger = $this->logger->prefix($wgDBname);

		// force migration of wikis with read-only mode
		if (wfReadOnly()) {
			global $wgReadOnly;
			$wgReadOnly = false;
		}

		$this->init();
		$dbr = $this->getDB( DB_SLAVE );

		$isForced = $this->hasOption( 'force' );
		$isDryRun = $this->hasOption( 'dry-run' );

		$this->useDiff = $this->getOption('diff',false);
		$this->useLocalFiles = $this->getOption('local',false);

		$this->threads = intval($this->getOption('threads',10));
		$this->threads = min(400,max(1,$this->threads));

		$this->hammer = $this->getOption('hammer',null);

		$uploadDir = !empty($wgUploadDirectoryNFS) ? $wgUploadDirectoryNFS : $wgUploadDirectory;
		if ( $this->useLocalFiles ) {
			$uploadDir = preg_replace("#^/images/#",self::LOCAL_PATH,$uploadDir);
		}
		if ( !is_dir($uploadDir) ) {
			$this->fatal(__CLASS__,"Could not read the source directory: {$uploadDir}",self::LOG_MIGRATION_ERRORS);
		}

		// just don't fuck everything!
		if ( $this->useLocalFiles && !$isDryRun ) {
			if ( gethostname() !== 'file-s4' ) {
				$this->fatal(__CLASS__,"Incremental upload requires access to master file system (don't use --local)",self::LOG_MIGRATION_ERRORS);
			}
		}
		if ( !empty($this->hammer) && !$isDryRun ) {
			$this->fatal(__CLASS__,"Hammer option not supported when not using --dry-run",self::LOG_MIGRATION_ERRORS);
		}

		// one migration is enough
		global $wgEnableSwiftFileBackend, $wgEnableUploads, $wgDBname;
		if ( !empty( $wgEnableSwiftFileBackend ) && !$isForced ) {
			$this->error( "\$wgEnableSwiftFileBackend = true - new files storage already enabled on {$wgDBname} wiki!", 1 );
		}

		if ( empty( $wgEnableUploads ) && !$isForced ) {
			$this->error( "\$wgEnableUploads = false - migration is already running on {$wgDBname} wiki!", 1 );
		}

		// get images count
		$tables = [
			'filearchive' => 'fa_size',
			'image' => 'img_size',
			'oldimage' => 'oi_size',
		];

		foreach ( $tables as $table => $sizeField ) {
			$row = $dbr->selectRow( $table, [
					'count(*) AS cnt',
					"SUM({$sizeField}) AS size"
				],
				[],
				__METHOD__
			);

			$this->output( sprintf( "* %s:\t%d images (%d MB)\n",
				$table,
				$row->cnt,
				round( $row->size / 1024 / 1024 )
			) );

			$this->imagesCnt += $row->cnt;
			$this->imagesSize += $row->size;
		}

		$this->output( sprintf( "\n%d image(s) (%d MB) will be migrated (should take ~ %s with %d kB/s / ~ %s with %d files/sec)...\n",
			$this->imagesCnt,
			round( $this->imagesSize / 1024 / 1024 ),
			Wikia::timeDuration($this->imagesSize / 1024 / self::KB_PER_SEC),
			self::KB_PER_SEC,
			Wikia::timeDuration($this->imagesCnt / self::FILES_PER_SEC),
			self::FILES_PER_SEC
		) );

		if ( $this->hasOption( 'stats-only' ) ) {
			return;
		}

		// ok, so let's start...
		$this->time = time();

		self::log( __CLASS__, 'migration started', self::LOG_MIGRATION_PROGRESS );

		// wait a bit to prevent deadlocks (from 0 to 2 sec)
		usleep( mt_rand(0,2000) * 1000 );

		// lock the wiki
		$dbw = $this->getDB( DB_MASTER, array(), $wgExternalSharedDB );
		if (!$isDryRun) {
			$dbw->replace( 'city_image_migrate', [ 'city_id' ], [ 'city_id' => $wgCityId, 'locked' => 1 ], __CLASS__ );
		}

		// block uploads via WikiFactory
		if (!$isDryRun) {
			WikiFactory::setVarByName( 'wgEnableUploads',     $wgCityId, false, self::REASON );
			WikiFactory::setVarByName( 'wgUploadMaintenance', $wgCityId, true,  self::REASON );

			$this->output( "Uploads and image operations disabled\n\n" );

			if ( $this->hasOption('wait') ) {
				$this->output( "Sleeping for 180 seconds to allow Apache to finish handling upload requests." );
				sleep(180);
			}
		}
		else {
			$this->output( "Performing dry run...\n\n" );
		}

		// prepare the list of files to migrate to new storage
		// (a) current revisions of images
		// @see http://www.mediawiki.org/wiki/Image_table
		$this->output( "\nA) Current revisions of images - /images\n" );

		$res = $dbr->select( 'image', [
			'img_name AS name',
			'img_size AS size',
			'img_sha1 AS hash',
			'img_major_mime AS major_mime',
			'img_minor_mime AS minor_mime',
		] );

		while ( $row = $res->fetchRow() ) {
			$path = $this->getImagePath( $row );
			$this->queueFile( $path, $row );
		}

		// (b) old revisions of images
		// @see http://www.mediawiki.org/wiki/Oldimage_table
		$this->output( "\nB) Old revisions of images - /archive\n" );

		$res = $dbr->select( 'oldimage', [
			'oi_name AS name',
			'oi_archive_name AS archived_name',
			'oi_size AS size',
			'oi_sha1 AS hash',
			'oi_major_mime AS major_mime',
			'oi_minor_mime AS minor_mime',
		] );

		while ( $row = $res->fetchRow() ) {
			$path = $this->getOldImagePath( $row );
			$this->queueFile( $path, $row );
		}

		// (c) deleted images
		// @see http://www.mediawiki.org/wiki/Filearchive_table
		$this->output( "\nC) Deleted images - /deleted\n" );

		$res = $dbr->select( 'filearchive', [
			'fa_name AS name',
			'fa_storage_key AS storage_key',
			'fa_size AS size',
			'fa_major_mime AS major_mime',
			'fa_minor_mime AS minor_mime',
		] );

		while ( $row = $res->fetchRow() ) {
			$path = $this->getRemovedImagePath( $row );
			$this->queueFile( $path, $row );
		}

		$this->processQueue();

		echo count($this->allFiles) . PHP_EOL;

		// stats per DC
		$statsPerDC = [];
		foreach ($this->timePerDC as $dc => $time) {
			$statsPerDC[] = sprintf("%s took %s", $dc, Wikia::timeDuration(round($time)));
		}



		// summary
		$totalTime = time() - $this->time;

		$report = sprintf( 'Migrated %d files (%d MB) with %d fails in %s (%.2f files/sec, %.2f kB/s) - DCs: %s',
			$this->migratedImagesCnt,
			round( $this->migratedImagesSize / 1024 / 1024 ),
			$this->migratedImagesFailedCnt,
			Wikia::timeDuration( $totalTime ),
			floor( $this->imagesCnt ) / ( time() - $this->time ),
			( $this->migratedImagesSize / 1024 ) / ( time() - $this->time ),
			join(', ', $statsPerDC)
		);

		$this->output( "\n{$report}\n" );
		self::log( __CLASS__, 'migration completed - ' . $report, self::LOG_MIGRATION_PROGRESS );

		// if running in --dry-run, leave now
		if ($isDryRun) {
			$this->output( "\nDry run completed!\n" );
			return;
		}

		// unlock the wiki
		$dbw->ping();
		$dbw->replace( 'city_image_migrate', [ 'city_id' ], [ 'city_id' => $wgCityId, 'locked' => 0 ], __CLASS__ );

		// update wiki configuration
		// enable Swift storage via WikiFactory
		WikiFactory::setVarByName( 'wgEnableSwiftFileBackend', $wgCityId, true, sprintf('%s - migration took %s', self::REASON, Wikia::timeDuration( $totalTime ) ) );

		$this->output( "\nNew storage enabled\n" );

		// too short bucket name fix
		if ($this->shortBucketNameFixed) {
			global $wgUploadPath, $wgUploadDirectory, $wgUploadDirectoryNFS;
			WikiFactory::setVarByName( 'wgUploadPath',         $wgCityId, $wgUploadPath,         self::REASON );
			WikiFactory::setVarByName( 'wgUploadDirectory',    $wgCityId, $wgUploadDirectory,    self::REASON );
			WikiFactory::setVarByName( 'wgUploadDirectoryNFS', $wgCityId, $wgUploadDirectoryNFS, self::REASON );

			$this->output( "\nNew upload directory set up\n" );
		}

		// enable uploads via WikiFactory
		// wgEnableUploads = true / wgUploadMaintenance = false (remove values from WF to give them the default value)
		WikiFactory::removeVarByName( 'wgEnableUploads',     $wgCityId, self::REASON );
		WikiFactory::removeVarByName( 'wgUploadMaintenance', $wgCityId, self::REASON );

		$this->output( "\nUploads and image operations enabled\n" );

		$this->output( "\nDone!\n" );
	}

	protected function processQueue() {
		global $wgFSSwiftDC, $wgDBname;

//		var_dump($this->allFiles);
//		return;

		$logger = $this->logger;

		$targets = array();
		foreach($this->swiftBackends as $dc => $swift) {
			$hostnames = $wgFSSwiftDC[$dc]['servers'];
			if ( $this->hammer !== null ) {
				if ( !in_array($this->hammer,$hostnames) ) {
					$logger->log(0,"Skipping DC {$dc} due to --hammer argument");
					continue;
				} else {
					$hostnames = array( $this->hammer );
					$logger->log(0,"Using only {$this->hammer} for DC {$dc}");
				}
			}
			$authConfig = $wgFSSwiftDC[$dc]['config'];
			$cluster = new \Wikia\Swift\Net\Cluster($dc,$hostnames,$authConfig);
			$container = new \Wikia\Swift\Entity\Container($swift->getContainerName());
			$targets[] = new \Wikia\Swift\Wiki\Target($cluster,$container);
		}

		if ( empty($targets) ) {
			$logger->log(0,"No DC remamining after applying --hammer");
			return;
		}

		$files = $this->allFiles;
//		$files[2]['src'] = $files[1]['src'];

		if ( $this->useDiff ) {
			$migration = new \Wikia\Swift\Wiki\DiffMigration($targets,$files,$logger);
		} else {
			$migration = new \Wikia\Swift\Wiki\SimpleMigration($targets,$files,$logger);
		}
		$migration->setThreads(400);
		$migration->setLogger($logger);
		$migration->run();

	}

}

$maintClass = "MigrateImagesToSwiftBulk2";
require_once( RUN_MAINTENANCE_IF_MAIN );
