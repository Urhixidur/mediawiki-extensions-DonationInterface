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

class AmazonGateway extends GatewayForm {
	/**
	 * Constructor - set up the new special page
	 */
	public function __construct() {
		$this->adapter = new AmazonAdapter();
		parent::__construct(); //the next layer up will know who we are.
	}

	/**
	 * Show the special page
	 *
	 * @todo
	 * - Finish error handling
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$this->getOutput()->allowClickjacking();

		$this->setHeaders();

		if ( $this->getRequest()->getText( 'redirect', 0 ) ) {
			$this->adapter->do_transaction( 'Donate' );
			return;
		}

		$this->log( 'At gateway return with params: ' . json_encode( $this->getRequest()->getValues() ), LOG_INFO );
		if ( $this->adapter->checkTokens() && $this->getRequest()->getText( 'status' ) ) {
			$this->adapter->do_transaction( 'ProcessAmazonReturn' );

			$status = $this->adapter->getTransactionWMFStatus();

			if ( ( $status == 'complete' ) || ( $status == 'pending' ) ) {
				$this->getOutput()->redirect( $this->adapter->getThankYouPage() );
			}
			else {
				$this->getOutput()->redirect( $this->adapter->getFailPage() );
			}
		} else {
			$this->log( 'Failed to process gateway return. Tokens bad or no status.', LOG_ERR );
		}
	}
}
