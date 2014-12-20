# Yii + iron.io = Yiiron

## What is Yiiron?
Yiiron is a Yii extension that integrates the services of [iron.io](http://iron.io?rc=je1 ) in the [Yii Famework](http://www.yiiframework.com). 

[Iron.io](http://iron.io?rc=je1 ) offers three services:

- **IronMQ** - A message Queue for the Cloud (10 Million free API Requests / month Unlimited Queues)
- **IronWorkers** - A worker platform that runs tasks in the background, in parallel, and at massive scale (200 Free Hours per month, that is over 6 hours per day, 50 concurrent Tasks, 25 Scheduled Jobs)
- **IronCache**  - Key/Value Data Cache (offers: 100 MB storage and 10 Million API Requests /month)

## Why should you use Yiiron?
If you ask your self any of these questions you should probably try it:

- How can I easily integrate an MQ service in my Yii application?
- Hmm, these cronjobs are really draining my server. Should I spin up another server just to run the cronjobs?
- My app is starting to gain a lot of traction. I really need to start caching properly but I don’t want to install and manage [Memcached](http://memcached.org/) or a similar service. I wonder if someone can manage the cache for me?
- I really like the iron.io services but I don’t have time to develop a Yii component for it.
- My site is growing quickly and the background tasks I run can no longer keep up with being run in one thread. Is there a way of running my workers in parallel without installing and maintaining something like [gearman.org](http://gearman.org)
- I am on [AWS](http://aws.amazon.com/) and I want to use an MQ, workers or cache but I want someone else to manage scaling and management of those services.

## Requirements
- Your application needs to be  hosted on AWS. This is not actually a requirement but all the [iron.io](http://iron.io?rc=je1) services are hosted on AWS and to get the full speed of the services and as little latency as possible your applications should be hosted on the AWS infrastructure. The services are based on REST so in theory you can use them from any computer that is connected to the internet and for instance adding data to IronMQ usually works fine with low latency in a cross cloud environment.

## Resources
- GitHub page(for forks and trouble reports): [https://github.com/br0sk/yiiron](https://github.com/br0sk/yiiron)  
- Packagist page(for Composer download): [https://packagist.org/packages/br0sk/yiiron](https://packagist.org/packages/br0sk/yiiron)  
- iron.io dev center(for full details on the services including full API documentation) - [http://dev.iron.io/](http://dev.iron.io/)


## Getting Started
If you don’t already have an [iron.io](http://iron.io?rc=je1) account please sign up for a free account [here](http://www.iron.io?rc=je1).

Go to the [hud.iron.io/dashboard](https://hud.iron.io/dashboard) and create a new project.

When the project is created click the key icon and take a note of the token and the project id.

Unzip `yiiron.zip` file that you downloaded from the [Yiiron Yii extension page](http://www.yiiframework.com/extension/yiiron) and put all the files in the extensions directory. 
It would look something like this: `/var/www/myapp/protected/extensions/yiiron`  

**Note:** You can also install the the extension using Composer. It uses the new [Yii Composer installer](https://github.com/composer/installers/pull/61). 
Remember to set the composer flag for the extension to false if you used the zip file and set it to true if you have installed using composer. This is needed since the iron.io classes will be found in different locations based on how it was installed.

Add this to your config file (don't forget to add it to the console.php if you want to use the IronWorkers)
	
	import'=>array('ext.yiiron.*',)

Add this to the component section...

    'yiiron'=> array(
    	'class' =>'EYiiron',
    	'token'  => ‘your_iron_io_token’,
    	'projectId'  => 'your_iron_io_project_id',
    	'services'   => array('mq','worker',’cache’),
		'composer'	=> false,
    	'workerFileCopyOptions' => array('exclude' => array('.git','.csv','.svn', '.zip', "/runtime", "/config")),
		'configFile' => 'config/console_ironworker.php',
		'stack' => 'php-5.5'
     	),
    
This should be all!

As you can see you can set the PHP version you want to use when the code is executed as an IronWorker. Find the valid versions [here](http://dev.iron.io/worker/reference/environment/#default_language_versions). 

Now test it by adding this to one of the actions in a controller:

    Yii::app()->yiiron->mqPostMessage("yii_demo", "First Value");

Load the action in a browser.

Now go to the back to [hud.iron.io/dashboard](https://hud.iron.io/dashboard) and click the MQ button next to the project you created. If everything is fine you should see a “Queues” tab. Click it!

You should now see your freshly created queue with one message added.

Congrats everything is working and you can start to use the iron.io services!

## How to use the services
You can now call the different services since we have verified the connection is OK in “Getting started” above.

The iron.io PHP API was used to build Yiiron. All the public methods of the API's [github.com/iron-io/iron\\_mq\\_php](https://github.com/iron-io/iron_mq_php), [github.com/iron-io/iron\\_worker\\_php](https://github.com/iron-io/iron_worker_php), [github.com/iron-io/iron\\_cache\\_php](https://github.com/iron-io/iron_cache_php) have been wrapped in the Yiiron component.

To make use of the auto complete function in most editors I suggest that you initialise the Yiiron component once and save it in a local variable like this:

    /**
     * @var $yiiron EYiiron The iron.io connector
     */
     $yiiron = Yii::app()->yiiron;
    
Doing so will give you access to auto complete for the variable `$yiiron`. This makes it very easy to find the wrapped API methods.

I have prefixed the wrapper methods with **mq**, **worker** and **cache**. 

So to get all the methods for IronMQ just type:
	
	$yiiron->mq

and auto complete should bring up all the methods starting with mq.

For the IronWorkers type:

	$yiiron->worker

and auto complete should bring up all the methods starting with worker.

For the IronCache:

	$yiiron->cache

and auto complete should bring up all the methods starting with cache.

**tip:** If you don't want to use my wrapper functions you can get a reference to each of the services directly like this.

	$myWorker = Yii::app()->yiiron->getRawWorker();
	$myCache = Yii::app()->yiiron->getRawCache();
	$myMq = Yii::app()->yiiron->getRawMq();	

## How to use IronMQ
To use IronMQ you can simply call all the methods starting with mq. Here is an example of the most common scenario for an generic MQ (put/get/delete).
    
	/**
     * @var $yiiron EYiiron The iron.io connector
     */
    $yiiron = Yii::app()->yiiron;
      
    //Adds a message to the queue
    $yiiron->mqPostMessage("yii_demo", "First Value");
    
    //Get the message from the queue and reserve it for 10 seconds(default is 60 seconds)
    $message = $yiiron->mqGetMessage("yii_demo",10);
    
	//Print the body and id of the message to the screen
    echo("Message id=".$message->id." message body=". $message->body);
    
    //The message has been consumed now remove it from the queue so it is not put back on the queue when the time out of 10 seconds have passed
    $yiiron->mqDeleteMessage("yii_demo", $message_get_after_release->id);
    
For more examples check the unit test classes.

## How to use IronWorkers
You can use the IronWorkers in two ways. The first way is to simply use the wrapped API directly. You probably want to do that if you are writing your own integration on top of Yiiron or you have a very specific use case.

The second and preferred way of using IronWorkers is to run the worker as a Yii command line application ([Yii CLI documentation](http://www.yiiframework.com/doc/guide/1.1/en/topics.console)). By extending the class  **EIronWorkersCommand** instead of  **CConsoleCommand** directly you will now get access to extra functionality that will allow you to run your command line actions directly as an IronWorker without making any changes to the the Yii API. 

This basically means that you can run your currently existing command actions as IronWorkers only by changing the class that your command extends.

### Here is a practical example step by step.

Create a separate config file called **console\_ironworker.php** and place it in the config folder of your application.
Here is a simple version of this file.
 
    <?php
    // This is the configuration for yiic console application.
    // Any writable CConsoleApplication properties can be configured here.
    return array(
    	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
      	'name'=>'Iron workers',
    
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
    

In a more advanced appplication you would probably want to add more stuff to that config file or even merge in the `console.php` file. Be careful because the IronWorker runtime environment might not support everything that your production server supports, for more details have a look [here](http://dev.iron.io/worker/languages/php/#environment?rc=je1) and [here]( http://dev.iron.io/worker/reference/environment/?rc=je1) for more information on the runtime environment.

**Tip:** You can since version 1.0.4 use the parameter `configFile` to set the path of the config file to be used by Yiiron. It always defaults to `config/console_ironworker.php`

Now create a command line application by creating a file called **CronjobsCommand.php**(this is just an example it works fine with any command file name that Yii allows) and save it in the command folder in Yii, it will look something like this `/var/www/myapp/protected/commands/CronjobsCommand.php`.

Add this code to the file:

    class CronjobsCommand extends EIronWorkersCommand
    {
    public function actionMyAction()
      	{
    		echo("My Action Finished!\n");
      	}
    }

We have now created a very simple action that outputs some text to the screen. 

Run it locally like this:

- Go to the base folder of your application, something like `/var/www/myapp/protected`. 
- Now run `./yiic cronjobs myAction`. This should print “My Action Finished!” to the screen.  

Now the magic starts.
 
- Run the command `./yiic cronjobs uploadIronWorker`. This will output some trace to the screen and it will take a while to run especially if you are on a slow connection. This command packages both your application and the Yii framework in a zip file an publishes it to IronWorkers.   
**Note:** As of version 1.0.4 the config file is no longer part of the zip package but uses the config parameter in the upload function. This means that the credentials you store in the your config file will no longer be available in the zip file that is being uploaded to IronWorkers. This is a more secure way of handling your credentials in the config file.    
When it is finished you should be able to run the myTask command remotely as an IronWorker like this `./yiic cronjobs myAction --ironWorker=true`. 
- Now go to [hud.iron.io/dashboard](https://hud.iron.io/dashboard). 
- Click the IronWorker button next to the project we created earlier. The button has a cog on it. 
- Go to the tasks tab. You will see a new task called cronjobs. 
- Click that task. This should give you a list of the tasks that have been executed. You should have one task with a round green icon next to it. If it failed you can check the log and try to figure out what went wrong and run the command `./yiic cronjobs myAction --ironWorker=true` again.

**Note:** If you make any changes to any code you need to upload the code again using the command `./yiic cronjobs uploadIronWorker`.

**tip:** Since we can run the deployment script from the command line it is perfect to run from your deployment script/system. I use [Phing.info](http://www.phing.info/) for this purpose. In my `phing live` command I make sure to execute the command `./yiic cronjobs uploadIronWorker`. This way I know that my deployed code is always the same on my production server as in the IronWorkers environment. Remember that you have to do this for every command file you create. This is because IronWorkers demand that you upload a separate code package for every single task you create.

Great, now you have a remote server that runs your heavy cronjobs that used to bog your server down. You can now just add the flag `--ironWorker=true` at the end of the cronjob that runs the command and it will auto-magically be execute on IronWorkers. 

**tip:** You can also set priority, timeout, delay and task label like this if needed:

	./yiic cronjobs myAction --ironWorker=true --ironWorkerPriority=0 --ironWorkerTimeout=20 --ironWorkerDelay=30  --ironWorkerTaskLabel=MyLabel

Since `1.0.3` you can now run the command line actions directly in any code. You use the method called `workerRunYiiAction()` for this.

This method can execute any command line action as an IronWorker. The command line actions are normally executed directly in a shell. This method has been build so that we can fire off the command line actions directly from any Yii code. This way we don't have to fork off a process just to push an action to IronWorkers.

Here is an example:

This is how we can run a command from the command line to push it to IronWorkers
	
	./yiic cronjobs myAction --param1=34 --ironWorker=true
   
In order to run this action directly from for instance a controller you can do this:

	$yiiron = Yii::app()->yiiron;
   	$yiiron->workerRunYiiAction('cronjobs', 'myAction', array('--param1=34', '--ironWorker=true'));


**Tip:** If you leave out `'--ironWorker=true'` you can run the same command but locally not pushing it to IronWorkers.

**Note:** As of 1.0.4 the method `workerRunYiiAction` now returns the IronWorker  task id if it is run with the option `ironWorker=true`. This way you can use the id to cancel the task or check for progress. 

More documentation about the parameters can be found in the class `EIronWorkersCommand`.

## How to use IronCache
IronCache can also be used in several ways. Either free standing using the wrapped API like so:

	Yii::app()->yiiron->cachePutItem(‘cache_name’, ‘cache_value’, array(
        "value" => $value,
        'expires_in' => $expire
      ));
Or the preferred way of using the Yii cache component I have implemented.
To use that component add this to your config file in the the component section:

	'cache'=>array(
		'class'=>'EIronCache',
      	'yiironCacheName'=>'demo_cache’),

Of course you need the Yiiron setup we used for the other services as well.

When you have dropped the `EIronCache` class in like that you can use cache as you normally would in your application and it will use IronCache as the storage method. This is great if you don't feel like setting up your own MemCached server

## Yiiron Limitations

- Right now only AWS is supported in Yiiron. IronMQ exists on Rackspace and I will add support for that soon.
- Only tested on Linux
- No check for memory leaks has been done yet. I will add that to the unit test soon.
- Unit test doesn’t have full coverage yet
- You might not be able to use all php modules you normally use when running the code as an IronWorker. Have a look [here](http://dev.iron.io/worker/languages/php/#environment?rc=je1) for more information about the IronWorker runtime environment.


## Unit Test
Yiiron has a test suite. It can run just like any other test suite in Yii. I have used a [Composer](http://getcomposer.org/) based installation of [PHPUnit](https://github.com/sebastianbergmann/phpunit/). I had to make a change to how the iron.io classes are imported. The composer based installation seems to load the classes twice and fail if this is not done. If you have a PEAR based installation of PHPUnit you might need to remove the if statement at the top of class `if(Yii::app()->getComponent('fixture') === null)` to get it running the tests. I also had to change the base class for the test cases to work around another issue with installing PHPUnit via composer. Same goes here, if you are using the PEAR installed PHPUnit you might need to change back to the standard Yii test case class before running the tests.

