<?php
/**
 * Yii log route that turns selected Yii log entries into Sentry events.
 *
 * The route keeps the "should this event leave the app at all?" decisions local:
 * category/status filtering happens before dispatch, and repeated fingerprints are
 * throttled before they consume Sentry quota.
 *
 * Configuration example:
 *
 * 'log' => array(
 *     'class' => 'CLogRouter',
 *     'routes' => array(
 *         array(
 *             'class' => 'ext.components.log.XSentryLogRoute',
 *             'levels' => 'error, warning',
 *             'ignoreCategories' => array(
 *                 'exception.CHttpException.404',
 *             ),
 *             'ignoreHttpStatusCodes' => array(400, 401, 403, 404),
 *             'throttleWindowSeconds' => 300,
 *             'throttleMaxInitialEvents' => 1,
 *             'summaryThreshold' => 25,
 *             'dsn' => 'https://example@example.ingest.sentry.io/1',
 *             'environment' => 'prod',
 *             'release' => 'aadresslehed@2026.05.19',
 *         ),
 *     ),
 * )
 */
class XSentryLogRoute extends CLogRoute
{
	/**
	 * Project DSN from Sentry. The legacy branch uses the Raven client line.
	 */
	public $dsn;
	/**
	 * Deployment environment tag shown in Sentry, such as prod or stage.
	 */
	public $environment;
	/**
	 * Release tag used for regressions and "which deploy caused this?" triage.
	 */
	public $release;
	/**
	 * Optional host name override shown by Sentry for this app instance.
	 */
	public $serverName;
	/**
	 * Fraction of matching events the Sentry SDK should keep, from 0.0 to 1.0.
	 */
	public $sampleRate=1.0;
	/**
	 * Controls whether user names and client IPs are allowed into Sentry payloads.
	 */
	public $sendDefaultPii=false;
	/**
	 * Raw options passed through to `Raven_Client` for SDK-specific tuning.
	 */
	public $clientOptions=array();
	/**
	 * Optional pre-built Raven client, mainly for tests or custom bootstrap.
	 */
	public $client;
	/**
	 * Shared Composer autoloader path for the legacy Raven client.
	 */
	public $vendorAutoloadPath;

	/**
	 * Drops logs by Yii category before payload building.
	 * Supports exact values and `prefix.*` wildcards.
	 */
	public $ignoreCategories=array();
	/**
	 * Drops logs whose message contains a substring or matches a regex.
	 */
	public $ignorePatterns=array();
	/**
	 * Drops events by HTTP status even when category names differ between call sites.
	 */
	public $ignoreHttpStatusCodes=array(404);

	/**
	 * Adds Yii user id, and optionally username, to the Sentry event.
	 */
	public $includeUserContext=false;
	/**
	 * Adds request URL/method/route details so the failing path is visible in Sentry.
	 */
	public $includeRequestContext=true;

	/**
	 * Length of the local suppression window for repeated fingerprints.
	 */
	public $throttleWindowSeconds=300;
	/**
	 * How many first occurrences are allowed through before suppression starts.
	 */
	public $throttleMaxInitialEvents=1;
	/**
	 * Sends one roll-up event once the repeat count reaches this threshold.
	 */
	public $summaryThreshold=0;
	/**
	 * Chooses what makes two log records the "same issue" for throttling/grouping.
	 */
	public $fingerprintStrategy='category_message';

	/**
	 * Application cache component used to share suppression state across workers.
	 */
	public $cacheID='cache';
	/**
	 * Optional direct cache instance, mainly useful in tests or custom bootstrap.
	 */
	public $cache;
	public $cacheKeyPrefix='sentry.logroute.';

	private static $_sdkInitialized=false;
	private static $_client;

	/**
	 * Coerce config into predictable arrays and integers before the first flush.
	 */
	public function init()
	{
		parent::init();

		$this->ignoreCategories=$this->normalizeList($this->ignoreCategories);
		$this->ignorePatterns=$this->normalizeList($this->ignorePatterns);
		$this->ignoreHttpStatusCodes=$this->normalizeIntegerList($this->ignoreHttpStatusCodes);
		$this->throttleWindowSeconds=max(0,(int)$this->throttleWindowSeconds);
		$this->throttleMaxInitialEvents=max(1,(int)$this->throttleMaxInitialEvents);
		$this->summaryThreshold=max(0,(int)$this->summaryThreshold);
	}

