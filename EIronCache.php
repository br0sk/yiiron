<?php
/**
 * Extends the Yii class 'CCache' to store cached data in Iron Cache.
 *
 * @author John Eskilsson <john.eskilsson@gmail.com>
 * @link https://github.com/br0sk/yiiron
 * @link http://br0sk.blogspot.co.uk/
 * @copyright 2013
 * @license New BSD License
 */

/**
 * EIronCache implements a cache application component by storing cached data in Iron Cache.
 *
 * EIronCache stores cache data in a cache called yiiiron_cache by default.
 * If the cache bucket does not exist, it will be automatically created.
 *
 * You can also specify {@link yiironConnectionId} to select another Iron Cache bucket name
 *
 * See {@link CCache} manual for common cache operations that are supported by EIronCache.
 */
class EIronCache extends CCache
{
	/**
	 * @var string the ID of the application component of Yiiron
	 */
	public $yiironConnectionId = 'yiiron'; //the config id of Yiiron

	/**
	 * @var string name of the Iron Cache. You can have several cache "buckets" in Iron Cache.
   * This is the name of the "bucket" and it will be created automatically if there is no bucket with that
   * name already.
   * Defaults to 'yii_iron_cache'.
	 */
	public $yiironCacheName = 'yiiiron_cache';

  /**
   * @var EYiiron
   * This is the adapter object for iron.io. In order to get this class to work the cache service must be
   * activated.
   */
  private $_yiiron;

  /**
   * Here we connect the Iron Cache to prepare for setting, getting or deleting cache entries.
   */
  public function init()
  {
    parent::init();
    $this->_yiiron = Yii::app()->yiiron;
  }

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key A unique key identifying the cached value
	 * @return string The value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
    $cacheItem = $this->_yiiron->cacheGetItem($this->yiironCacheName, $key);
		if ($cacheItem != null && $cacheItem->value != null)
			return $cacheItem->value;
    else
      return false;
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key,$value,$expire)
	{
    try{
      $cacheResult = $this->_yiiron->cachePutItem($this->yiironCacheName, $key, array(
        "value" => $value,
        'expires_in' => $expire
      ));
      return true;
    }
    catch(Exception $e){
      Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, "ext.yiiron" );
      return false;
    }
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * If the key exists the value will be updated, otherwise inserted
	 *
	 * @param string $key The key identifying the value to be cached
	 * @param string $value The value to be cached
	 * @param integer $expire The number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key,$value,$expire)
	{
    //Same implementation as setValue, Iron Cache handles this
    return $this->setValue($key,$value,$expire);
  }

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key The key of the value to be deleted
	 * @return boolean If no error happens during deletion. If something goes wrong we return false.
	 */
	protected function deleteValue($key)
	{
    try{
      $this->_yiiron->cacheDeleteItem($this->yiironCacheName, $key);
      return true;
    }
    catch(Exception $e){
      Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, "ext.yiiron" );
      return false;
    }
	}

	/**
	 * Deletes all values from cache.
	 * This is the implementation of the method declared in the parent class.
	 * @return boolean Whether the flush operation was successful.
	 */
	protected function flushValues()
	{
    try{
      $this->_yiiron->cacheClear($this->yiironCacheName);
      return true;
    }
    catch(Exception $e){
      Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, "ext.yiiron" );
      return false;
    }
	}
}