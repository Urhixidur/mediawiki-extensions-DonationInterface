<?php
/**
 * Wikimedia Foundation
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 */

/**
 * Gateway_Extras_CustomFilters_MinFraud
 *
 * Implements minFraud from Maxmind with CustomFilters.
 *
 * This allows us to capture the riskScore from minfraud and adjust it with our
 * own custom filters and risk score modifications.
 *
 * Enabling the minFraud filter requires three variables to be set in
 * LocalSettings.php:
 *
 * @code
 * $wgDonationInterfaceEnableMinfraud = true;
 * $wgMinFraudLicenseKey = 'YOUR LICENSE KEY';
 * $wgDonationInterfaceMinFraudActionRanges
 * @endcode
 */
class Gateway_Extras_CustomFilters_MinFraud extends Gateway_Extras {

	/**
	 * Instance of minFraud CreditCardFraudDetection
	 * @var CreditCardFraudDetection $ccfd
	 */
	public $ccfd;

	/**
	 * Instance of Custom filter object
	 * @var Gateway_Extras_CustomFilters $cfo
	 */
	public $cfo;

	/**
	 * The query to send to minFraud
	 * @var array $minfraudQuery
	 */
	public $minfraudQuery = array();

	/**
	 * Full response from minFraud
	 * @var array $minfraudResponse
	 */
	public $minfraudResponse = array();

	/**
	 * An array of minFraud API servers
	 * @var array $minFraudServers
	 */
	public $minFraudServers = array();

	/**
	 * License key for minfraud
	 * @var string $minfraudLicenseKey
	 */
	public $minfraudLicenseKey = '';
	
	/**
	 * Instance of Gateway_Extras_CustomFilters_MinFraud
	 * @var Gateway_Extras_CustomFilters_MinFraud $instance
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @param GatewayAdapter    $gateway_adapter    Gateway adapter instance
	 * @param GatewayAdapter    $custom_filter_object    Gateway adapter instance
	 * @param string            $license_key        The license key. May also be set in $wgMinFraudLicenseKey
	 * @throws MWException
	 */
	public function __construct( &$gateway_adapter, &$custom_filter_object, $license_key = NULL ) {

		parent::__construct( $gateway_adapter );

		$this->cfo = &$custom_filter_object;

		require_once( dirname( __FILE__ ) . "/ccfd/CreditCardFraudDetection.php" );

		global $wgMinFraudLicenseKey;

		// set the minfraud license key, go no further if we don't have it
		if ( !$license_key && !$wgMinFraudLicenseKey ) {
			throw new MWException( "minFraud license key required but not present." );
		}
		$this->minfraudLicenseKey = ( $license_key ) ? $license_key : $wgMinFraudLicenseKey;
		
		// Set the action range
		$gateway_ranges = $gateway_adapter->getGlobal( 'MinFraudActionRanges' );
		if ( !is_null( $gateway_ranges ) ) {
			$this->action_ranges = $gateway_ranges;
		}
		
		// Set the minFraud API servers
		$minFraudServers = $gateway_adapter->getGlobal( 'MinFraudServers' );
		if ( !empty( $minFraudServers ) && is_array( $minFraudServers ) ) {
			$this->minFraudServers = $minFraudServers;
		}
	}