	protected function processLogs($logs)
	{
		if(empty($logs))
			return;

		foreach($logs as $log)
		{
			if($this->shouldIgnoreLog($log))
				continue;

			$payload=$this->createPayload($log);
			list($decision,$payload)=$this->decideDispatch($payload);
			if($decision==='suppress')
				continue;
			// Summary events tell Sentry that a flood is still happening without
			// forwarding every occurrence as a separate event.
			if($decision==='send_summary')
				$payload=$this->createSummaryPayload($payload);

			$this->dispatchPayloadSafely($payload);
		}
	}

	/**
	 * Applies the local "never send this to Sentry" rules.
	 */
	protected function shouldIgnoreLog($log)
	{
		$category=isset($log[2]) ? (string)$log[2] : '';
		$message=$this->stringifyMessage(isset($log[0]) ? $log[0] : '');

		if($this->matchesCategory($category))
			return true;

		if($this->matchesPattern($message))
			return true;

		$httpStatusCode=$this->resolveHttpStatusCode($category,$message);
		return $httpStatusCode!==null && in_array($httpStatusCode,$this->ignoreHttpStatusCodes,true);
	}

	/**
	 * Maps Yii's log tuple into the event shape this route sends to Sentry.
	 * Keeping this translation isolated makes filtering and dispatch easier to reason about.
	 */
	protected function createPayload($log)
	{
		$message=$this->stringifyMessage(isset($log[0]) ? $log[0] : '');
		$category=isset($log[2]) ? (string)$log[2] : 'application';
		$level=isset($log[1]) ? (string)$log[1] : CLogger::LEVEL_ERROR;
		$timestamp=isset($log[3]) ? (float)$log[3] : microtime(true);
		$normalizedMessage=$this->normalizeMessage($message);
		$httpStatusCode=$this->resolveHttpStatusCode($category,$message);

		$payload=array(
			'message'=>$message,
			'normalizedMessage'=>$normalizedMessage,
			'level'=>$level,
			'sentryLevel'=>$this->mapSentryLevel($level),
			'category'=>$category,
			'timestamp'=>$timestamp,
			'httpStatusCode'=>$httpStatusCode,
			'fingerprint'=>$this->buildFingerprint($category,$normalizedMessage),
			'tags'=>array(
				'yii.category'=>$category,
				'yii.level'=>$level,
			),
			'extra'=>array(
				'yii.timestamp'=>$timestamp,
				'yii.normalized_message'=>$normalizedMessage,
			),
		);

		if($httpStatusCode!==null)
			$payload['tags']['http.status_code']=(string)$httpStatusCode;

		if($this->environment!==null && $this->environment!=='')
			$payload['tags']['environment']=$this->environment;

		if($this->release!==null && $this->release!=='')
			$payload['tags']['release']=$this->release;

		if(($requestContext=$this->buildRequestContext())!==array())
			$payload['extra']['request']=$requestContext;

		if(($userContext=$this->buildUserContext())!==array())
			$payload['user']=$userContext;

		return $payload;
	}

	/**
	 * Reuses the original event shape so summary events still group with the issue.
	 */
	protected function createSummaryPayload($payload)
	{
		$count=isset($payload['throttle']['count']) ? (int)$payload['throttle']['count'] : 0;
		$suppressed=max(0,$count-$this->throttleMaxInitialEvents);

		$payload['message']='Repeated Yii log events suppressed: '.$payload['message'];
		$payload['tags']['yii.summary']='1';
		$payload['extra']['yii.summary']=array(
			'count'=>$count,
			'suppressed_count'=>$suppressed,
			'window_seconds'=>$this->throttleWindowSeconds,
		);

		return $payload;
	}

