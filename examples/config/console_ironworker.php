<?php
/**
 * This is the configuration for yiic IronWorker console application.
 *
 * To get started testing IronWorkers put this file in the config folder of your Yii application.
 * It configures your Yii application on the IronWorker target environment.
 *
 * This is a good starting point and I would suggest that you add the rest of your features one by one
 * to see that they run properly in the IronWorker environment.
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */


  return array(
  'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
  'name'=>'Yiiron IronWorkers App',

  //Autoloading model and component classes
  'import'=>array(
    'application.models.*',
    'application.components.*',
    'application.extensions.yiiron.*',
  ),
  // application components
  'components'=>array(
  ),

  // application-level parameters that can be accessed
  // using Yii::app()->params['paramName']
  'params'=>array(
    // this is used in contact page
    'adminEmail'=>'webmaster@example.com',
  ),
);