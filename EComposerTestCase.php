<?php
/**
 * This file replaces the CTestCase class. CTestCase contains code that loads the PEAR
 * auto loader and that breaks the composer installation of PHPUnit.
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */

/**
 * EComposerTestCase is the base class for all test case classes.
 */
abstract class EComposerTestCase extends PHPUnit_Framework_TestCase
{
}