	/**
	 * Decides whether this fingerprint should be sent now, summarized, or suppressed.
	 */
	protected function decideDispatch($payload)
	{
		if($this->throttleWindowSeconds<=0)
			return array('send',$payload);

		$cache=$this->getThrottleCache();
		if($cache===null)
			return array('send',$payload);

		if($cache instanceof CDbCache)
			return $this->decideDispatchWithDbCache($cache,$payload);

		$now=$this->currentTime();
		$key=$this->buildThrottleCacheKey($payload['fingerprint']);
		$state=$cache->get($key);
		if(!is_array($state) || empty($state['expires_at']) || $state['expires_at']<=$now)
		{
			$state=array(
				'count'=>0,
				'summary_sent'=>false,
				'expires_at'=>$now+$this->throttleWindowSeconds,
			);
		}

		$state['count']++;
		$decision='suppress';

		if($state['count']<=$this->throttleMaxInitialEvents)
			$decision='send';
		// Only one summary is emitted per window; otherwise a storm would still
		// generate a steady stream of summary events.
		elseif($this->summaryThreshold>0 && !$state['summary_sent'] && $state['count']>=$this->summaryThreshold)
		{
			$state['summary_sent']=true;
			$decision='send_summary';
		}

		$cache->set($key,$state,max(1,$state['expires_at']-$now));

		$payload['throttle']=$state;

		return array($decision,$payload);
	}

	protected function currentTime()
	{
		return time();
	}

	/**
	 * Sentry transport failures must not break request handling or logging flush.
	 */
	protected function dispatchPayloadSafely($payload)
	{
		try
		{
			$this->dispatchPayload($payload);
		}
		catch(Exception $e)
		{
			$this->reportDispatchFailure($e);
		}
	}

	protected function reportDispatchFailure($exception)
	{
		error_log('XSentryLogRoute dispatch failed: '.$exception->getMessage());
	}

	protected function dispatchPayload($payload)
	{
		$client=$this->bootstrapSdk();
		if(!$client)
			return false;

		$data=array(
			'level'=>$payload['sentryLevel'],
			'tags'=>$payload['tags'],
			'extra'=>$payload['extra'],
			'fingerprint'=>array($payload['fingerprint']),
		);

		if(isset($payload['user']))
			$data['user']=$payload['user'];
		if($this->serverName!==null && $this->serverName!=='')
			$data['server_name']=$this->serverName;
		if($this->release!==null && $this->release!=='')
			$data['release']=$this->release;
		if($this->environment!==null && $this->environment!=='')
			$data['environment']=$this->environment;

		$client->captureMessage($payload['message'],array(),$data,false,null);

		return true;
	}

	protected function bootstrapSdk()
	{
		if(self::$_sdkInitialized)
			return self::$_client;

		if(is_object($this->client) && method_exists($this->client,'captureMessage'))
		{
			self::$_client=$this->client;
			self::$_sdkInitialized=true;
			return self::$_client;
		}

		if(!class_exists('Raven_Client',false))
			$this->loadVendorAutoload();
		if(!class_exists('Raven_Client'))
			return false;
		if($this->dsn===null || $this->dsn==='')
			return false;

		$options=$this->clientOptions;

		if($this->environment!==null && $this->environment!=='')
			$options['environment']=$this->environment;
		if($this->release!==null && $this->release!=='')
			$options['release']=$this->release;
		if($this->serverName!==null && $this->serverName!=='')
			$options['server_name']=$this->serverName;
		if($this->sampleRate!==null)
			$options['sample_rate']=(float)$this->sampleRate;
		if($this->serverName!==null && $this->serverName!=='')
			$options['name']=$this->serverName;

		self::$_client=new Raven_Client($this->dsn,$options);
		self::$_sdkInitialized=true;

		return self::$_client;
	}

	protected function loadVendorAutoload()
	{
		$autoloadPath=$this->vendorAutoloadPath;
		if($autoloadPath===null || $autoloadPath==='')
			$autoloadPath=dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

		if(is_string($autoloadPath) && file_exists($autoloadPath))
			require_once $autoloadPath;
	}

	/**
	 * Builds a stable local key for throttling and a stable Sentry fingerprint.
	 */
	protected function buildFingerprint($category,$normalizedMessage)
	{
		switch($this->fingerprintStrategy)
		{
			case 'category':
				$base=$category;
				break;
			case 'message':
				$base=$normalizedMessage;
				break;
			case 'category_message':
			default:
				$base=$category."\n".$normalizedMessage;
				break;
		}

		return sha1($base);
	}

