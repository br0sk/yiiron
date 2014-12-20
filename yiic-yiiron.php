<?php
//We need to set this in order for the code to execute in a PHP 5.5 environment 
date_default_timezone_set('UTC');
/**
 * This is the Yii bootstrap file for running code on IronWorkers
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */

//Get all the params
$params = getArgs();

//The path from the base folder to the folder of the app, usually called protected
$relativeAppPath = $params['payload']->relativeAppPath;

//For the Iron Worker we only need the Yiic params, if more data is needed just add it
//as parameters to your yiic command
$yiicParams = $params['payload']->yiicParams;

//This array will keep the data that you add to the config file that is configured in the
//Yiiron plugin
$configParamsArray = $params['config'];

//Update the app path to reflect the path on IronWorkers. This will overwrite anything
//that you added in the file locally in your system.
$configParamsArray['basePath'] = '/task/app/'.$relativeAppPath;

//Check that we are not trying to upload the iron worker when we run as an Iron worker
if($yiicParams[2] == 'uploadIronWorker')
{
  throw new  Exception("You cannot run command \"".$yiicParams[2]. "\" from an Iron Worker. You can only run that command locally!");
}
else
{
  echo("Command \"".$yiicParams[2]. "\" started!\n");
}

//Clean out the ironWorker flag if it is not done it will get stuck in an endless loop
foreach($yiicParams AS $i=>$param)
{
  //Remove all Iron Worker specific parameters, the Iron Worker is already posted now just do the work
  if(stristr($param,"--ironWorker") != false)
  {
    unset($yiicParams[$i]);
  }
}




//Now set the yiic parameters
$_SERVER['argv'] = $yiicParams;

//In the Iron Worker environment we can find the files in a folder called task
$taskPath = "/task/";
chdir($taskPath);

//Rig the initial framework folders so we don't get permission problems
mkdir('/task/app/'.$relativeAppPath.'/runtime');
chmod('/task/app/'.$relativeAppPath.'/runtime', 0777);

//The path where we uploaded Yii
$yiic=$taskPath.'yii/framework/yiic.php';

//Set the iron.io specific config array
$config=$configParamsArray;

//Bootstrap Yii
require_once($yiic);