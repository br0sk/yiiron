<?php
/**
 * This is class demonstrating simple usage of the Yiiron Iron Workers usage.
 *
 * 1. Make sure you have an account at iron.io and yiiron setup according to the installation instructions
 * 1. Put this file in the commands folder of your Yii web app
 * 2. In a shell go to the root of your project(normally the protected folder)
 * 3. Run "./yiic cronjobs uploadIronWorker" (this uploads your web app and the Yii framework as a zip file to iron.io)
 * 4. Run "./yiic cronjobs uploadIronWorker --ironWorker=true"  and it will will run as an Iron Worker
 * 5. Check that the command ran by going to https://hud.iron.io/
 * 3. Enjoy not having a server being totally bogged down by the heavy cronjob :-)
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 *
 */

class CronjobsCommand extends EIronWorkersCommand
{
  public function actionMyAction() {
    echo("My Action Finished!\n");
  }
}
