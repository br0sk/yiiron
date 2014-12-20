<?php
/**
 * This class is a Yii application component. It handles the communication with the services on iron.io.
 * It is built on top of this
 * IronMQ - A cloud message queue. Using https://github.com/iron-io/iron_mq_php
 * IronWorker - A service for parallel workers. Using https://github.com/iron-io/iron_worker_php
 * IronCache - A cache service https://github.com/iron-io/iron_cache_php
 * The classes are using https://github.com/iron-io/iron_core_php
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */
class EYiiron extends CApplicationComponent
{
  /**
   * This is the iron.io token. It is used for the REST authentication. You can find the token here
   * @link https://hud.iron.io/dashboard
   * @var integer
   */
  public $token;

  /**
   * This is the project id. This is useful since we can create several connectors to different projects.
   * You can find the project id here
   * @link https://hud.iron.io/dashboard
   * @var string
   */
  public $projectId;

  /**
   * An array with the list of iron services to activate.
   * Can be array('mq','worker','cache')
   * @var array
   */
  public $services = array('mq', 'worker', 'cache');

  /**
   * This array is used to exclude the files and folders we do not need to copy
   * to the iron worker target environment. Supply it on the format of the options parameter
   * for CFileHelper::copyDirectory().
   *
   * It has a reasonable default value set.
   *
   * @var array
   */
  public $workerFileCopyOptions = array();

  /**
   * Needs to be set to true if the extension has been installed via Composer.
   * In case of the installation being installed by composer the iron.io classes have been installed
   * to the vendor library. If the extension is installed directly from the zip file the iron.io files
   * are bundled.
   *
   * @var bool
   */
  public $composer = false;

  /**
   * This is the path to the config file that shall be used when running as an IronWorker
   * @var string
   */
  public $configFile  = 'config/console_ironworker.php';
	
  /**
   * This is the PHP version we are using, default is php-5.5.
   * @var string
   */
  public $stack  = 'php-5.5';

  /**
   * This is the request object for iron MQ
   * @var IronMQ
   */
  private $_mq = null;

  /**
   * This is the request object for the Iron Workers
   * @var IronWorker
   */
  private $_worker = null;

  /**
   * This is the request object for the Iron Cache
   * @var IronCache
   */
  private $_cache = null;

  /**
   * Constructor.
   *
   * Just set the config parameters.
   * @param string $token This is the IronIo token. It is used for the auth.
   * @param string $projectId This is the project id. This is useful since we can create several connectors to different projects.
   * @param array $workerFileCopyOptions Supply it on the format of the options parameter for CFileHelper::copyDirectory().
   * @param boolean $composer Indicate if the extension has been installed via composer
   * @param array $services
   */
	public function __construct($token='',$projectId='',$services=array('mq', 'worker', 'cache'),$workerFileCopyOptions=array('exclude' => array('.git', '.csv', '.svn', '.zip', "/runtime", "/config")),$composer=false, $configFile="config/console_ironworker.php", $stack="php-5.5")
	{
		$this->token=$token;
		$this->projectId=$projectId;
		$this->services=$services;
		$this->workerFileCopyOptions=$workerFileCopyOptions;
		$this->composer=$composer;
		$this->configFile=$configFile;
		$this->stack=$stack;
    /**
     * Fix to not include the class twice in Unit tests. This fixes a problem with installing PHPUnit using Composer.
     * note: If you are using a PEAR installation of PHPUnit you might need to remove the if statement.
     */
    if(Yii::app()->getComponent('fixture') === null)
    {
      if($this->composer)
      {
        Yii::import('application.vendors.*');
        require_once('iron-io/iron_core/IronCore.class.php');
        require_once('iron-io/iron_worker/IronWorker.class.php');
        require_once('iron-io/iron_mq/IronMQ.class.php');
        require_once('iron-io/iron_cache/IronCache.class.php');
      }
      else
      {
        require_once('lib/IronCore.class.php');
        require_once('lib/IronWorker.class.php');
        require_once('lib/IronMQ.class.php');
        require_once('lib/IronCache.class.php');
      }
    }
	}