	/**
	 * Builds minfraud query from user input
	 *
	 * Required:
	 * - city
	 * - country
	 * - i: Client IPA
	 * - license_key
	 * - postal
	 * - region
	 *
	 * Optional that we are sending:
	 * - bin: First 6 digits of the card
	 * - domain: send the domain of the email address
	 * - emailMD5: send an MD5 of the email address
	 * - txnID: The internal transaction id of the contribution.
	 *
	 * @param array $data
	 * @return array containing hash for minfraud query
	 */
	public function build_query( array $data ) {
		// mapping of data keys -> minfraud array keys
		$map = array(
			"city" => "city",
			"region" => "state",
			"postal" => "zip",
			"country" => "country",
			"domain" => "email",
			"emailMD5" => "email",
			"bin" => "card_num",
			"txnID" => "contribution_tracking_id"
		);
		
		$this->minfraudQuery = array();

		// minfraud license key
		$this->minfraudQuery["license_key"] = $this->minfraudLicenseKey;

		// user's IP address
		//TODO: GET THIS FROM THE ADAPTER. 
		$this->minfraudQuery["i"] = ( $this->gateway_adapter->getData_Unstaged_Escaped( 'user_ip' ) );

		// user's user agent
		global $wgRequest;
		$this->minfraudQuery["user_agent"] = $wgRequest->getHeader( 'user-agent' );

		// user's language
		$this->minfraudQuery['accept_language'] = $wgRequest->getHeader( 'accept-language' );

		// fetch the array of country codes
		$country_codes = GatewayForm::getCountries();

		// loop through the map and add pertinent values from $data to the hash
		foreach ( $map as $key => $value ) {

			// do some data processing to clean up values for minfraud
			switch ( $key ) {
				case "domain": // get just the domain from the email address
					$newdata[$value] = substr( strstr( $data[$value], '@' ), 1 );
					break;
				case "bin": // get just the first 6 digits from CC#
					$newdata[$value] = substr( $data[$value], 0, 6 );
					break;
				case "country":
					$newdata[$value] = $country_codes[$data[$value]];
					break;
				case "emailMD5":
					$newdata[$value] = $this->get_ccfd()->filter_field( $key, $data[$value] );
					break;
				default:
					$newdata[$value] = $data[$value];
			}

			$this->minfraudQuery[$key] = $newdata[$value];
		}

		return $this->minfraudQuery;
	}

	/**
	 * Check to see if we can bypass minFraud check
	 *
	 * The first time a user hits the submission form, a hash of the full data array plus a
	 * hashed action name are injected to the data.  This allows us to track the transaction's
	 * status.  If a valid hash of the data is present and a valid action is present, we can
	 * assume the transaction has already gone through the minFraud check and can be passed
	 * on to the appropriate action.
	 *
	 * @return boolean
	 */
	public function can_bypass_minfraud() {
		// if the data bits data_hash and action are not set, we need to hit minFraud
		$localdata = $this->gateway_adapter->getData_Unstaged_Escaped();
		if ( !isset($localdata['data_hash']) || !strlen( $localdata['data_hash'] ) || !isset($localdata['action']) || !strlen( $localdata['action'] ) ) {
			return FALSE;
		}

		$data_hash = $localdata['data_hash']; // the data hash passed in by the form submission		
		// unset these values since they are not part of the overall data hash
		$this->gateway_adapter->unsetHash();
		unset( $localdata['data_hash'] );
		// compare the data hash to make sure it's legit
		if ( $this->compare_hash( $data_hash, serialize( $localdata ) ) ) {

			$this->gateway_adapter->setHash( $this->generate_hash( $this->gateway_adapter->getData_Unstaged_Escaped() ) ); // hash the data array
			// check to see if we have a valid action set for us to bypass minfraud
			$actions = array( 'process', 'challenge', 'review', 'reject' );
			$action_hash = $localdata['action']; // a hash of the action to take passed in by the form submission
			foreach ( $actions as $action ) {
				if ( $this->compare_hash( $action_hash, $action ) ) {
					// set the action that should be taken
					$this->gateway_adapter->setValidationAction( $action );
					return TRUE;
				}
			}
		} else {
			// log potential tampering
			if ( $this->log_fh )
				$this->log( $localdata['contribution_tracking_id'], 'Data hash/action mismatch', LOG_ERR );
		}

		return FALSE;
	}

	/**
	 * Execute the minFraud filter
	 *
	 * @return bool true
	 */
	public function filter() {
		// see if we can bypass minfraud
		if ( $this->can_bypass_minfraud() ){
			return TRUE;
		}

		$minfraud_query = $this->build_query( $this->gateway_adapter->getData_Unstaged_Escaped() );
		$this->query_minfraud( $minfraud_query );
		
		// Write the query/response to the log before we go mad.
		$this->log_query();
		
		try {
			$this->cfo->addRiskScore( $this->minfraudResponse['riskScore'], 'minfraud_filter' );
		} 
		catch( MWException $ex){
			//log out the whole response to the error log so we can tell what the heck happened... and fail closed.
			$log_message = 'Minfraud filter came back with some garbage. Assigning all the points.';
			$this->cfo->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . '"addRiskScore" ' . $log_message , LOG_ERR, '_fraud' );
			$this->cfo->addRiskScore( 100, 'minfraud_filter' );
		}