	protected function mapSentryLevel($yiiLevel)
	{
		switch(strtolower((string)$yiiLevel))
		{
			case CLogger::LEVEL_WARNING:
				return 'warning';
			case CLogger::LEVEL_INFO:
				return 'info';
			case CLogger::LEVEL_TRACE:
			case CLogger::LEVEL_PROFILE:
				return 'debug';
			case CLogger::LEVEL_ERROR:
			default:
				return 'error';
		}
	}

	protected function normalizeMessage($message)
	{
		$message=$this->extractFingerprintHeadline($message);
		// Replace unstable values that would otherwise turn one broken code path
		// into thousands of unique fingerprints.
		$message=preg_replace('#\bin\s+/[^:]+:\d+\b#',' in {path}:{line}',$message);
		$message=preg_replace('/\s+/',' ',trim($message));
		$message=preg_replace('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i','{uuid}',$message);
		$message=preg_replace('/\b0x[0-9a-f]+\b/i','{hex}',$message);
		$message=preg_replace('/\b\d{5,}\b/','{int}',$message);

		return $message;
	}

	protected function extractFingerprintHeadline($message)
	{
		$lines=preg_split('/\r\n|\r|\n/',trim((string)$message));
		foreach($lines as $line)
		{
			$line=trim($line);
			if($line==='' || $line==='Stack trace:')
				continue;

			return preg_replace('/^(?:\[[^\]]*\])+\\s*/','',$line);
		}

		return trim((string)$message);
	}

	/**
	 * Adds request or console command details that help identify where the failure came from.
	 */
	protected function buildRequestContext()
	{
		if(!$this->includeRequestContext || Yii::app()===null)
			return array();

		$context=array();
		if(Yii::app() instanceof CConsoleApplication)
		{
			// Console jobs still need enough context to distinguish which command failed.
			if(isset($_SERVER['argv']))
				$context['command']=implode(' ',$_SERVER['argv']);
			return $context;
		}

		$request=Yii::app()->getComponent('request',false);
		if($request!==null)
		{
			if(method_exists($request,'getUrl'))
				$context['url']=$request->getUrl();
			if(method_exists($request,'getRequestType'))
				$context['method']=$request->getRequestType();
			if($this->sendDefaultPii && method_exists($request,'getUserHostAddress'))
				$context['ip']=$request->getUserHostAddress();
		}

		if(($controller=Yii::app()->getController())!==null)
		{
			$context['route']=$controller->getRoute();
			$context['controller']=get_class($controller);
			if($controller->getAction()!==null)
				$context['action']=$controller->getAction()->getId();
		}

		return $context;
	}

	/**
	 * Adds user identifiers only when explicitly enabled in route config.
	 */
	protected function buildUserContext()
	{
		if(!$this->includeUserContext || Yii::app()===null)
			return array();

		$user=Yii::app()->getComponent('user',false);
		if($user===null)
			return array();

		$context=array();
		if(method_exists($user,'getId'))
			$context['id']=(string)$user->getId();
		if($this->sendDefaultPii && method_exists($user,'getName'))
			$context['username']=(string)$user->getName();

		return $context;
	}

	protected function resolveHttpStatusCode($category,$message)
	{
		// Different call sites encode the status differently, so try the cheap,
		// high-signal patterns first instead of forcing one logging convention.
		if(preg_match('/\.([1-5][0-9]{2})$/',$category,$matches))
			return (int)$matches[1];

		if(preg_match('/\bCHttpException\b.*?\b([1-5][0-9]{2})\b/i',$message,$matches))
			return (int)$matches[1];

		if(preg_match('/\bHTTP(?:\s+status)?\s*([1-5][0-9]{2})\b/i',$message,$matches))
			return (int)$matches[1];

		return null;
	}

	protected function matchesCategory($category)
	{
		foreach($this->ignoreCategories as $ignoredCategory)
		{
			if($category===$ignoredCategory)
				return true;

			if(substr($ignoredCategory,-2)==='.*')
			{
				// Keep the trailing dot so `system.db.*` does not also match `system.dba`.
				$prefix=substr($ignoredCategory,0,-1);
				if(strpos($category.'.',$prefix)===0)
					return true;
			}
		}

		return false;
	}

	protected function matchesPattern($message)
	{
		foreach($this->ignorePatterns as $pattern)
		{
			if($this->isRegularExpression($pattern))
			{
				if(@preg_match($pattern,$message))
					return true;
			}
			elseif($pattern!=='' && strpos($message,$pattern)!==false)
				return true;
		}

		return false;
	}

