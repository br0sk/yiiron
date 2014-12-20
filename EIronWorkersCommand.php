<?php
/**
 * This class can handle uploading and scheduling of IronWorkers.
 *
 * If you want to make use of the power of Iron workers you should let your
 *command class extend this class instead of CConsoleCommand.
 *
 * 1. Step 1 run the uploadIronWorker action in the extending class.
 * 2. Run any other action but add the parameter --ironWorker=true and it will run as an Iron Worker
 * 3. Enjoy not having a server being totally bogged down by the heavy cronjob :-)
 *
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */
class EIronWorkersCommand extends CConsoleCommand
{
  /**
   * @var array $yiicParams The array of args being sent in to the Yiic command. We need to pass them on to the
   * Iron worker.
   */
  public $yiicParams = array();

  /**
   * @var boolean Set to true if you are running the command as an Iron Worker
   */
  public $ironWorker = false;

  /**
   * @var int Priority queue to run the job in (0, 1, 2). 0 is default.
   * Can only be used when you run a command with --ironWorker=true
   */
  public $ironWorkerPriority = 0;

  /**
   * @var int Maximum runtime of your task in seconds. Maximum time is 3600 seconds (60 minutes). Default is 3600 seconds (60 minutes).
   * Can only be used when you run a command with --ironWorker=true
   */
  public $ironWorkerTimeout = 3600;

  /**
 * @var int Delay before actually queueing the task in seconds. Default is 0 seconds.
 * Can only be used when you run a command with --ironWorker=true
 */
  public $ironWorkerDelay = 0;
	
  /**
 * @var int A human readable string to classify this task.
 * Can only be used when you run a command with --ironWorker=true
 */
  public $ironWorkerTaskLabel = "";

  /**
   * Deciding if we are running the command locally or on Iron Workers
   * @see CConsoleCommand::run()
   * @param $args array
   * @return If run locally the exit code. If run as IronWorker the ironWorker id.
   */
  public function run($args)
  {
    //Store the parameters passed to the function, will be used to pass on to the iron workers
    $this->yiicParams = $args;
    //Add in the command name
    array_unshift($this->yiicParams, $this->getName());
    //Add in the entry script name
    array_unshift($this->yiicParams, "./".$this->getCommandRunner()->getScriptName());

    CVarDumper::dump($this->yiicParams, 100, false);
    if($this->isIronWorker())
    {
      $this->ironWorker = true;

      //Kick the command off to Iron Workers
      $resId = $this->runAsIronWorker();
      echo("Task ".$resId." pushed to Iron Worker!\n");
      //When run as an iron worker we return the IronWorker id
      return $resId;
    }
    //If we are running this as normal just run the parent implementation
    else
    {
      parent::run($args);
    }
  }