  /**
   * Make the service automatically connect to the specified iron.io services
   */
  public function init()
	{
		$this->connect();
		parent::init();
	}

  /**
   * Connect to the allowed iron.io services.
   * @throws CException If the service is not known this exception is thrown
   */
  public function connect()
  {
		foreach ($this->services AS $service)
		{
      if($service == 'mq')
      {
        if($this->_mq == null){
          try{
            $this->_mq = new IronMQ(array(
              'token' => $this->token,
              'project_id' => $this->projectId,
            ));
            Yii::log('Iron MQ in project '. $this->projectId.' is connected!', 'info', 'ext.yiiron');
          }
          catch(Exception $e){
            Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
            throw new  CException('Error in IronMQ: '. $e->getMessage());
          }
        }
			}
			else if($service == 'worker')
			{
        if($this->_worker == null){
          try{
            $this->_worker = new IronWorker(array(
              'token' => $this->token,
              'project_id' => $this->projectId
            ));
            Yii::log('Iron Worker in project '. $this->projectId.' is connected!', 'info', 'ext.yiiron');
          }
          catch(Exception $e){
            Yii::log('Error in IronWorker: '. $e->getMessage(), 'error', 'ext.yiiron');
            throw new  CException('Error in IronWorker: '. $e->getMessage());
          }
        }
			}
      else if($service == 'cache')
      {
        if($this->_cache == null){
          try{
            $this->_cache = new IronCache(array(
              'token' => $this->token,
              'project_id' => $this->projectId
            ));
            Yii::log('Iron Cache in project'. $this->projectId.' is connected!', 'info', 'ext.yiiron');
          }
          catch(Exception $e){
            Yii::log('Error in IronCache: '. $e->getMessage(), 'error', 'ext.yiiron');
            throw new  CException('Error in IronCache: '. $e->getMessage());
          }
        }
      }
			else
			{
        Yii::log('Service '.$service.' is not available', 'error', 'ext.yiiron');
        throw new CException('Service '.$service.' is not available');
			}
		}
	}