	protected function isRegularExpression($pattern)
	{
		return is_string($pattern) && preg_match('/^\/.+\/[a-zA-Z]*$/',$pattern)===1;
	}

	protected function stringifyMessage($message)
	{
		if(is_string($message))
			return $message;
		if(is_scalar($message) || $message===null)
			return (string)$message;

		return var_export($message,true);
	}

	protected function getThrottleCache()
	{
		// Throttling only works across PHP workers when they share cache state.
		// If no compatible cache exists, fail open and keep sending events.
		if(is_object($this->cache) && method_exists($this->cache,'get') && method_exists($this->cache,'set'))
			return $this->cache;

		if(Yii::app()===null || $this->cacheID===null || $this->cacheID==='')
			return null;

		try
		{
			$cache=Yii::app()->getComponent($this->cacheID);
		}
		catch(Exception $e)
		{
			return null;
		}
		if(is_object($cache) && method_exists($cache,'get') && method_exists($cache,'set'))
			return $cache;

		return null;
	}

	/**
	 * Uses a DB transaction when the throttle cache is backed by CDbCache so
	 * concurrent workers do not all win the "first event" race.
	 */
	protected function decideDispatchWithDbCache($cache,$payload)
	{
		$key=$this->buildThrottleCacheKey($payload['fingerprint']);
		$result=$this->updateDbCacheThrottleState($cache,$key);
		$state=$result['state'];
		$payload['throttle']=$state;

		$decision='suppress';
		if($state['count']<=$this->throttleMaxInitialEvents)
			$decision='send';
		elseif(!empty($result['summary_due']))
			$decision='send_summary';

		return array($decision,$payload);
	}

	protected function updateDbCacheThrottleState($cache,$logicalKey)
	{
		$db=$cache->getDbConnection();
		$db->setActive(true);
		$driver=$db->getDriverName();
		$tableName=$cache->cacheTableName;
		$physicalKey=$this->buildDbCacheKey($cache,$logicalKey);
		$now=$this->currentTime();

		for($attempt=0;$attempt<3;$attempt++)
		{
			$transaction=$this->beginThrottleTransaction($db,$driver);

			try
			{
				$row=$this->loadThrottleRow($db,$tableName,$physicalKey,$driver);
				$state=$this->extractThrottleStateFromRowAtTime($cache,$row,$now);
				$state['count']++;
				$summaryDue=false;
				$shouldMarkSummary=$this->summaryThreshold>0
					&& !$state['summary_sent']
					&& $state['count']>=$this->summaryThreshold;
				if($shouldMarkSummary)
				{
					$state['summary_sent']=true;
					$summaryDue=true;
				}

				$this->storeThrottleStateRow($db,$tableName,$physicalKey,$cache,$state,$state['expires_at'],is_array($row));
				$this->commitThrottleTransaction($db,$transaction,$driver);

				return array(
					'state'=>$state,
					'summary_due'=>$summaryDue,
				);
			}
			catch(CDbException $e)
			{
				$this->rollbackThrottleTransaction($db,$transaction,$driver);
				if(!$this->isDuplicateThrottleKeyException($e,$driver) || $attempt===2)
					throw $e;
			}
			catch(Exception $e)
			{
				$this->rollbackThrottleTransaction($db,$transaction,$driver);
				throw $e;
			}
		}

		return array(
			'state'=>array(
				'count'=>1,
				'summary_sent'=>false,
			),
			'summary_due'=>false,
		);
	}

	protected function beginThrottleTransaction($db,$driver)
	{
		return $db->beginTransaction();
	}

	protected function commitThrottleTransaction($db,$transaction,$driver)
	{
		if($transaction!==null && $transaction->getActive())
			$transaction->commit();
	}

	protected function rollbackThrottleTransaction($db,$transaction,$driver)
	{
		if($transaction!==null && $transaction->getActive())
			$transaction->rollback();
	}

	protected function loadThrottleRow($db,$tableName,$physicalKey,$driver)
	{
		$sql='SELECT expire, value FROM '.$tableName.' WHERE id=:id';
		if($driver!=='sqlite')
			$sql.=' FOR UPDATE';

		return $db->createCommand($sql)->queryRow(true,array(':id'=>$physicalKey));
	}