  /**
   * Check if this action should be run as an Iron Worker.
   * If so make sure to set the global command line parameters since they are not set yet because we
   * never actually entered an action.
   * @return bool true indicates that the task should be run as an Iron Worker
   */
  function isIronWorker()
  {
    //Since this is done before the action is resolved we need to take care of the
    //values by hand
    if(in_array("--ironWorker=true", $this->yiicParams))
    {
      foreach($this->yiicParams AS $yiicParam)
      {
        if(stristr($yiicParam, "--ironWorkerPriority"))
        {
          $parts = explode("=",$yiicParam);
          $this->ironWorkerPriority = intval($parts[1]);
        }
        if(stristr($yiicParam, "--ironWorkerTimeout"))
        {
          $parts = explode("=",$yiicParam);
          $this->ironWorkerTimeout = intval($parts[1]);
        }
        if(stristr($yiicParam, "--ironWorkerDelay"))
        {
          $parts = explode("=",$yiicParam);
          $this->ironWorkerDelay = intval($parts[1]);
        }
		if(stristr($yiicParam, "--ironWorkerTaskLabel"))
        {
          $parts = explode("=",$yiicParam);
          $this->ironWorkerTaskLabel = $parts[1];
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Run this action like this: yiic myAction uploadIronWorker
   *
   * This command can be integrated to your deployment system. When you have committed your code you can
   * run this command to deploy the code to the iron.io servers.
   *
   * This command zips all the code needed to be uploaded to the Iron Workers
   * Upload the zipped file to Iron Workers. This will create a new version of the code for the current project
   *
   * It will also create a task in the iron.io hud named after the command file. All the actions in this command file
   * will run  as this task on iron.io.
   *
   * It prepares all the files in the runtoime directory and cleans up when finished.
   * TODO: Test in Windows environment
   */
  public function actionUploadIronWorker()
  {
    /**
     * The EYiiron class instance. It is our gateway to all iron.io services
     * @var EYiiron $yiiron
     */
    $yiiron = Yii::app()->yiiron;

    //This is where we store the files before deploying them on iron.io
    $tmpDir = Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'ironworkers'.DIRECTORY_SEPARATOR;

	  echo("Using PHP Stack :". $yiiron->stack."\n");
    //Clean up in the directory. We do this before we start in case the old code was not fully removed.
    EIronWorkersCommand::deleteDir($tmpDir);

    //Make sure we have a clean environment before preparing the runtime environment for iron.io.
    //THis is crucial so we know that the code we deploy is exactly the same as we run locally.
    if (!file_exists($tmpDir)) {
      echo($tmpDir." doesn't exist creating it now...\n");
      if (!mkdir($tmpDir)) {
        echo("**Error**: Creation failed. Please check your permissions!\n");
        exit(0);
      }
    }
    else{
      echo("**Error**: ".$tmpDir." existed even though we tried to fully remove it.\n".
           "It could be a permission problem please remove ".$tmpDir." manually before running this command again.\n");
      exit(0);
    }

    echo("Copying Yii Framework to tmp dir...\n");

    /**
     * The Framework path
     * @var string $yiiPath
     */
    $yiiPath = Yii::getFrameworkPath()."/../";

    /**
     * The path of your Yii app. Usually the protected folder
     * @var string $appPath
     */
    $appPath = Yii::app()->getBasePath()."/";

    echo("Yii path: ". $yiiPath. "\n");

    CFileHelper::copyDirectory($yiiPath, $tmpDir.'yii');
    echo("Copying app from ".$appPath." to the tmp dir ".$tmpDir.'app/'.basename($appPath)."\n");

    //Exclude as much as we can to get a slim file to upload
    CFileHelper::copyDirectory($appPath, $tmpDir.'app/'.basename($appPath), Yii::app()->yiiron->workerFileCopyOptions);
    echo("Zipping code to ".$tmpDir."iron_worker.zip\n");
    IronWorker::zipDirectory($tmpDir, $tmpDir.'iron_worker.zip', true);
    echo("Uploading the code for the worker ".$this->name."...\n");

    //This is so we can handle custom extension paths
    $ironWorkerExtensionPath = str_replace($appPath,"app/".basename($appPath)."/", Yii::app()->getExtensionPath());

    //Read the config array into an array
    $configFile = json_encode(require($appPath.$yiiron->configFile));

    //Posting the code and the initial php file to execute. This on the Iron Worker platform, not locally
    $res = $yiiron->workerPostCode($ironWorkerExtensionPath."/yiiron/yiic-yiiron.php", $tmpDir.'iron_worker.zip', $this->getName(), array('config'=>$configFile, 'stack'=>$yiiron->stack));
    echo("Finished uploading iron_worker.zip (" . EIronWorkersCommand::format_bytes(filesize($tmpDir.'iron_worker.zip')) . ")\n");
	  
    //Remove all files
    echo("Remove all temp files...\n");
    //EIronWorkersCommand::deleteDir($tmpDir);

    echo("Done!\n");
    echo("Find the worker here http://hud.iron.io/tq/projects/".Yii::app()->yiiron->projectId."/tasks/".$res->id."/activity\n");
    echo("Now run your command like this: './yiic ".$this->name." myAction --ironWorker=true' to execute it as an Iron Worker.\n" );
  }

  /**
   * This method routes the command to the Iron Workers instead of running it locally.
   * @return integer The id returned by the Iron Worker
   */
  public function runAsIronWorker()
  {
    /**
     * @var EYiiron $yiiron
     */
    $yiiron = Yii::app()->yiiron;
    $payload = array(
      'yiicParams' => $this->yiicParams,
      'relativeAppPath' => basename(Yii::app()->getBasePath())
    );

    //Set the iron worker properties and launch it
    $res = $yiiron->workerPostTask($this->name, $payload, array('priority'=>$this->ironWorkerPriority, 'timeout'=>$this->ironWorkerTimeout, 'delay'=>$this->ironWorkerDelay, 'label'=>$this->ironWorkerTaskLabel));

    //Return the Iron Worker task id
    return $res;
  }

  /**
   * This function traverses the sent in directory and recursively removes every file and the folder.
   * @param string $dir This is the path to the folder we want to recursively remove
   * @return boolean true if successful false if not
   */
  public static function deleteDir($dir) {
    if(file_exists($dir)) {
      $it = new RecursiveDirectoryIterator($dir);
      $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
      foreach($files as $file) {
        if ($file->getFilename() === '.' || $file->getFilename() === '..') {
          continue;
        }
        if ($file->isDir()){
          rmdir($file->getRealPath());
        } else {
          unlink($file->getRealPath());
        }
      }
      rmdir($dir);
      //Check that everything was removed
      if(file_exists($dir))
        return false;
    }
    return true;
  }


  /**
   * Get the number of bytes in a good human readable format
   * @param integer $size in bytes
   * @return string
   */
  static public function format_bytes($size)
  {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2) . $units[$i];
  }
}

