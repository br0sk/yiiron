<?php
/**
 * This class unit tests the iron IO connector.
 *
 *
 */
class IronIoConnectorTest extends EComposerTestCase
{
  public $fixtures=array(	);

  /**
   * @var string This is the project id set in the test.php file
   */
  public $initialProjectId;

  /**
   * @var string This is the token set in the test.php file
   */
  public $initialToken;

  /**
   * @var EYiiron
   */
  public static $iio;

  /**
   * This runs one time before all test starts
   */
  static public function setUpBeforeClass()
  {
    self::$iio = Yii::app()->yiiron;
  }

  /**
   * This runs one time after all test starts
   */
  public static function tearDownAfterClass()
  {
    self::$iio = null;
  }

  /**
   * This runs before every test method
   */
  public function setUp()
  {
    $this->initialProjectId = self::$iio->projectId;
    $this->initialToken = self::$iio->token;
  }

  /**
   * This runs after every test method
   */
  public function tearDown()
  {
    self::$iio->projectId = $this->initialProjectId;
    self::$iio->token = $this->initialToken;
  }

  /**
   * Testing successful connection based on the test.php config file
   */
  public function testConnect()
  {
    //Start connecting both mq and worker
    self::$iio->connect();
    $mq = self::$iio->getRawMq();
    $this->assertInstanceOf('IronMQ', $mq);

    $worker = self::$iio->getRawWorker();
    $this->assertInstanceOf('IronWorker', $worker);

    $cache = self::$iio->getRawCache();
    $this->assertInstanceOf('IronCache', $cache);
  }

  /**
   * Test what happens if we try to connect with an empty project id
   * @expectedException CException
   */
  public function testConnectEmptyProject()
  {
    //Disconnect all services
    self::$iio->disconnect();

    self::$iio->projectId = "";
    self::$iio->connect();
  }


  /**
   * Test what happens if we try to connect with a faulty project id
   * @expectedException CException
   */
  public function testConnectFaultyProject()
  {
    //Disconnect all services
    self::$iio->disconnect();

    self::$iio->projectId = "faulty_id";
    self::$iio->connect();
    $mq = self::$iio->mqGetQueues();

    $mq->getQueues();
  }

