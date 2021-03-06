<?php

class Gateway_Extras_CustomFilters_IP_Velocity extends Gateway_Extras {

	/**
	 * Container for an instance of self
	 * @var object
	 */
	static $instance;

	/**
	 * Custom filter object holder
	 * @var object
	 */
	public $cfo;

	public function __construct( &$gateway_adapter, &$custom_filter_object = null ) {
		parent::__construct( $gateway_adapter );
		$this->cfo = & $custom_filter_object;
	}

	public function filter() {
		$user_ip = $this->gateway_adapter->getData_Unstaged_Escaped( 'user_ip' );
		
		//first, handle the whitelist / blacklist before you do anything else. 
		if ( DataValidator::ip_is_listed( $user_ip, 'IPWhitelist' ) ){
			$this->gateway_adapter->debugarray[] = "IP present in whitelist.";
			$this->cfo->addRiskScore( 0, 'IPWhitelist' );
			return true;
		}
		if ( DataValidator::ip_is_listed( $user_ip, 'IPBlacklist' ) ){
			$this->gateway_adapter->debugarray[] = "IP present in blacklist.";
			$this->cfo->addRiskScore( $this->gateway_adapter->getGlobal( 'IPVelocityFailScore' ), 'IPBlacklist' );
			return true;
		}
		
		//if the user ip was in neither list, check the velocity. 
		if ( $this->connectToMemcache() ){

			$stored = $this->getMemcachedValue();

			if (!$stored){ //we don't have anything in memcache for this dude yet.
				$this->gateway_adapter->debugarray[] = "Found no memcached data for $user_ip";
				$this->cfo->addRiskScore( 0, 'IPVelocityFilter' ); //want to see the explicit zero
				return true;
			} else {
				$count = count( $stored );
				$this->gateway_adapter->debugarray[] = "Found a memcached bit of data for $user_ip: " . print_r($stored, true);
				$this->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . " IPVelocityFilter: $user_ip has $count hits" );
				if ( $count >= $this->gateway_adapter->getGlobal( 'IPVelocityThreshhold' ) ){
					$this->cfo->addRiskScore( $this->gateway_adapter->getGlobal( 'IPVelocityFailScore' ), 'IPVelocityFilter' );
					//cool off, sucker. Muahahaha. 
					$this->addNowToMemcachedValue( $stored, true );
				} else {
					$this->cfo->addRiskScore( 0, 'IPVelocityFilter' ); //want to see the explicit zero here, too.
				}
			}	
		}
		
		//fail open, in case memcached doesn't work.
		return true;
	}
	
	
	public function postProcess(){
		//after a successful transaction, add a record of it.
		if ( $this->connectToMemcache() ){
			$this->addNowToMemcachedValue();
		}
		return true;
	}
	
	function connectToMemcache(){
		//this needs Memcached to work.
		if ( !class_exists('Memcached') ){
			$this->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . " IPVelocityFilter says Memcached class does not exist.", LOG_ALERT );
			return false; //can't proceed... 
		}
		
		//connect to memcache
		$this->cache_obj = new Memcached;
		$ret = $this->cache_obj->addServer( $this->gateway_adapter->getGlobal( 'MemcacheHost' ), $this->gateway_adapter->getGlobal( 'MemcachePort' ) );
		if ($ret){
			return true;
		} else {
			$this->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . " IPVelocityFilter unable to connect to memcache host " . $this->gateway_adapter->getGlobal( 'MemcacheHost' ), LOG_ALERT );
			return false;
		}
	}
	
	function getMemcachedValue(){		
		//check to see if the user ip is in memcache
		//need to be connected first. 
		$user_ip = $this->gateway_adapter->getData_Unstaged_Escaped( 'user_ip' );
		$stored = $this->cache_obj->get( $user_ip );
		return $stored;
	}
	
	/**
	 * Adds the ip to the local memcache, recording another attempt.
	 * If the $fail var is set and true, this denotes that the sensor has been 
	 * tripped and will cause the data to live for the (potentially longer) 
	 * duration defined in the IPVelocityFailDuration global
	 * @param array $oldvalue The value we've just pulled from memcache for this 
	 * ip address
	 * @param bool $fail If this entry was added on the filter being tripped
	 */
	function addNowToMemcachedValue( $oldvalue = null, $fail = false ){
		//need to be connected first. 
		if ( is_null( $oldvalue ) ){
			$oldvalue = $this->getMemcachedValue();
		}
		
		$timeout = null;
		if ( $fail ){
			$timeout = $this->gateway_adapter->getGlobal( 'IPVelocityFailDuration' );
		}
		if ( is_null($timeout) ){
			$timeout = $this->gateway_adapter->getGlobal( 'IPVelocityTimeout' );
		}
		
		$user_ip = $this->gateway_adapter->getData_Unstaged_Escaped( 'user_ip' );
		$ret = $this->cache_obj->set( $user_ip, self::addNowToVelocityData( $oldvalue, $timeout ), $timeout );
		if (!$ret){
			$this->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . " IPVelocityFilter unable to set new memcache data.", LOG_ALERT );
		}
	}
	
	
	static function addNowToVelocityData( $stored = false, $timeout = false ){
		$new_velocity_records = array();
		$nowstamp = time();
		if ( is_array( $stored ) ){
			foreach ( $stored as $timestamp ){
				if ( !$timeout || $timestamp > ( $nowstamp - $timeout ) ){
					$new_velocity_records[] = $timestamp;
				}
			}
		}
		$new_velocity_records[] = $nowstamp;
		return $new_velocity_records;
	}
	

	static function onFilter( &$gateway_adapter, &$custom_filter_object ) {
		if ( !$gateway_adapter->getGlobal( 'EnableIPVelocityFilter' ) ){
			return true;
		}
		$gateway_adapter->debugarray[] = 'IP Velocity onFilter hook!';
		return self::singleton( $gateway_adapter, $custom_filter_object )->filter();
	}
	
	static function onPostProcess( &$gateway_adapter ) {
		if ( !$gateway_adapter->getGlobal( 'EnableIPVelocityFilter' ) ){
			return true;
		}
		$gateway_adapter->debugarray[] = 'IP Velocity onPostProcess hook!';
		$dummy = null; //have to do this or it fails hard on a pass-by-reference...
		return self::singleton( $gateway_adapter, $dummy )->postProcess();
	}

	static function singleton( &$gateway_adapter, &$custom_filter_object ) {
		if ( !self::$instance || $gateway_adapter->isBatchProcessor() ) {
			self::$instance = new self( $gateway_adapter, $custom_filter_object );
		}
		return self::$instance;
	}

}
