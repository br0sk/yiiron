<?php
/**
 * This class unit tests the the iron IO connector.
 */
class EIronCacheTest extends EComposerTestCase
{
  public $fixtures=array(	);

  /**
   * @var EIronCache This is the cache object to be used in the tests
   */
  public static $ironCache;

  /**
   * This runs one time after all test starts
   */
  public static function tearDownAfterClass()
  {
    self::$ironCache = null;
  }

  public static function setUpBeforeClass()
  {
    self::$ironCache = new EIronCache();
    self::$ironCache->yiironCacheName = "unit_test_cache";
    self::$ironCache->init();
  }

  /**
   * Testing getting a value from cache
   */
  public function testGet()
  {
    $setValueReturn = self::$ironCache->set('test_cache_1','value1',0);
    $this->assertTrue($setValueReturn);
    $theValue = self::$ironCache->get('test_cache_1');
    $this->assertEquals('value1', $theValue);
  }

  /**
   * Test situation where the value doesn't exist in cache
   */
  public function testGetFail()
  {
    $theValue = self::$ironCache->get('a_value_that_doesnt_exist');
    $this->assertFalse($theValue);
  }

  public function testDelete()
  {
    $setValueReturn = self::$ironCache->set('test_cache_1','value1',0);
    $this->assertTrue($setValueReturn);
    $deleteValue = self::$ironCache->delete('test_cache_1');
    $this->assertTrue($deleteValue);
  }

  public function testFlush()
  {
    $setValueReturn = self::$ironCache->set('test_cache_1','value1',0);
    $this->assertTrue($setValueReturn);
    $setValueReturn = self::$ironCache->set('test_cache_2','value2',0);
    $this->assertTrue($setValueReturn);
    $flushValue = self::$ironCache->flush();
    $this->assertTrue($flushValue);
  }
}