  /**
   * This function shall only be used to make sure that the connection works initially and that the credentials are OK.
   * It lists the queues to test the connection.
   * @return bool
   */
  public function testConnection()
  {
    //Create a connection to the iron mq service if it doesn't exist so we can check for if
    //a connection can be made.
    if($this->_mq == null)
    {
      $this->_mq = new IronMQ(array(
        'token' => $this->token,
        'project_id' => $this->projectId,
      ));
    }

    $conTestResult = $this->mqGetQueues();
    if(is_array($conTestResult))
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Disconnects all services previously connected.
   */
  public function disconnect()
  {
    $this->_mq = null;
    $this->_worker = null;
    $this->_cache = null;
  }

	/**
	 * Returns the active Iron Worker object.
   * @return IronWorker This is a reference to IronWorker object
	 */
	public function getRawWorker()
	{
		return $this->_worker;
	}

  /**
   * Returns the active Iron Cache object.
   * @return IronCache This is a reference to the IronCache object
   */
  public function getRawCache()
  {
    return $this->_cache;
  }

    /**
     * Returns the active IronMQ
     * @return IronMQ This is a reference to IronMQ object
     * @see I
     */
    public function getRawMq()
    {
      return $this->_mq;
    }

  /********** All the improved worker functionality **************/

  /**
   * This method can execute any command line action as an IronWorker. The command line actions are normally
   * executed directly in a shell. This method has been build so that we can fire off the command line actions
   * directly from any Yii code. This way we don't have to fork off a process just to push an action to IronWorkers.
   *
   * Here is an example:
   * This is how we can run a command from the command line to push it to IronWorkers
   * ./yiic cronjobs myAction --param1=34 --ironWorker=true
   *
   * In order to run this action directly from for instance a controller you can do this:
   * $yiiron = Yii::app()->yiiron;
   * $yiiron->workerRunYiiAction('cronjobs', 'myAction', array('--param1=34', '--ironWorker=true'));
   *
   * If you leave out '--ironWorker=true' you can run the same command but locally not pushing it to IronWorkers.
   *
   * @note Remember that only none interactive command line actions can be run this way.
   *
   * @param null $command This is the command name. If the command class is CronjobsCommand this will be "cronjobs".
   * @param null $action This is the name of the command. If the command is called actionDownloadFile this will be "downloadFile"
   * @param array $options This is the array of parameters that can be sent in to the action.
   * It is an array of strings on this format array("--filePath=/tmp/my_file.txt", "--newFileName=my_new_file.txt")
   * @param boolean $silent Set this to true  to suppress any output coming from the command line action. This is only
   * valid for the code being run locally. When you check the log in the iron.io hub you will still see the trace.
   * @param string $entryScript This is normally the string "yiic". You will only have to set this if you are using a custom
   * entry script.
   * @return integer The IronWorker id
   */
  public function workerRunYiiAction($command=null, $action=null, $options=array(), $silent=true, $entryScript="yiic") {
    $commandPath = Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . 'commands';
    $runner = new CConsoleCommandRunner();
    $runner->addCommands($commandPath);

    $args = array($entryScript, $command, $action);

    //Add in the options
    foreach ($options AS $option)
    {
      $args[] = $option;
    }

    //Buffer the output to go silent not outputting text when using the commands in non CLI code
    ob_start();
    $res = $runner->run($args);
    //Discard the output if silent
    if($silent)
      ob_end_clean();
    else
      echo htmlentities(ob_get_clean(), null, Yii::app()->charset);

    return $res;
  }




  /********** All the MQ wrappers **************/

  /**
   * Get list of message queues
   *
   * @param int $page
   *        Zero-indexed page to view
   * @param int $per_page
   *        Number of queues per page
   * @throws CException
   * @return mixed
   */
  public function mqGetQueues($page = 0, $per_page = IronMQ::LIST_QUEUES_PER_PAGE) {
    try{
      return $this->_mq->getQueues($page, $per_page);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get information about queue.
   * Also returns queue size.
   *
   * @param string $queue_name
   * @return mixed
   * @throws CException
   */
  public function getQueue($queue_name) {
    try{
      return $this->_mq->getQueue($queue_name);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Clear all messages from queue.
   *
   * @param string $queue_name
   * @return mixed
   * @throws CException
   */
  public function mqClearQueue($queue_name) {
    try{
      return $this->_mq->clearQueue($queue_name);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Push a message on the queue
   *
   * Examples:
   * <code>
   * $ironmq->postMessage("test_queue", "Hello world");
   * </code>
   * <code>
   * $ironmq->postMessage("test_queue", "Test Message",e array(
   *   'timeout' => 120,
   *   'delay' => 2,
   *   'expires_in' => 2*24*3600 # 2 days
   * ));
   * </code>
   *
   * @param string $queue_name Name of the queue.
   * @param string $message
   * @param array $properties
   * @return mixed
   * @throws CException
   */
  public function mqPostMessage($queue_name, $message, $properties = array()) {
    try{
      return $this->_mq->PostMessage($queue_name, $message, $properties);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }

  }

  /**
   * Push multiple messages on the queue
   *
   * Example:
   * <code>
   * $ironmq->postMessages("test_queue", array("Lorem", "Ipsum"), array(
   *   'timeout' => 120,
   *   'delay' => 2,
   *   'expires_in' => 2*24*3600 # 2 days
   * ));
   * </code>
   *
   * @param string $queue_name Name of the queue.
   * @param array $messages array of messages, each message same as for postMessage() method
   * @param array $properties array of message properties, applied to each message in $messages
   * @return mixed
   * @throws CException
   */
  public function mqPostMessages($queue_name, $messages, $properties = array()) {
    try{
      return $this->_mq->postMessages($queue_name, $messages, $properties);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get multiple messages from queue
   *
   * @param string $queue_name Queue name
   * @param int $count
   * @param int $timeout
   * @return array|null array of messages or null
   * @throws CException
   */
  public function mqGetMessages($queue_name, $count = 1, $timeout = IronMQ::GET_MESSAGE_TIMEOUT) {
    try{
      return $this->_mq->getMessages($queue_name, $count, $timeout);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get single message from queue
   *
   * @param string $queue_name Queue name
   * @param int $timeout
   * @return mixed|null single message or null
   * @throws CException
   */
  public function mqGetMessage($queue_name, $timeout = IronMQ::GET_MESSAGE_TIMEOUT) {
    try{
      return $this->_mq->GetMessage($queue_name, $timeout);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }

  }


  /**
   * Delete a Message from a Queue
   * This call will delete the message. Be sure you call this after you’re done with a message or it will be placed back on the queue.
   *
   * @param $queue_name
   * @param $message_id
   * @return mixed
   * @throws CException
   */
  public function mqDeleteMessage($queue_name, $message_id) {
    try{
      return $this->_mq->deleteMessage($queue_name, $message_id);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Peek Messages on a Queue
   * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
   *
   * @param string $queue_name
   * @return object|null  message or null if queue is empty
   * @throws CException
   */
  public function mqPeekMessage($queue_name) {
    try{
      return $this->_mq->peekMessage($queue_name);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }

  }

  /**
   * Peek Messages on a Queue
   * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
   *
   * @param string $queue_name
   * @param int $count The maximum number of messages to peek. Maximum is 100.
   * @return array|null array of messages or null if queue is empty
   * @throws CException
   */
  public function mqPeekMessages($queue_name, $count) {
    try{
      return $this->_mq->peekMessages($queue_name, $count);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Touch a Message on a Queue
   * Touching a reserved message extends its timeout by the duration specified when the message was created, which is 60 seconds by default.
   *
   * @param string $queue_name
   * @param string $message_id
   * @return mixed
   * @throws CException
   */
  public function mqTouchMessage($queue_name, $message_id) {
    try{
      return $this->_mq->touchMessage($queue_name, $message_id);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Release a Message on a Queue
   * Releasing a reserved message unreserves the message and puts it back on the queue as if the message had timed out.
   *
   * @param string $queue_name
   * @param string $message_id
   * @param int $delay The item will not be available on the queue until this many seconds have passed. Default is 0 seconds. Maximum is 604,800 seconds (7 days).
   * @return mixed
   * @throws CException
   */
  public function mqReleaseMessage($queue_name, $message_id, $delay = 0) {
    try{
      return $this->_mq->releaseMessage($queue_name, $message_id, $delay);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Updates the queue object
   *
   * @param string $queue_name
   * @param array $options Parameters to change. keys:
   * - "subscribers" url's to subscribe to
   * - "push_type" multicast (default) or unicast.
   * - "retries" Number of retries. 3 by default
   * - "retries_delay" Delay between retries. 60 (seconds) by default
   * @throws CException
   */
  public function mqUpdateQueue($queue_name, $options) {
    try{
      return $this->_mq->updateQueue($queue_name, $options);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function mqDeleteQueue($queue_name) {
    try{
      return $this->_mq->deleteQueue($queue_name);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Add Subscriber to a Queue
   *
   * Example:
   * <code>
   * $ironmq->addSubscriber("test_queue", array("url" => "http://example.com"));
   * </code>
   *
   * @param string $queue_name
   * @param array $subscriber_hash Subscriber. keys:
   * - "url" Subscriber url
   * @return mixed
   * @throws CException
   */
  public function mqAddSubscriber($queue_name, $subscriber_hash) {
    try{
      return $this->_mq->addSubscriber($queue_name, $subscriber_hash);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Remove Subscriber from a Queue
   *
   * Example:
   * <code>
   * $ironmq->removeSubscriber("test_queue", array("url" => "http://example.com"));
   * </code>
   *
   * @param string $queue_name
   * @param array $subscriber_hash Subscriber. keys:
   * - "url" Subscriber url
   * @return mixed
   * @throws CException
   */
  public function mqRemoveSubscriber($queue_name, $subscriber_hash) {
    try{
      return $this->_mq->removeSubscriber($queue_name, $subscriber_hash);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }


  /**
   * Get Message's Push Statuses (for Push Queues only)
   *
   * Example:
   * <code>
   * statuses = $ironmq->getMessagePushStatuses("test_queue", $message_id)
   * </code>
   *
   * @param string $queue_name
   * @param string $message_id
   * @throws CException
   * @return array
   */
  public function mqGetMessagePushStatuses($queue_name, $message_id) {
    try{
      return $this->_mq->GetMessagePushStatuses($queue_name, $message_id);
    }
    catch(Exception $e){
      Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

    /**
     * Delete Message's Push Status (for Push Queues only)
     *
     * Example:
     * <code>
     * $ironmq->deleteMessagePushStatus("test_queue", $message_id, $subscription_id)
     * </code>
     *
     * @param string $queue_name
     * @param string $message_id
     * @param string $subscription_id
     * @throws CException
     * @return mixed
     */
public function mqDeleteMessagePushStatus($queue_name, $message_id, $subscription_id) {
  try{
    return $this->_mq->deleteMessagePushStatus($queue_name, $message_id, $subscription_id);
  }
  catch(Exception $e){
    Yii::log('Error in IronMQ: '. $e->getMessage(), 'error', 'ext.yiiron');
    throw new  CException($e->getMessage());
  }
}



/********** All the Worker wrappers **************/

  /**
   * Zips and uploads your code
   *
   * Shortcut for zipDirectory() + postCode()
   *
   * @param string $directory Directory with worker files
   * @param string $run_filename This file will be launched as worker
   * @param string $code_name Referenceable (unique) name for your worker
   * @param array $options Optional parameters:
   *  - "max_concurrency" The maximum number of tasks that should be run in parallel.
   *  - "retries" The number of auto-retries of failed task.
   *  - "retries_delay" Delay in seconds between retries.
   * @return bool Result of operation
   * @throws CException
   */
public function workerUpload($directory, $run_filename, $code_name, $options = array()){
  try {
    return $this->_worker->upload($directory, $run_filename, $code_name, $options);
  }
  catch (Exception $e) {
    Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
    throw new  CException($e->getMessage());
  }
}

  public function workerGetProjects(){
    try {
      return $this->_worker->getProjects();
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * List Tasks
   *
   * @param int $page Page. Default is 0, maximum is 100.
   * @param int $per_page The number of tasks to return per page. Default is 30, maximum is 100.
   * @param array $options Optional URL Parameters
   * Filter by Status: the parameters queued, running, complete, error, cancelled, killed, and timeout will all filter by their respective status when given a value of 1. These parameters can be mixed and matched to return tasks that fall into any of the status filters. If no filters are provided, tasks will be displayed across all statuses.
   * - "from_time" Limit the retrieved tasks to only those that were created after the time specified in the value. Time should be formatted as the number of seconds since the Unix epoch.
   * - "to_time" Limit the retrieved tasks to only those that were created before the time specified in the value. Time should be formatted as the number of seconds since the Unix epoch.
   * @return mixed
   * @throws CException
   */
  public function workerGetTasks($page = 0, $per_page = 30, $options = array()){
    try {
      return $this->_worker->getTasks($page, $per_page, $options);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerGetProjectDetails(){
    try {
      return $this->_worker->getProjectDetails();
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerGetCodes($page = 0, $per_page = 30){
    try {
      return $this->_worker->getCodes($page, $per_page);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerGetCodeDetails($code_id){
    try {
      return $this->_worker->getCodeDetails($code_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Uploads your code package
   *
   * @param string $filename This file will be launched as worker
   * @param string $zipFilename zip file containing code to execute
   * @param string $name referenceable (unique) name for your worker
   * @param array $options Optional parameters:
   *  - "max_concurrency" The maximum number of tasks that should be run in parallel.
   *  - "retries" The number of auto-retries of failed task.
   *  - "retries_delay" Delay in seconds between retries.
   * @return mixed
   * @throws CException
   */
  public function workerPostCode($filename, $zipFilename, $name, $options = array()){
    try {
      return $this->_worker->postCode($filename, $zipFilename, $name, $options);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerDeleteCode($code_id){
    try {
      return $this->_worker->deleteCode($code_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerDeleteSchedule($schedule_id){
    try {
      return $this->_worker->deleteSchedule($schedule_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get information about all schedules for project
   *
   * @param int $page
   * @param int $per_page
   * @return mixed
   * @throws CException
   */
  public function workerGetSchedules($page = 0, $per_page = 30){
    try {
      return $this->_worker->getSchedules($page, $per_page);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get information about schedule
   *
   * @param string $schedule_id Schedule ID
   * @return mixed
   * @throws CException
   */
  public function workerGetSchedule($schedule_id){
    try {
      return $this->_worker->getSchedule($schedule_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Schedules task
   *
   * @param string $name Package name
   * @param array $payload Payload for task
   * @param int $delay Delay in seconds
   * @return string Created Schedule id
   * @throws CException
   */
  public function workerPostScheduleSimple($name, $payload = array(), $delay = 1){
    try {
      return $this->_worker->postScheduleSimple($name, $payload, $delay);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Schedules task
   *
   * @param string        $name       Package name
   * @param array         $payload    Payload for task
   * @param int|DateTime  $start_at   Time of first run in unix timestamp format or as DateTime instance. Example: time()+2*60
   * @param int           $run_every  Time in seconds between runs. If omitted, task will only run once.
   * @param int|DateTime  $end_at     Time tasks will stop being enqueued in unix timestamp or as DateTime instance format.
   * @param int           $run_times  Number of times to run task.
   * @param int           $priority   Priority queue to run the job in (0, 1, 2). p0 is default.
   * @return string Created Schedule id
   * @throws CException
   */
  public function workerPostScheduleAdvanced($name, $payload = array(), $start_at, $run_every = null, $end_at = null, $run_times = null, $priority = null){
    try {
      return $this->_worker->postScheduleAdvanced($name, $payload, $start_at, $run_every, $end_at, $run_times, $priority);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Queues already uploaded worker
   *
   * @param string $name Package name
   * @param array $payload Payload for task
   * @param array $options Optional parameters:
   *  - "priority" priority queue to run the job in (0, 1, 2). 0 is default.
   *  - "timeout" maximum runtime of your task in seconds. Maximum time is 3600 seconds (60 minutes). Default is 3600 seconds (60 minutes).
   *  - "delay" delay before actually queueing the task in seconds. Default is 0 seconds.
   * @return string Created Task ID
   * @throws CException
   */
  public function workerPostTask($name, $payload = array(), $options = array()){
    try {
      return $this->_worker->postTask($name, $payload, $options);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function getLog($task_id){
    try {
      return $this->_worker->getLog($task_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerGetTaskDetails($task_id){
    try {
      return $this->_worker->getTaskDetails($task_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerCancelTask($task_id){
    try {
      return $this->_worker->cancelTask($task_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  public function workerDeleteTask($task_id){
    try {
      return $this->_worker->deleteTask($task_id);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Wait while the task specified by task_id executes
   *
   * @param string $task_id Task ID
   * @param int $sleep Delay between API invocations in seconds
   * @param int $max_wait_time Maximum waiting time in seconds, 0 for infinity
   * @return mixed $details Task details or false
   * @throws CException
   */
  public function workerWaitFor($task_id, $sleep = 5, $max_wait_time = 0){
    try {
      return $this->_worker->waitFor($task_id, $sleep, $max_wait_time);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Schedule a task
   *
   * @param string $name
   * @param array $options options contain:
   *   start_at OR delay — required - start_at is time of first run. Delay is number of seconds to wait before starting.
   *   run_every         — optional - Time in seconds between runs. If omitted, task will only run once.
   *   end_at            — optional - Time tasks will stop being enqueued. (Should be a Time or DateTime object.)
   *   run_times         — optional - Number of times to run task. For example, if run_times: is 5, the task will run 5 times.
   *   priority          — optional - Priority queue to run the job in (0, 1, 2). p0 is default. Run at higher priorities to reduce time jobs may spend in the queue once they come off schedule. Same as priority when queuing up a task.
   * @param array $payload
   * @return mixed
   * @throws CException
   */
  public function workerPostSchedule($name, $options, $payload = array()){
    try {
      return $this->_worker->postSchedule($name, $options, $payload);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Set a Task’s Progress
   *
   * Example (inside a worker):
   * <code>
   * require_once "phar://iron_worker.phar";
   * $worker = new IronWorker(); # assuming you have iron.json inside a worker
   * $args = getArgs();
   * $task_id = $args['task_id'];
   * $worker->setProgress($task_id, 50, "Task is half-done");
   * </code>
   *
   * @param string $task_id Task ID
   * @param int $percent An integer, between 0 and 100 inclusive, that describes the completion of the task.
   * @param string $msg Any message or data describing the completion of the task. Must be a string value, and the 64KB request limit applies.
   * @return mixed
   * @throws CException
   */
  public function workerSetProgress($task_id, $percent, $msg = ''){
    try {
      return $this->_worker->setProgress($task_id, $percent, $msg);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Alias for setProgress()
   *
   * @param string $task_id Task ID
   * @param int $percent
   * @param string $msg
   * @return mixed
   * @throws CException
   */
  public function workerSetTaskProgress($task_id, $percent, $msg = ''){
    try {
      return $this->_worker->setTaskProgress($task_id, $percent, $msg);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Set a Task’s Progress. Work only inside a worker
   *
   * Example (inside a worker):
   * <code>
   * require_once "phar://iron_worker.phar";
   * $worker = new IronWorker(); # assuming you have iron.json inside a worker
   * $worker->setCurrentTaskProgress(50, "Task is half-done");
   * </code>
   * @param int $percent An integer, between 0 and 100 inclusive, that describes the completion of the task.
   * @param string $msg Any message or data describing the completion of the task. Must be a string value, and the 64KB request limit applies.
   * @return mixed
   * @throws CException
   */
  public function workerSetCurrentTaskProgress($percent, $msg = ''){
    try {
      return $this->_worker->setCurrentTaskProgress($percent, $msg);
    }
    catch (Exception $e) {
      Yii::log('Error in IronWorker: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /********** All the Cache wrappers **************/
  /**
   * Set default cache name. Use this together with shortcut methods.
   *
   * @param string $cache_name name of cache
   * @throws CException
   */
  public function cacheSetCacheName($cache_name) {
    try {
      $this->_cache->setCacheName($cache_name);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get a list of the cache buckets back.
   * @param int $page
   * @return mixed
   * @throws CException
   */
  public function cacheGetCaches($page = 0){
    try {
      return $this->_cache->getCaches($page);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Get information about cache.
   * Also returns cache size.
   *
   * @param string $cache
   * @return mixed
   * @throws CException
   */
  public function cacheGetCache($cache) {
    try {
      return $this->_cache->GetCache($cache);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

    /**
     * Push a item on the cache at 'key'
     *
     * Examples:
     * <code>
     * Yii::app()->yiiron->cachePutItem("test_cache", 'default', "Hello world");
     * </code>
     * <code>
     * $cache->putItem("test_cache", 'default', array(
     *   "value" => "Test Item",
     *   'expires_in' => 2*24*3600, # 2 days
     *   "replace" => true
     * ));
     * </code>
     *
     * @param string $cache Name of the cache.
     * @param string $key Item key.
     * @param array|string $item
     * @throws CException
     * @return mixed
     */
    public function cachePutItem($cache, $key, $item) {
      try {
        return $this->_cache->putItem($cache, $key, $item);
      }
      catch (Exception $e) {
        Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
        throw new  CException($e->getMessage());
      }
    }

  /**
   * Get item from cache by key
   *
   * @param string $cache Cache name
   * @param string $key Cache key
   * @return mixed|null single item or null
   * @throws CException
   */
  public function cacheGetItem($cache, $key) {
    try {
      return $this->_cache->getItem($cache, $key);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Remove a cached item from cache.
   * @param $cache
   * @param $key
   * @return mixed
   * @throws CException
   */
  public function cacheDeleteItem($cache, $key) {
    try {
      return $this->_cache->deleteItem($cache, $key);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }


  /**
   * Atomically increments the value for key by amount.
   * Can be used for both increment and decrement by passing a negative value.
   * The value must exist and must be an integer.
   * The number is treated as an unsigned 64-bit integer.
   * The usual overflow rules apply when adding, but subtracting from 0 always yields 0.
   *
   * @param string $cache
   * @param string $key
   * @param int $amount Change by this value
   * @return mixed|void
   * @throws CException
   */
  public function cacheIncrementItem($cache, $key, $amount = 1){
    try {
      return $this->_cache->incrementItem($cache, $key, $amount);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Shortcut for getItem($cache, $key)
   * Please set $cache name before use by Yii::app()->yiiron->cacheSetCacheName() method
   *
   * @param string $key
   * @return mixed|null
   * @throws CException
   */
  public function cacheGet($key){
    try {
      return $this->_cache->get($key);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Shortcut for putItem($cache, $key, $item)
   * Please set $cache name before use by Yii::app()->yiiron->cache->setCacheName() method
   *
   * @param string $key
   * @param array|string $item
   * @return mixed
   * @throws CException
   */
  public function cachePut($key, $item){
    try {
      return $this->_cache->put($key, $item);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }

  /**
   * Shortcut for deleteItem($cache, $key)
   * Please set $cache name before use by Yii::app()->yiiron->cacheSetCacheName() method
   *
   * @param string $key
   * @return mixed|void
   * @throws CException
   */
  public function cacheDelete($key){
    try {
      return $this->_cache->delete($key);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }
    /**
     * Shortcut for incrementItem($cache, $key, $amount)
     * Please set $cache name before use by Yii::app()->yiiron->cacheSetCacheName() method
     *
     * @param string $key
     * @param int $amount
     * @return mixed|void
     * @throws CException
     */
    public function cacheIncrement($key, $amount = 1){
      try {
        return $this->_cache->increment($key, $amount);
      }
      catch (Exception $e) {
        Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
        throw new  CException($e->getMessage());
      }
    }

  /**
   * Clear a Cache
   * Delete all items in a cache bucket. This cannot be undone.
   *
   * @param string|null $cache Cache name or null
   * @return mixed
   * @throws CException
   */
  public function cacheClear($cache = null) {
    try {
      return $this->_cache->clear($cache);
    }
    catch (Exception $e) {
      Yii::log('Error in Iron Cache: ' . $e->getMessage(), 'error', 'ext.yiiron');
      throw new  CException($e->getMessage());
    }
  }
}