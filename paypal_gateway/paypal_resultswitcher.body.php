<?php

class PaypalGatewayResult extends GatewayForm {

	/**
	 * Defines the action to take on a Paypal transaction.
	 *
	 * Possible values include 'process', 'challenge',
	 * 'review', 'reject'.  These values can be set during
	 * data processing validation, for instance.
	 *
	 * Hooks are exposed to handle the different actions.
	 *
	 * Defaults to 'process'.
	 * @var string
	 */
	public $action = 'process';

	/**
	 * An array of form errors
	 * @var array
	 */
	public $errors = array( );

	public function __construct() {
		$this->adapter = new PaypalAdapter();
		parent::__construct();
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		//no longer letting people in without these things. If this is 
		//preventing you from doing something, you almost certainly want to be 
		//somewhere else. 
		$forbidden = false;
		if ( !$this->adapter->hasDonorDataInSession() ) {
			$forbidden = true;
			$f_message = 'No active donation in the session';
		}
		
		if ( $forbidden ){
			wfHttpError( 403, 'Forbidden', wfMsg( 'donate_interface-error-http-403' ) );
		}
		$oid = $this->adapter->getData_Unstaged_Escaped( 'order_id' );

		$this->setHeaders();
		
		if ( $this->adapter->checkTokens() ) {

/*
			// One day, we should have pass/fail detection.  I don't think PP returns enough information at the moment.
			if ( NULL === $this->adapter->processResponse( $ ) ) {
				switch ( $this->adapter->getTransactionWMFStatus() ) {
				case 'complete':
				case 'pending':
					$this->getOutput()->redirect( $this->adapter->getThankYouPage() );
					return;
				}
			}
			$this->getOutput()->redirect( $this->adapter->getFailPage() );
*/
			$this->getOutput()->redirect( $this->adapter->getThankYouPage() );
		} else {
			$this->adapter->log("Resultswitcher: Token Check Failed. Order ID: $oid");
			$this->getOutput()->redirect( $this->adapter->getFailPage() );
		}
	}

}

// end class