		return TRUE;
	}

	/**
	 * Get instance of CreditCardFraudDetection
	 * @return CreditCardFraudDetection
	 */
	public function get_ccfd() {
		if ( !$this->ccfd ) {
			$this->ccfd = new CreditCardFraudDetection( $this->gateway_adapter );
			
			// Override the minFraud API servers
			if ( !empty( $this->minFraudServers ) && is_array( $this->minFraudServers )  ) {
				
				$this->ccfd->server = $this->minFraudServers;
			}
		}
		return $this->ccfd;
	}

	/**
	 * Logs a minFraud query and its response
	 *
	 * WARNING: It is critical that the order of these fields is not altered.
	 *
	 * The minfraud_log_mailer depends on the order of these fields.
	 *
	 * @see http://svn.wikimedia.org/viewvc/wikimedia/trunk/fundraising-misc/minfraud_log_mailer/
	 */
	public function log_query() {

		$encoded_response = array();
		foreach ($this->minfraudResponse as $key => $value) {
			$encoded_response[ $key ] = utf8_encode( $value );
		}

		$log_message = '';

		$log_message .= '"' . addslashes( $this->gateway_adapter->getData_Unstaged_Escaped( 'comment' ) ) . '"';
		$log_message .= "\t" . '"' . date( 'c' ) . '"';
		$log_message .= "\t" . '"' . addslashes( $this->gateway_adapter->getData_Unstaged_Escaped( 'amount' ) . ' ' . $this->gateway_adapter->getData_Unstaged_Escaped( 'currency_code' ) ) . '"';
		$log_message .= "\t" . '"' . addslashes( json_encode( $this->minfraudQuery ) ) . '"';
		$log_message .= "\t" . '"' . addslashes( json_encode( $encoded_response ) ) . '"';
		$log_message .= "\t" . '"' . addslashes( $this->gateway_adapter->getData_Unstaged_Escaped( 'referrer' ) ) . '"';
		$this->gateway_adapter->log( $this->gateway_adapter->getLogMessagePrefix() . '"minFraud query" ' . $log_message , LOG_INFO, '_fraud' );
	}

	/**
	 * Get an instance of Gateway_Extras_CustomFilters_MinFraud
	 *
	 * @param GlobalCollectAdapter $gateway_adapter
	 * @param Gateway_Extras_CustomFilters $custom_filter_object
	 *
	 * @return true
	 */
	public static function onFilter( &$gateway_adapter, &$custom_filter_object ) {
		
		if ( !$gateway_adapter->getGlobal( 'EnableMinfraud' ) ){
			return true;
		}
		$gateway_adapter->debugarray[] = 'minfraud onFilter hook!';
		return self::singleton( $gateway_adapter, $custom_filter_object )->filter();
	}

	/**
	 * Perform the min fraud query and capture the response
	 *
	 * @param array $minfraud_query The array you would pass to minfraud in a query
	 */
	public function query_minfraud( array $minfraud_query ) {
		global $wgMinFraudTimeout;
		$this->get_ccfd()->timeout = $wgMinFraudTimeout;
		$this->get_ccfd()->input( $minfraud_query );
		$this->get_ccfd()->query();
		$this->minfraudResponse = $this->get_ccfd()->output();
	}

	/**
	 * Get an instance of Gateway_Extras_CustomFilters_MinFraud
	 *
	 * @param GlobalCollectAdapter $gateway_adapter
	 * @param Gateway_Extras_CustomFilters $custom_filter_object
	 *
	 * @return Gateway_Extras_CustomFilters_MinFraud
	 */
	public static function singleton( &$gateway_adapter, &$custom_filter_object ) {
		if ( !self::$instance || $gateway_adapter->isBatchProcessor() ) {
			self::$instance = new self( $gateway_adapter, $custom_filter_object );
		}
		return self::$instance;
	}

	/**
	 * Validates the minfraud_query for minimum required fields
	 *
	 * This is a pretty dumb validator.  It just checks to see if
	 * there is a value for a required field and if its length is > 0
	 *
	 * @param array $minfraud_query The array you would pass to minfraud in a query
	 * @return boolean
	 */
	public function validate_minfraud_query( array $minfraud_query ) {
		// array of minfraud required fields
		$reqd_fields = array(
			'license_key',
			'i',
			'city',
			'region',
			'postal',
			'country'
		);

		foreach ( $reqd_fields as $reqd_field ) {
			if ( !isset( $minfraud_query[$reqd_field] ) ||
				strlen( $minfraud_query[$reqd_field] ) < 1 ) {
				return FALSE;
			}
		}

		return TRUE;
	}
}
