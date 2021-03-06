<?php

/**
 * Donation Interface
 *
 *  To install the DontaionInterface extension, put the following line in LocalSettings.php:
 *	require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
 *
 */


# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install the DontaionInterface extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/DonationInterface/donationinterface.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Donation Interface',
	'author' => array( 'Katie Horn', 'Ryan Kaldari' , 'Arthur Richards', 'Matt Walker', 'Adam Wight', 'Peter Gehres', 'Jeremy Postlethwaite' ),
	'version' => '2.0.0',
	'descriptionmsg' => 'donationinterface-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:DonationInterface',
);

$donationinterface_dir = dirname( __FILE__ ) . '/';

/**
 * Figure out what we've got enabled.
 */

$optionalParts = array( //define as fail closed. This variable will be unset before we leave this file.
	'Extras' => false, //this one gets set in the next loop, so don't bother.
	'CustomFilters' => false, //Also gets set in the next loop.
	'Stomp' => false,
	'ConversionLog' => false, //this is definitely an Extra
	'Minfraud' => false, //this is definitely an Extra
	'Recaptcha' => false, //extra
	'PayflowPro' => false,
	'GlobalCollect' => false,
	'Amazon' => false,
	'Adyen' => false,
	'Paypal' => false,
	'FormChooser' => false,
	'ReferrerFilter' => false, //extra
	'SourceFilter' => false, //extra
	'FunctionsFilter' => false, //extra
	'IPVelocityFilter' => false, //extra
	'SessionVelocityFilter' => false, //extra
	'SystemStatus' => false, //extra
);

foreach ($optionalParts as $subextension => $enabled){
	$globalname = 'wgDonationInterfaceEnable' . $subextension;
	global $$globalname;
	if ( isset( $$globalname ) && $$globalname === true ) {
		$optionalParts[$subextension] = true;
		//this is getting annoying.
		if ( $subextension === 'ReferrerFilter' ||
			$subextension === 'SourceFilter' ||
			$subextension === 'FunctionsFilter' ||
			$subextension === 'ConversionLog' ||
			$subextension === 'Minfraud' ||
			$subextension === 'Recaptcha' ||
			$subextension === 'IPVelocityFilter' ||
			$subextension === 'SessionVelocityFilter') {

			//we have extras
			$optionalParts['Extras'] = true;

			if ( $subextension === 'ReferrerFilter' ||
				$subextension === 'SourceFilter' ||
				$subextension === 'FunctionsFilter' ||
				$subextension === 'Minfraud' ||
				$subextension === 'IPVelocityFilter' ||
				$subextension === 'SessionVelocityFilter' ){

				//and at least one of them is a custom filter.
				$optionalParts['CustomFilters'] = true;
				$wgDonationInterfaceEnableCustomFilters = true; //override this for specific gateways to disable
			}
		}

	}
}


/**
 * CLASSES
 */
$wgAutoloadClasses['DonationData'] = $donationinterface_dir . 'gateway_common/DonationData.php';
$wgAutoloadClasses['GatewayAdapter'] = $donationinterface_dir . 'gateway_common/gateway.adapter.php';
$wgAutoloadClasses['GatewayForm'] = $donationinterface_dir . 'gateway_common/GatewayForm.php';
$wgAutoloadClasses['DataValidator'] = $donationinterface_dir . 'gateway_common/DataValidator.php';

//load all possible form classes
$wgAutoloadClasses['Gateway_Form'] = $donationinterface_dir . 'gateway_forms/Form.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumn'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumn.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetter3'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetter3.php';
$wgAutoloadClasses['Gateway_Form_TwoStepTwoColumnLetterCA'] = $donationinterface_dir . 'gateway_forms/TwoStepTwoColumnLetterCA.php';
$wgAutoloadClasses['Gateway_Form_RapidHtml'] = $donationinterface_dir . 'gateway_forms/RapidHtml.php';

//GlobalCollect gateway classes
if ( $optionalParts['GlobalCollect'] === true ){
	$wgAutoloadClasses['GlobalCollectGateway'] = $donationinterface_dir . 'globalcollect_gateway/globalcollect_gateway.body.php';
	$wgAutoloadClasses['GlobalCollectGatewayResult'] = $donationinterface_dir . 'globalcollect_gateway/globalcollect_resultswitcher.body.php';
	$wgAutoloadClasses['GlobalCollectAdapter'] = $donationinterface_dir . 'globalcollect_gateway/globalcollect.adapter.php';
}

//PayflowPro gateway classes
if ( $optionalParts['PayflowPro'] === true ){
	$wgAutoloadClasses['PayflowProGateway'] = $donationinterface_dir . 'payflowpro_gateway/payflowpro_gateway.body.php';
	$wgAutoloadClasses['PayflowProAdapter'] = $donationinterface_dir . 'payflowpro_gateway/payflowpro.adapter.php';
}

if ( $optionalParts['Amazon'] === true ){
	$wgAutoloadClasses['AmazonGateway'] = $donationinterface_dir . 'amazon_gateway/amazon_gateway.body.php';
	$wgAutoloadClasses['AmazonAdapter'] = $donationinterface_dir . 'amazon_gateway/amazon.adapter.php';
}