  /**
   * Test what happens if we try to connect with a faulty token
   * @expectedException CException
   */
  public function testConnectFaultyToken()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Try to connect with faulty project
    self::$iio->token = "faulty_token";
    self::$iio->connect();
    self::$iio->mqGetQueues();
  }

  /**
   * Test disconnecting all services
   */
  public function testDisconnect()
  {
    //Disconnect all services
    self::$iio->disconnect();

    $mq = self::$iio->getRawMq();
    $this->assertNull($mq);

    $worker = self::$iio->getRawWorker();
    $this->assertNull($worker);

    $cache = self::$iio->getRawCache();
    $this->assertNull($cache);
  }

  /**
   * Test what happens if we try to connect without setting up any connection types
   */
  public function testConnectWithNoServices()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Try to connect with no services
    self::$iio->services = array();

    $mq = self::$iio->getRawMq();
    $this->assertNull($mq);

    $worker = self::$iio->getRawWorker();
    $this->assertNull($worker);

    $cache = self::$iio->getRawCache();
    $this->assertNull($cache);
  }

  /**
   * Test connecting to mq only
   */
  public function testConnectIronMq()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Try mq only
    self::$iio->services = array('mq');
    self::$iio->connect();

    $worker = self::$iio->getRawWorker();
    $this->assertNull($worker);

    $cache = self::$iio->getRawCache();
    $this->assertNull($cache);

    $mq = self::$iio->getRawMq();
    $this->assertInstanceOf('IronMQ', $mq);
  }

  /**
   * Test connecting to iron worker only
   */
  public function testConnectIronWorker()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Try worker only
    self::$iio->services = array('worker');
    self::$iio->connect();

    $mq = self::$iio->getRawMq();
    $this->assertNull($mq);

    $cache = self::$iio->getRawCache();
    $this->assertNull($cache);

    $worker = self::$iio->getRawWorker();
    $this->assertInstanceOf('IronWorker', $worker);
  }

  /**
   * Test connecting to iron cache only
   */
  public function testConnectIronCache()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Try worker only
    self::$iio->services = array('cache');
    self::$iio->connect();

    $mq = self::$iio->getRawMq();
    $this->assertNull($mq);

    $worker = self::$iio->getRawWorker();
    $this->assertNull($worker);

    $cache = self::$iio->getRawCache();
    $this->assertInstanceOf('IronCache', $cache);
  }

  /**
   * Test what happens when we connect to a service that doesn't exist
   * @expectedException CException
   */
  public function testConnectFaultyService()
  {
    //Disconnect all services
    self::$iio->disconnect();

    //Test non existing service
    self::$iio->services = array('faulty_service');
    self::$iio->connect();
  }

  /**
   * Try to get the iron worker object
   */
  public function testGetWorker()
  {
    //Disconnect all services
    self::$iio->disconnect();
    //Try worker only
    self::$iio->services = array('worker');
    self::$iio->connect();

    $worker = self::$iio->getRawWorker();
    $this->assertInstanceOf('IronWorker', $worker);
  }

  /**
   * Try to get the iron mq object
   */
  public function testGetMq()
  {
    //Disconnect all services
    self::$iio->disconnect();
    //Try worker only
    self::$iio->services = array('mq');
    self::$iio->connect();

    $mq = self::$iio->getRawMq();
    $this->assertInstanceOf('IronMQ', $mq);
  }

  /**
   * Try to get the iron cache object
   */
  public function testGetCache()
  {
    //Disconnect all services
    self::$iio->disconnect();
    //Try cache only
    self::$iio->services = array('cache');
    self::$iio->connect();

    $cached = self::$iio->getRawCache();
    $this->assertInstanceOf('IronCache', $cached);
  }

  /**
   * Testing setting a few messages on the queue
   */
  public function testSetMessages()
  {
    //Disconnect all services
    self::$iio->disconnect();
    self::$iio->services = array('mq');
    self::$iio->connect();
    $messages = 2;

    for($i=0;$i<$messages;$i++)
    {
      $messageResult = self::$iio->mqPostMessage("test_queue", "The Message");
      $this->assertEquals("Messages put on queue.", $messageResult->msg);
    }
  }

  /**
   * Testing pulling the messages off the queue and deleting them.
   * This will go on until all messages are gone.
   */
  public function testGetAndDeleteMessages()
  {
    self::$iio->disconnect();
    self::$iio->services = array('mq');
    self::$iio->connect();

    //Get rid of all the messages on the test queue
    while(true)
    {
      $messageResult = self::$iio->mqGetMessage("test_queue");

      //Returns null when we are out of messages
      if($messageResult == null)
      {
        //End the test when the queue is empty
        break;
      }
      $this->assertEquals("The Message", $messageResult->body);
      $deleteResult = self::$iio->mqDeleteMessage("test_queue", $messageResult->id);
      $this->assertEquals('{"msg":"Deleted"}', $deleteResult);
    }
  }

  /**
   * Test getting and setting and deleting Iron Cache
   */
  public function testSetGetDeleteCache()
  {
    self::$iio->disconnect();
    self::$iio->services = array('cache');
    self::$iio->connect();

    // Set the default cache name
    self::$iio->cacheSetCacheName("test_cache");

    // Put value to cache by key
    $cacheValue = self::$iio->cachePut("number_item", 42);

    $this->assertEquals($cacheValue->msg, 'Stored.');

    // Get value from cache by key
    $value = self::$iio->cacheGet("number_item")->value;
    $this->assertEquals($value, 42);

    // Delete the cache value
    $deleteResult = self::$iio->cacheDelete("number_item");
    $this->assertEquals($deleteResult->msg, 'Deleted.');
  }

  /**
   * Test that we can get a connection to iron.io
   */
  public function testTestConnection()
  {
    self::$iio->connect();
    $testResult = self::$iio->testConnection();
    $this->assertTrue($testResult);
  }

  /**
   * Test what happens when we connect with bad credentials.
   * @expectedException CException
   */
  public function testTestConnectionBadCredentials()
  {
    self::$iio->disconnect();
    self::$iio->token = "faulty_token";
    self::$iio->connect();
    $testResult = self::$iio->testConnection();
  }
}