	protected function extractThrottleStateFromRow($cache,$row)
	{
		return $this->extractThrottleStateFromRowAtTime($cache,$row,$this->currentTime());
	}

	protected function extractThrottleStateFromRowAtTime($cache,$row,$now)
	{
		if(!is_array($row))
			return array(
				'count'=>0,
				'summary_sent'=>false,
				'expires_at'=>$now+$this->throttleWindowSeconds,
			);

		$expire=isset($row['expire']) ? (int)$row['expire'] : 0;
		if($expire>0 && $expire<=$now)
		{
			return array(
				'count'=>0,
				'summary_sent'=>false,
				'expires_at'=>$now+$this->throttleWindowSeconds,
			);
		}

		$value=$this->unserializeCacheValue($cache,$row['value']);
		if(!is_array($value) || !isset($value[0]) || !is_array($value[0]))
		{
			return array(
				'count'=>0,
				'summary_sent'=>false,
				'expires_at'=>$now+$this->throttleWindowSeconds,
			);
		}

		return array(
			'count'=>isset($value[0]['count']) ? (int)$value[0]['count'] : 0,
			'summary_sent'=>!empty($value[0]['summary_sent']),
			'expires_at'=>$expire>0 ? $expire : $now+$this->throttleWindowSeconds,
		);
	}

	protected function storeThrottleStateRow($db,$tableName,$physicalKey,$cache,$state,$expireAt,$exists)
	{
		$params=array(
			':id'=>$physicalKey,
			':expire'=>$expireAt,
			':value'=>$this->serializeCacheValue($cache,$state),
		);

		if($exists)
		{
			$sql='UPDATE '.$tableName.' SET expire=:expire, value=:value WHERE id=:id';
			$command=$db->createCommand($sql);
		}
		else
		{
			$sql='INSERT INTO '.$tableName.' (id, expire, value) VALUES (:id, :expire, :value)';
			$command=$db->createCommand($sql);
		}

		$command->bindValue(':id',$params[':id']);
		$command->bindValue(':expire',$params[':expire']);
		$command->bindValue(':value',$params[':value'],PDO::PARAM_LOB);
		$command->execute();
	}

	protected function buildDbCacheKey($cache,$logicalKey)
	{
		$keyPrefix=$cache->keyPrefix!==null ? $cache->keyPrefix : Yii::app()->getId();
		return md5($keyPrefix.$logicalKey);
	}

	protected function serializeCacheValue($cache,$value)
	{
		if(!property_exists($cache,'serializer'))
			return serialize(array($value,null));
		if($cache->serializer===false)
			return $value;
		$wrapped=array($value,null);
		if($cache->serializer===null)
			return serialize($wrapped);

		return call_user_func($cache->serializer[0],$wrapped);
	}

	protected function unserializeCacheValue($cache,$value)
	{
		if(!property_exists($cache,'serializer'))
			return @unserialize($value);
		if($cache->serializer===false)
			return $value;
		if($cache->serializer===null)
			return @unserialize($value);

		return call_user_func($cache->serializer[1],$value);
	}

	protected function isDuplicateThrottleKeyException($exception,$driver)
	{
		$message=$exception->getMessage();

		if($driver==='sqlite')
			return strpos($message,'UNIQUE constraint failed')!==false;
		if($driver==='pgsql')
			return strpos($message,'SQLSTATE[23505]')!==false;
		if($driver==='mysql')
			return strpos($message,'SQLSTATE[23000]')!==false && strpos($message,'1062')!==false;

		return false;
	}

	protected function buildThrottleCacheKey($fingerprint)
	{
		return $this->cacheKeyPrefix.$fingerprint;
	}

	protected function normalizeList($value)
	{
		if(empty($value))
			return array();

		if(is_array($value))
		{
			$normalized=array();
			foreach($value as $item)
			{
				if($item===null || $item==='')
					continue;
				$normalized[]=$item;
			}

			return array_values($normalized);
		}

		return preg_split('/[\s,]+/',$value,-1,PREG_SPLIT_NO_EMPTY);
	}

	protected function normalizeIntegerList($value)
	{
		$values=$this->normalizeList($value);
		foreach($values as &$item)
			$item=(int)$item;

		return $values;
	}
}