if ( $optionalParts['Adyen'] === true ){
	$wgAutoloadClasses['AdyenGateway'] = $donationinterface_dir . 'adyen_gateway/adyen_gateway.body.php';
	$wgAutoloadClasses['AdyenGatewayResult'] = $donationinterface_dir . 'adyen_gateway/adyen_resultswitcher.body.php';
	$wgAutoloadClasses['AdyenAdapter'] = $donationinterface_dir . 'adyen_gateway/adyen.adapter.php';
}

if ( $optionalParts['Paypal'] === true ){
	$wgAutoloadClasses['PaypalGateway'] = $donationinterface_dir . 'paypal_gateway/paypal_gateway.body.php';
	$wgAutoloadClasses['PaypalGatewayResult'] = $donationinterface_dir . 'paypal_gateway/paypal_resultswitcher.body.php';
	$wgAutoloadClasses['PaypalAdapter'] = $donationinterface_dir . 'paypal_gateway/paypal.adapter.php';
}


//Stomp classes
if ($optionalParts['Stomp'] === true){
	$wgAutoloadClasses['activemq_stomp'] = $donationinterface_dir . 'activemq_stomp/activemq_stomp.php'; # Tell MediaWiki to load the extension body.
}

//Extras classes - required for ANY optional class that is considered an "extra".
if ($optionalParts['Extras'] === true){
	$wgAutoloadClasses['Gateway_Extras'] = $donationinterface_dir . "extras/extras.body.php";
}

//Custom Filters classes
if ($optionalParts['CustomFilters'] === true){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters'] = $donationinterface_dir . "extras/custom_filters/custom_filters.body.php";
}

//Conversion Log classes
if ($optionalParts['ConversionLog'] === true){
	$wgAutoloadClasses['Gateway_Extras_ConversionLog'] = $donationinterface_dir . "extras/conversion_log/conversion_log.body.php";
}

//Minfraud classes
if ( $optionalParts['Minfraud'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_MinFraud'] = $donationinterface_dir . "extras/custom_filters/filters/minfraud/minfraud.body.php";
}

//Referrer Filter classes
if ( $optionalParts['ReferrerFilter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_Referrer'] = $donationinterface_dir . "extras/custom_filters/filters/referrer/referrer.body.php";
}

//Source Filter classes
if ( $optionalParts['SourceFilter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_Source'] = $donationinterface_dir . "extras/custom_filters/filters/source/source.body.php";
}

//Functions Filter classes
if ( $optionalParts['FunctionsFilter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_Functions'] = $donationinterface_dir . "extras/custom_filters/filters/functions/functions.body.php";
}

//Recaptcha classes
if ( $optionalParts['Recaptcha'] === true ){
	$wgAutoloadClasses['Gateway_Extras_ReCaptcha'] = $donationinterface_dir . "extras/recaptcha/recaptcha.body.php";
}

//Functions Filter classes
if ( $optionalParts['IPVelocityFilter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_CustomFilters_IP_Velocity'] = $donationinterface_dir . "extras/custom_filters/filters/ip_velocity/ip_velocity.body.php";
}

//Functions Filter classes
if ( $optionalParts['SessionVelocityFilter'] === true ){
	$wgAutoloadClasses['Gateway_Extras_SessionVelocityFilter'] = $donationinterface_dir . "extras/session_velocity/session_velocity.body.php";
}

if ( $optionalParts['FormChooser'] === true ){
	$wgAutoloadClasses['GatewayFormChooser'] = $donationinterface_dir . 'special/GatewayFormChooser.php';

}
if ( $optionalParts['SystemStatus'] === true ){
	$wgAutoloadClasses['SystemStatus'] = $donationinterface_dir . 'special/SystemStatus.php';
}

/**
 * GLOBALS
 */

/**
 * Global form dir
 */
$wgDonationInterfaceHtmlFormDir = dirname( __FILE__ ) . "/gateway_forms/rapidhtml/html";
$wgDonationInterfaceTest = false;

//all of the following variables make sense to override directly,
//or change "DonationInterface" to the gateway's id to override just for that gateway.
//for instance: To override $wgDonationInterfaceUseSyslog just for GlobalCollect, add
// $wgGlobalCollectGatewayUseSyslog = true
// to LocalSettings.
//

$wgDonationInterfaceDisplayDebug = false;
$wgDonationInterfaceUseSyslog = false;
$wgDonationInterfaceSaveCommStats = false;

$wgDonationInterfaceCSSVersion = 1;
$wgDonationInterfaceTimeout = 5;
$wgDonationInterfaceDefaultForm = 'TwoStepTwoColumnLetter';

/**
 * A string or array of strings for making tokens more secure
 *
 * Please set this!  If you do not, tokens are easy to get around, which can
 * potentially leave you and your users vulnerable to CSRF or other forms of
 * attack.
 */
$wgDonationInterfaceSalt = $wgSecretKey;

/**
 * A string that can contain wikitext to display at the head of the credit card form
 *
 * This string gets run like so: $wg->addHtml( $wg->Parse( $wgpayflowGatewayHeader ))
 * You can use '@language' as a placeholder token to extract the user's language.
 *
 */
$wgDonationInterfaceHeader = NULL;

/**
 * A string containing full URL for Javascript-disabled credit card form redirect
 */
$wgDonationInterfaceNoScriptRedirect = null;

/**
 * Proxy settings
 *
 * If you need to use an HTTP proxy for outgoing traffic,
 * set wgPayflowProGatewayUseHTTPProxy=TRUE and set $wgPayflowProGatewayHTTPProxy
 * to the proxy desination.
 *  eg:
 *  $wgPayflowProGatewayUseHTTPProxy=TRUE;
 *  $wgPayflowProGatewayHTTPProxy='192.168.1.1:3128'
 */
$wgDonationInterfaceUseHTTPProxy = FALSE;
$wgDonationInterfaceHTTPProxy = '';

/**
 * Set the max-age value for Squid
 *
 * If you have Squid enabled for caching, use this variable to configure
 * the s-max-age for cached requests.
 * @var int Time in seconds
 */
$wgDonationInterfaceSMaxAge = 6000;

/**
 * Configure price ceiling and floor for valid contribution amount.  Values
 * should be in USD.
 */
$wgDonationInterfacePriceFloor = '1.00';
$wgDonationInterfacePriceCeiling = '10000.00';

/**
 * Default Thank You and Fail pages for all of donationinterface - language will be calc'd and appended at runtime.
 */
//$wgDonationInterfaceThankYouPage = 'https://wikimediafoundation.org/wiki/Thank_You';
$wgDonationInterfaceThankYouPage = 'Donate-thanks';
$wgDonationInterfaceFailPage = 'Donate-error'; 

/**
 * Retry Loop Count - If there's a place where the API can choose to loop on some retry behavior, do it this number of times. 
 */
$wgDonationInterfaceRetryLoopCount = 3;

/**
 * Orphan Cron settings global
 */
$wgDonationInterfaceOrphanCron = array(
	'enable' => true,
//	'override_command_line_params' => true,
//	'function' => 'orphan_stomp',
//	'target_execute_time' => 300,
//	'max_per_execute' => '',
);

/**
 * Forbidden countries. No donations will be allowed to come in from countries 
 * in this list.
 * All should be represented as all-caps ISO 3166-1 alpha-2
 * This one global shouldn't ever be overridden per gateway. As it's probably 
 * going to only conatin countries forbidden by law, there's no reason
 * to override by gateway and as such it's always referenced directly. 
 */
$wgDonationInterfaceForbiddenCountries = array();

/**
 * 3D Secure enabled currencies (and countries) for Credit Card.
 * An array in the form of currency => array of countries 
 * (all-caps ISO 3166-1 alpha-2), or an empty array for all transactions in that
 * currency regardless of country of origin.
 * As this is a mandatroy check for all INR transactions, that rule made it into
 * the default.  
 */
$wgDonationInterface3DSRules = array(
	'INR' => array(), //all countries
);

//GlobalCollect gateway globals
if ( $optionalParts['GlobalCollect'] === true ){
	$wgDonationInterfaceEnabledGateways[] = 'globalcollect';
	$wgGlobalCollectGatewayURL = 'https://ps.gcsip.nl/wdl/wdl';
	$wgGlobalCollectGatewayTestingURL = 'https://'; // GlobalCollect testing URL

#	$wgGlobalCollectGatewayAccountInfo['example'] = array(
#		'MerchantID' => '', // GlobalCollect ID
#	);

	$wgGlobalCollectGatewayHtmlFormDir = $donationinterface_dir . 'globalcollect_gateway/forms/html';
	//this really should be redefined in LocalSettings.
	$wgDonationInterfaceAllowedHtmlForms['lightbox1_gc'] = array(
		'file' => $wgGlobalCollectGatewayHtmlFormDir .'/lightbox1.html',
		'gateway' => 'globalcollect',
		'payment_methods' => array('cc' => array( 'visa', 'mc', 'amex', 'discover' ))
	);
	
	$wgGlobalCollectGatewayCvvMap = array(
		'M' => true, //CVV check performed and valid value.
		'N' => false, //CVV checked and no match.
		'P' => true, //CVV check not performed, not requested
		'S' => false, //Card holder claims no CVV-code on card, issuer states CVV-code should be on card. 
		'U' => true, //? //Issuer not certified for CVV2.
		'Y' => false, //Server provider did not respond.
		'0' => true, //No service available.
	);
	
	$wgGlobalCollectGatewayAvsMap = array(
		'A' => 50, //Address (Street) matches, Zip does not.
		'B' => 50, //Street address match for international transactions. Postal code not verified due to incompatible formats.
		'C' => 50, //Street address and postal code not verified for international transaction due to incompatible formats.
		'D' => 0, //Street address and postal codes match for international transaction.
		'E' => 100, //AVS Error.
		'F' => 0, //Address does match and five digit ZIP code does match (UK only).
		'G' => 50, //Address information is unavailable; international transaction; non-AVS participant. 
		'I' => 50, //Address information not verified for international transaction.
		'M' => 0, //Street address and postal codes match for international transaction.
		'N' => 100, //No Match on Address (Street) or Zip.
		'P' => 50, //Postal codes match for international transaction. Street address not verified due to incompatible formats.
		'R' => 100, //Retry, System unavailable or Timed out.
		'S' => 50, //Service not supported by issuer.
		'U' => 50, //Address information is unavailable.
		'W' => 50, //9 digit Zip matches, Address (Street) does not.
		'X' => 0, //Exact AVS Match.
		'Y' => 0, //Address (Street) and 5 digit Zip match.
		'Z' => 50, //5 digit Zip matches, Address (Street) does not.
		'0' => 25, //No service available.
	);	
	
}

//PayflowPro gateway globals
if ( $optionalParts['PayflowPro'] === true ){
	$wgDonationInterfaceEnabledGateways[] = 'payflowpro';
	$wgPayflowProGatewayURL = 'https://payflowpro.paypal.com';
	$wgPayflowProGatewayTestingURL = 'https://pilot-payflowpro.paypal.com'; // Payflow testing URL

#	$wgPayflowProGatewayAccountInfo['example'] = array(
#		'PartnerID' => '', // PayPal or original authorized reseller
#		'VendorID' => '', // paypal merchant login ID
#		'UserID' => '', // if one or more users are set up, authorized user ID, else same as VENDOR
#		'Password' => '', // merchant login password
#	);

	$wgPayflowProGatewayHtmlFormDir = $donationinterface_dir . 'payflowpro_gateway/forms/html';

	$wgDonationInterfaceAllowedHtmlForms['lightbox1_pfp'] = array(
		'file' => $wgPayflowProGatewayHtmlFormDir .'/lightbox1.html',
		'gateway' => 'payflowpro',
		'payment_methods' => array('cc' => array( 'visa', 'mc', 'amex', 'discover' ))
	);
	
	//defaults to not doing the new fail page redirect. 
	$wgPayflowProGatewayFailPage = false;
}

if ( $optionalParts['Amazon'] === true ){
	$wgDonationInterfaceEnabledGateways[] = 'amazon';

	//n.b. "-Testing-" urls are not wired to anything, they're just here for
	// your copy n paste pleasure.

	$wgAmazonGatewayURL = "https://authorize.payments.amazon.com/pba/paypipeline";
	$wgAmazonGatewayTestingURL = "https://authorize.payments-sandbox.amazon.com/pba/paypipeline";

	$wgAmazonGatewayFpsURL = "https://fps.amazonaws.com/";
	$wgAmazonGatewayFpsTestingURL = "https://fps.sandbox.amazonaws.com/";

#	$wgAmazonGatewayAccountInfo['example'] = array(
#		'AccessKey' => "",
#		'SecretKey' => "",
#
#		// the long one, not the AWS account ID
#		'PaymentsAccountID' => "",
#	);

	// e.g. http://payments.wikimedia.org/index.php/Special:AmazonGateway  --
	// does NOT accept unroutable development names, use the number instead
	// even if it's 127.0.0.1
	$wgAmazonGatewayReturnURL = "";
}

if ( $optionalParts['Paypal'] === true ){
	$wgDonationInterfaceEnabledGateways[] = 'paypal';

	$wgPaypalGatewayURL = 'https://www.paypal.com/cgi-bin/webscr';
	$wgPaypalGatewayTestingURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	$wgPaypalGatewayReturnURL = ''; //'http://127.0.0.1/index.php/Special:PaypalGatewayResult';
	$wgPaypalGatewayRecurringLength = '0'; // 0 should mean forever

	$wgPaypalGatewayPriceFloor = 1.00;

	$wgPaypalGatewayHtmlFormDir = $donationinterface_dir . 'paypal_gateway/forms/html';

#	$wgPaypalGatewayAccountInfo['example'] = array(
#		'AccountEmail' => "",
#	);
}

if ( $optionalParts['Adyen'] === true ){
	$wgDonationInterfaceEnabledGateways[] = 'adyen';

	$wgAdyenGatewayHtmlFormDir = $donationinterface_dir . 'adyen_gateway/forms/html';

	$wgAdyenGatewayBaseURL = 'https://live.adyen.com';
	$wgAdyenGatewayBaseTestingURL = 'https://test.adyen.com'; // unused

#	$wgAdyenGatewayAccountInfo['example'] = array(
#		'AccountName' => ''; // account identifier, not login name
#		'SharedSecret' => ''; // entered in the skin editor
#		'SkinCode' => '';
#	);
}

//Stomp globals
if ($optionalParts['Stomp'] === true){
	$wgStompServer = "";

	// In this array, 'default', 'pending', and 'limbo' are required keys for those categories of
	// transactions. The value is the name of the queue. To single out a transaction type, ie:
	// credit cards, prepend 'cc-' to the base key name.
	//
	// If the resultant queue name evaluates to false, the message will not be queued on the server.
	$wgStompQueueNames = array(
		'default' => 'test-default',    // Previously known as $wgStompQueueName
		'pending' => 'test-pending',    // Previously known as $wgPendingStompQueueName
		'limbo' => 'test-limbo',        // Previously known as $wgLimboStompQueueName
	);
}

//Extras globals - required for ANY optional class that is considered an "extra".
if ($optionalParts['Extras'] === true){
	$wgDonationInterfaceExtrasLog = '';
}

//Custom Filters globals
if ( $optionalParts['CustomFilters'] === true ){
	//Define the action to take for a given $risk_score
	$wgDonationInterfaceCustomFiltersActionRanges = array(
		'process' => array( 0, 100 ),
		'review' => array( -1, -1 ),
		'challenge' => array( -1, -1 ),
		'reject' => array( -1, -1 ),
	);

	/**
	 * A value for tracking the 'riskiness' of a transaction
	 *
	 * The action to take based on a transaction's riskScore is determined by
	 * $action_ranges.  This is built assuming a range of possible risk scores
	 * as 0-100, although you can probably bend this as needed.
	 */
	$wgDonationInterfaceCustomFiltersRiskScore = 0;
}

//Minfraud globals
if ( $optionalParts['Minfraud'] === true ){
	/**
	 * Your minFraud license key.
	 */
	$wgMinFraudLicenseKey = '';

	/**
	 * Set the risk score ranges that will cause a particular 'action'
	 *
	 * The keys to the array are the 'actions' to be taken (eg 'process').
	 * The value for one of these keys is an array representing the lower
	 * and upper bounds for that action.  For instance,
	 *   $wgDonationInterfaceMinFraudActionRanges = array(
	 * 		'process' => array( 0, 100)
	 * 		...
	 * 	);
	 * means that any transaction with a risk score greather than or equal
	 * to 0 and less than or equal to 100 will be given the 'process' action.
	 *
	 * These are evauluated on a >= or <= basis.  Please refer to minFraud
	 * documentation for a thorough explanation of the 'riskScore'.
	 */
	$wgDonationInterfaceMinFraudActionRanges = array(
		'process' => array( 0, 100 ),
		'review' => array( -1, -1 ),
		'challenge' => array( -1, -1 ),
		'reject' => array( -1, -1 )
	);

	/**
	 * This allows setting where to point the minFraud servers.
	 *
	 * As of February 21st, 2012 minfraud.maxmind.com will route to the east or
	 * west server, depending on you location.
	 *
	 * minfraud-us-east.maxmind.com: 174.36.207.186
	 * minfraud-us-west.maxmind.com: 50.97.220.226
	 *
	 * The minFraud API requires an array of servers.
	 *
	 * You do not have to specify a server.
	 *
	 * @see CreditCardFraudDetection::$server
	 */
	$wgDonationInterfaceMinFraudServers = array();

	// Timeout in seconds for communicating with MaxMind
	$wgMinFraudTimeout = 2;
}

//Referrer Filter globals
if ( $optionalParts['ReferrerFilter'] === true ){
	$wgDonationInterfaceCustomFiltersRefRules = array( );
}

//Source Filter globals
if ( $optionalParts['SourceFilter'] === true ){
	$wgDonationInterfaceCustomFiltersSrcRules = array( );
}

//Functions Filter globals
if ( $optionalParts['FunctionsFilter'] === true ){
	$wgDonationInterfaceCustomFiltersFunctions = array( );
}

//IP velocity filter globals
$wgDonationInterfaceMemcacheHost = 'localhost';
$wgDonationInterfaceMemcachePort = '11211';
$wgDonationInterfaceIPVelocityFailScore = 100;
$wgDonationInterfaceIPVelocityTimeout = 60 * 5;	//5 minutes in seconds
$wgDonationInterfaceIPVelocityThreshhold = 3;	//3 transactions per timeout
//$wgDonationInterfaceIPVelocityFailDuration is also something you can set...
//If you leave it blank, it will use the VelocityTimeout as a default.

// Session velocity filter globals
$wgDonationInterfaceSessionVelocity_HitScore = 10;  // How much to add to the score per API hit
$wgDonationInterfaceSessionVelocity_DecayRate = 1;  // Linear decay rate; pts / sec
$wgDonationInterfaceSessionVelocity_Threshold = 50; // Above this score, we deny users the page

/**
 * $wgDonationInterfaceCountryMap
 *
 * A score of 0 for a country means no risk.
 * A score of 100 means this country is extremely risky for fraud.
 *
 * The score for a country has the following range:
 *
 * 0 <= $score <= 100
 *
 * To enable this filter add this to your LocalSettings.php:
 *
 * @code
 * <?php
 *
 * $wgCustomFiltersFunctions = array(
 * 	'getScoreCountryMap' => 100,
 * );
 *
 * $wgDonationInterfaceCountryMap = array(
 * 	'CA' =>  1,
 * 	'US' => 5,
 * );
 * ?>
 * @endcode
 */
$wgDonationInterfaceCountryMap = array();

/**
 * $wgDonationInterfaceEmailDomainMap
 *
 * A score of 0 for an email domain means no risk.
 * A score of 100 means this email domain is extremely risky for fraud.
 * Scores may be negative.
 *
 * To enable this filter add this to your LocalSettings.php:
 *
 * @code
 * <?php
 *
 * $wgCustomFiltersFunctions = array(
 * 	'getScoreEmailDomainMap' => 100,
 * );
 *
 * $wgDonationInterfaceEmailDomainMap = array(
 * 	'gmail.com' =>  5,
 * 	'wikimedia.org' => 0,
 * );
 * ?>
 * @endcode
 */
$wgDonationInterfaceEmailDomainMap = array();

/**
 * $wgDonationInterfaceUtmCampaignMap
 *
 * A score of 0 for utm_campaign means no risk.
 * A score of 100 means this utm_campaign is extremely risky for fraud.
 * Scores may be negative
 *
 * To enable this filter add this to your LocalSettings.php:
 *
 * @code
 * <?php
 *
 * $wgCustomFiltersFunctions = array(
 * 	'getScoreUtmCampaignMap' => 100,
 * );
 *
 * $wgDonationInterfaceUtmCampaignMap = array(
 * 	'' =>  20,
 * 	'some-odd-string' => 100,
 * );
 * ?>
 * @endcode
 */
$wgDonationInterfaceUtmCampaignMap = array();

/**
 * $wgDonationInterfaceUtmMediumMap
 *
 * A score of 0 for utm_medium means no risk.
 * A score of 100 means this utm_medium is extremely risky for fraud.
 * Scores may be negative
 *
 * To enable this filter add this to your LocalSettings.php:
 *
 * @code
 * <?php
 *
 * $wgCustomFiltersFunctions = array(
 * 	'getScoreUtmMediumMap' => 100,
 * );
 *
 * $wgDonationInterfaceUtmMediumMap = array(
 * 	'' =>  20,
 * 	'some-odd-string' => 100,
 * );
 * ?>
 * @endcode
 */
$wgDonationInterfaceUtmMediumMap = array();

/**
 * $wgDonationInterfaceUtmSourceMap
 *
 * A score of 0 for utm_source means no risk.
 * A score of 100 means this utm_source is extremely risky for fraud.
 * Scores may be negative
 *
 * To enable this filter add this to your LocalSettings.php:
 *
 * @code
 * <?php
 *
 * $wgCustomFiltersFunctions = array(
 * 	'getScoreUtmSourceMap' => 100,
 * );
 *
 * $wgDonationInterfaceUtmSourceMap = array(
 * 	'' =>  20,
 * 	'some-odd-string' => 100,
 * );
 * ?>
 * @endcode
 */
$wgDonationInterfaceUtmSourceMap = array();

//Recaptcha globals
if ( $optionalParts['Recaptcha'] === true ){
	/**
	 * Public and Private reCaptcha keys
	 *
	 * These can be obtained at:
	 *   http://www.google.com/recaptcha/whyrecaptcha
	 */
	$wgDonationInterfaceRecaptchaPublicKey = '';
	$wgDonationInterfaceRecaptchaPrivateKey = '';

	// Timeout (in seconds) for communicating with reCAPTCHA
	$wgDonationInterfaceRecaptchaTimeout = 2;

	/**
	 * HTTP Proxy settings
	 */
	$wgDonationInterfaceRecaptchaUseHTTPProxy = false;
	$wgDonationInterfaceRecaptchaHTTPProxy = false;

	/**
	 * Use SSL to communicate with reCaptcha
	 */
	$wgDonationInterfaceRecaptchaUseSSL = 1;

	/**
	 * The # of times to retry communicating with reCaptcha if communication fails
	 * @var int
	 */
	$wgDonationInterfaceRecaptchaComsRetryLimit = 3;
}

/**
 * SPECIAL PAGES
 */

if ( $optionalParts['FormChooser'] === true ){
	$wgSpecialPages['GatewayFormChooser'] = 'GatewayFormChooser';
}
if ( $optionalParts['SystemStatus'] === true ){
	$wgSpecialPages['SystemStatus'] = 'SystemStatus';
}

//GlobalCollect gateway special pages
if ( $optionalParts['GlobalCollect'] === true ){
	$wgSpecialPages['GlobalCollectGateway'] = 'GlobalCollectGateway';
	$wgSpecialPages['GlobalCollectGatewayResult'] = 'GlobalCollectGatewayResult';
}
//PayflowPro gateway special pages
if ( $optionalParts['PayflowPro'] === true ){
	$wgSpecialPages['PayflowProGateway'] = 'PayflowProGateway';
}
//Amazon Simple Payment gateway special pages
if ( $optionalParts['Amazon'] === true ){
	$wgSpecialPages['AmazonGateway'] = 'AmazonGateway';
}
//Adyen gateway special pages
if ( $optionalParts['Adyen'] === true ){
	$wgSpecialPages['AdyenGateway'] = 'AdyenGateway';
	$wgSpecialPages['AdyenGatewayResult'] = 'AdyenGatewayResult';
}
//PayPal
if ( $optionalParts['Paypal'] === true ){
	$wgSpecialPages['PaypalGateway'] = 'PaypalGateway';
	$wgSpecialPages['PaypalGatewayResult'] = 'PaypalGatewayResult';
}

/**
 * HOOKS
 */

//Unit tests
$wgHooks['UnitTestsList'][] = 'efDonationInterfaceUnitTests';

//Stomp hooks
if ($optionalParts['Stomp'] === true){
	$wgHooks['ParserFirstCallInit'][] = 'efStompSetup';
	$wgHooks['gwStomp'][] = 'sendSTOMP';
	$wgHooks['gwPendingStomp'][] = 'sendPendingSTOMP';
	$wgHooks['gwLimboStomp'][] = 'sendLimboSTOMP';
}

//Custom Filters hooks
if ($optionalParts['CustomFilters'] === true){
	$wgHooks["GatewayValidate"][] = array( 'Gateway_Extras_CustomFilters::onValidate' );
}

//Referrer Filter hooks
if ( $optionalParts['ReferrerFilter'] === true ){
	$wgHooks["GatewayCustomFilter"][] = array( 'Gateway_Extras_CustomFilters_Referrer::onFilter' );
}

//Source Filter hooks
if ( $optionalParts['SourceFilter'] === true ){
	$wgHooks["GatewayCustomFilter"][] = array( 'Gateway_Extras_CustomFilters_Source::onFilter' );
} 

//Functions Filter hooks
if ( $optionalParts['FunctionsFilter'] === true ){
	$wgHooks["GatewayCustomFilter"][] = array( 'Gateway_Extras_CustomFilters_Functions::onFilter' );
} 

//Minfraud as Filter globals
if ( $optionalParts['Minfraud'] === true ){
	$wgHooks["GatewayCustomFilter"][] = array( 'Gateway_Extras_CustomFilters_MinFraud::onFilter' );
}

//Conversion Log hooks
if ($optionalParts['ConversionLog'] === true){
	// Sets the 'conversion log' as logger for post-processing
	$wgHooks["GatewayPostProcess"][] = array( "Gateway_Extras_ConversionLog::onPostProcess" );
}

//Recaptcha hooks
if ($optionalParts['Recaptcha'] === true){
	// Set reCpatcha as plugin for 'challenge' action
	$wgHooks["GatewayChallenge"][] = array( "Gateway_Extras_ReCaptcha::onChallenge" );
}

//Functions Filter hooks
if ( $optionalParts['IPVelocityFilter'] === true ){
	$wgHooks["GatewayCustomFilter"][] = array( 'Gateway_Extras_CustomFilters_IP_Velocity::onFilter' );
	$wgHooks["GatewayPostProcess"][] = array( 'Gateway_Extras_CustomFilters_IP_Velocity::onPostProcess' );
}

if ( $optionalParts['SessionVelocityFilter'] === true ) {
	$wgHooks['DonationInterfaceCurlInit'][] = array( 'Gateway_Extras_SessionVelocityFilter::onCurlInit' );
}

/**
 * APIS
 */
// enable the API
$wgAPIModules['donate'] = 'DonationApi';
$wgAutoloadClasses['DonationApi'] = $donationinterface_dir . 'gateway_common/donation.api.php';

//Payflowpro API
if ( $optionalParts['PayflowPro'] === true ){
	$wgAPIModules['pfp'] = 'ApiPayflowProGateway';
	$wgAutoloadClasses['ApiPayflowProGateway'] = $donationinterface_dir . 'payflowpro_gateway/api_payflowpro_gateway.php';
}


/**
 * ADDITIONAL MAGICAL GLOBALS
 */

// Resource modules
$wgResourceTemplate = array(
	'localBasePath' => $donationinterface_dir . 'modules',
	'remoteExtPath' => 'DonationInterface/modules',
);
$wgResourceModules['iframe.liberator'] = array(
	'scripts' => 'iframe.liberator.js',
	'position' => 'top'
	) + $wgResourceTemplate;

$wgResourceModules['donationInterface.skinOverride'] = array(
	'scripts' => 'js/skinOverride.js',
	'styles' => array(
		'css/gateway.css',
		'css/skinOverride.css',
	),
	'position' => 'top'
	) + $wgResourceTemplate;

// load any rapidhtml related resources
require_once( $donationinterface_dir . 'gateway_forms/rapidhtml/RapidHtmlResources.php' );


$wgResourceTemplate = array(
	'localBasePath' => $donationinterface_dir . 'gateway_forms',
	'remoteExtPath' => 'DonationInterface/gateway_forms',
);

$wgResourceModules[ 'ext.donationInterface.errorMessages' ] = array(
	'messages' => array(
		'donate_interface-noscript-msg',
		'donate_interface-noscript-redirect-msg',
		'donate_interface-error-msg-general',
		'donate_interface-error-msg-js',
		'donate_interface-error-msg-validation',
		'donate_interface-error-msg-invalid-amount',
		'donate_interface-error-msg-email',
		'donate_interface-error-msg-card-num',
		'donate_interface-error-msg-amex',
		'donate_interface-error-msg-mc',
		'donate_interface-error-msg-visa',
		'donate_interface-error-msg-discover',
		'donate_interface-error-msg-amount',
		'donate_interface-error-msg-emailAdd',
		'donate_interface-error-msg-fname',
		'donate_interface-error-msg-lname',
		'donate_interface-error-msg-street',
		'donate_interface-error-msg-city',
		'donate_interface-error-msg-state',
		'donate_interface-error-msg-zip',
		'donate_interface-error-msg-postal',
		'donate_interface-error-msg-country',
		'donate_interface-error-msg-card_type',
		'donate_interface-error-msg-card_num',
		'donate_interface-error-msg-expiration',
		'donate_interface-error-msg-cvv',
		'donate_interface-error-msg-fiscal_number',
		'donate_interface-error-msg-captcha',
		'donate_interface-error-msg-captcha-please',
		'donate_interface-error-msg-cookies',
		'donate_interface-error-msg-account_name',
		'donate_interface-error-msg-account_number',
		'donate_interface-error-msg-authorization_id',
		'donate_interface-error-msg-bank_check_digit',
		'donate_interface-error-msg-bank_code',
		'donate_interface-error-msg-branch_code',
		'donate_interface-smallamount-error',
		'donate_interface-donor-fname',
		'donate_interface-donor-lname',
		'donate_interface-donor-street',
		'donate_interface-donor-city',
		'donate_interface-donor-state',
		'donate_interface-donor-zip',
		'donate_interface-donor-postal',
		'donate_interface-donor-country',
		'donate_interface-donor-emailAdd',
		'donate_interface-state-province',
		'donate_interface-cvv-explain',
	)
);

// minimum amounts for all currencies
$wgResourceModules[ 'di.form.core.minimums' ] = array(
	'scripts' => 'validate.currencyMinimums.js',
	'localBasePath' => $donationinterface_dir . 'modules',
	'remoteExtPath' => 'DonationInterface/modules'
);

// form validation resource
$wgResourceModules[ 'di.form.core.validate' ] = array(
	'scripts' => 'validate_input.js',
	'dependencies' => array( 'di.form.core.minimums', 'ext.donationInterface.errorMessages' ),
	'localBasePath' => $donationinterface_dir . 'modules',
	'remoteExtPath' => 'DonationInterface/modules'
);

// form placeholders
//TODO: Move this somewhere gateway-agnostic.
$wgResourceModules[ 'pfp.form.core.placeholders' ] = array(
	'scripts' => 'form_placeholders.js',
	'dependencies' => 'di.form.core.validate',
	'messages' => array(
		'donate_interface-donor-fname',
		'donate_interface-donor-lname',
		'donate_interface-donor-street',
		'donate_interface-donor-city',
		'donate_interface-donor-state',
		'donate_interface-donor-postal',
		'donate_interface-donor-country',
		'donate_interface-donor-address',
	),
	'localBasePath' => $donationinterface_dir . 'payflowpro_gateway',
	'remoteExtPath' => 'DonationInterface/payflowpro_gateway',
);

// general PFP css
$wgResourceModules[ 'pfp.form.core.pfp_css' ] = array(
	'styles' => 'css/gateway.css',
	'scripts' => array(),
	'dependencies' => array(),
) + $wgResourceTemplate;

// TowStepTwoColumnLetter3
$wgResourceModules[ 'pfp.form.TwoStepTwoColumnLetter3' ] = array(
	'styles' => 'css/TwoStepTwoColumnLetter3.css',
	'dependencies' => 'di.form.core.validate',
) + $wgResourceTemplate;

// API JS
//TODO: Either move this somewhere gateway-agnostic, or move it to the pfp installer section.
$wgResourceModules[ 'pfp.form.core.api' ] = array(
	'scripts' => 'pfp_api_controller.js',
	'dependencies' => array( 'mediawiki.util', 'jquery.json' ),
	'localBasePath' => $donationinterface_dir . 'payflowpro_gateway',
	'remoteExtPath' => 'DonationInterface/payflowpro_gateway',
);


// Load the interface messages that are shared across multiple gateways
$wgExtensionMessagesFiles['DonateInterface'] = $donationinterface_dir . 'gateway_common/interface.i18n.php';
$wgExtensionMessagesFiles['DonateInterfaceAlt'] = $donationinterface_dir . 'gateway_common/country.specific.i18n.php';
$wgExtensionMessagesFiles['GatewayCountries'] = $donationinterface_dir . 'gateway_common/countries.i18n.php';
$wgExtensionMessagesFiles['GatewayUSStates'] = $donationinterface_dir . 'gateway_common/us-states.i18n.php';
$wgExtensionMessagesFiles['GatewayCAProvinces'] = $donationinterface_dir . 'gateway_common/canada-provinces.i18n.php';

//GlobalCollect gateway magical globals
//TODO: all the bits where we make the i18n make sense for multiple gateways. This is clearly less than ideal.
if ( $optionalParts['GlobalCollect'] === true ){
	$wgExtensionMessagesFiles['GlobalCollectGateway'] = $donationinterface_dir . 'globalcollect_gateway/globalcollect_gateway.i18n.php';
	$wgExtensionMessagesFiles['GlobalCollectGatewayAlias'] = $donationinterface_dir . 'globalcollect_gateway/globalcollect_gateway.alias.php';
}

//PayflowPro gateway magical globals
if ( $optionalParts['PayflowPro'] === true ){
	$wgExtensionMessagesFiles['PayflowProGateway'] = $donationinterface_dir . 'payflowpro_gateway/payflowpro_gateway.i18n.php';
	$wgExtensionMessagesFiles['PayflowProGatewayAlias'] = $donationinterface_dir . 'payflowpro_gateway/payflowpro_gateway.alias.php';
	$wgAjaxExportList[] = "fnPayflowProofofWork";
}

if ( $optionalParts['Adyen'] === true ){
	$wgExtensionMessagesFiles['AdyenGateway'] = $donationinterface_dir . 'adyen_gateway/adyen_gateway.i18n.php';
	$wgExtensionMessagesFiles['AdyenGatewayAlias'] = $donationinterface_dir . 'adyen_gateway/adyen_gateway.alias.php';
}

if ( $optionalParts['Paypal'] === true ){
	$wgExtensionMessagesFiles['PaypalGateway'] = $donationinterface_dir . 'paypal_gateway/paypal_gateway.i18n.php';
	$wgExtensionMessagesFiles['PaypalGatewayAlias'] = $donationinterface_dir . 'paypal_gateway/paypal_gateway.alias.php';
}

/**
 * FUNCTIONS
 */

//---Stomp functions---
if ($optionalParts['Stomp'] === true){
	require_once( $donationinterface_dir . 'activemq_stomp/activemq_stomp.php'  );
	$wgAutoloadClasses['Stomp'] = $donationinterface_dir . 'activemq_stomp/Stomp.php';
}

function efDonationInterfaceUnitTests( &$files ) {
	$files[] = dirname( __FILE__ ) . '/tests/AllTests.php';
	return true;
}

unset( $optionalParts );
