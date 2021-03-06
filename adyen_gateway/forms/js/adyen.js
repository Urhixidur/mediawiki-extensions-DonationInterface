window.displayCreditCardForm = function() {
	$( '#payment' ).empty();
	// Load wait spinner
	$( '#payment' ).append( '<br/><br/><br/><img alt="loading" src="'+mw.config.get( 'wgScriptPath' )+'/extensions/DonationInterface/gateway_forms/includes/loading-white.gif" />' );
	var language = 'en'; // default value is English
	var matches = document.location.href.match(/uselang=(\w+)/i); // fine the real language
	if ( matches && matches[1] ) {
		language = matches[1];
	}
	
	var currencyField = document.getElementById( 'input_currency_code' );
	var currency_code = '';
	if ( currencyField && currencyField.type == 'select-one' ) { // currency is a dropdown select
		currency_code = $( 'select#input_currency_code option:selected' ).val();
	} else {
		currency_code = $( "input[name='currency_code']" ).val();
	}
	
	var stateField = document.getElementById( 'state' );
	var state = '';
	if ( stateField && stateField.type == 'select-one' ) { // state is a dropdown select
		state = $( 'select#state option:selected' ).val();
	} else {
		state = $( "input[name='state']" ).val();
	}
	
	var countryField = document.getElementById( 'country' );
	var country = '';
	if ( countryField && countryField.type == 'select-one' ) { // country is a dropdown select
		country = $( 'select#country option:selected' ).val();
	} else {
		country = $( "input[name='country']" ).val();
	}
	
	var sendData = {
		'action': 'donate',
		'gateway': 'adyen',
		'currency_code': currency_code,
		'amount': $( "input[name='amount']" ).val(),
		'fname': $( "input[name='fname']" ).val(),
		'lname': $( "input[name='lname']" ).val(),
		'street': $( "input[name='street']" ).val(),
		'city': $( "input[name='city']" ).val(),
		'state': state,
		'zip': $( "input[name='zip']" ).val(),
		'emailAdd': $( "input[name='emailAdd']" ).val(),
		'country': country,
		'payment_method': 'cc',
		'language': language,
		'card_type': $( "input[name='cardtype']:checked" ).val().toLowerCase(),
		'contribution_tracking_id': $( "input[name='contribution_tracking_id']" ).val(),
		'numAttempt': $( "input[name='numAttempt']" ).val(),
		'utm_source': $( "input[name='utm_source']" ).val(),
		'utm_campaign': $( "input[name='utm_campaign']" ).val(),
		'utm_medium': $( "input[name='utm_medium']" ).val(),
		'referrer': $( "input[name='referrer']" ).val(),
		'recurring': $( "input[name='recurring']" ).val(),
		'format': 'json'
	};

	// If the field, street_supplemental, exists add it to sendData
	if ( $("input[name='street_supplemental']").length ) {
		sendData.street_supplemental = $( "input[name='street_supplemental']" ).val();
	}

	$.ajax( {
		'url': mw.util.wikiScript( 'api' ),
		'data': sendData,
		'dataType': 'json',
		'type': 'GET',
		'success': function( data ) {
			if ( typeof data.error !== 'undefined' ) {
				alert( mw.msg( 'donate_interface-error-msg-general' ) );
				$( "#paymentContinue" ).show(); // Show continue button in 2nd section
			} else if ( typeof data.result !== 'undefined' ) {
				if ( data.result.errors ) {
					var errors = new Array();
					$.each( data.result.errors, function( index, value ) {
						alert( value ); // Show them the error
						$( "#paymentContinue" ).show(); // Show continue button in 2nd section
					} );
				} else {
					if ( data.result.formaction && data.gateway_params ) {
						$( '#payment' ).empty();
						// Insert the iframe into the form
						$( '#payment' ).append(
							'<iframe width="600" height="514" frameborder="0" name="adyen-iframe"></iframe>'
						);
						var params = new Array();
						$.each( data.gateway_params, function( key, value ) {
							params.push('<input type="hidden" name="'+key+'" value="'+value+'" />');
						} );
						$( '#payment' ).append(
							'<form method="post" action="'+data.result.formaction+'" target="adyen-iframe" id="fetch-iframe-form">'+params.join()+'</form>'
						);
						$( '#payment #fetch-iframe-form' ).submit();
					}
				}
			}
		},
		'error': function( xhr ) {
			alert( mw.msg( 'donate_interface-error-msg-general' ) );
		}
	} );
}/*
 * The following variable are declared inline in webitects_2_3step.html:
 *   amountErrors, billingErrors, paymentErrors, scriptPath, actionURL
 */
$( document ).ready( function () {

	// check for RapidHtml errors and display, if any
	var amountErrorString = "";
	var billingErrorString = "";
	var paymentErrorString = "";

	// generate formatted errors to display
	var temp = [];
	for ( var e in amountErrors )
		if ( amountErrors[e] != "" )
			temp[temp.length] = amountErrors[e];
	amountErrorString = temp.join( "<br />" );

	temp = [];
	for ( var f in billingErrors )
		if ( billingErrors[f] != "" )
			temp[temp.length] = billingErrors[f];
	billingErrorString = temp.join( "<br />" );

	temp = [];
	for ( var g in paymentErrors )
		if ( paymentErrors[g] != "" )
			temp[temp.length] = paymentErrors[g];
	paymentErrorString = temp.join( "<br />" );

	// show the errors
	if ( amountErrorString != "" ) {
		$( "#topError" ).html( amountErrorString );
	} else if ( billingErrorString != "" ) {
		$( "#topError" ).html( billingErrorString );
	} else if ( paymentErrorString != "" ) {
		$( "#topError" ).html( paymentErrorString );
	}

    $( "#paymentContinueBtn" ).live( "click", function() {
        if ( validate_personal( document.payment ) && validateAmount() ) {
            $( "#payment" ).animate( { height:'314px' }, 1000 );
            displayCreditCardForm();
            // hide the continue button so that people don't get confused with two of them
            $( "#paymentContinue" ).hide();
        }
    } );

    // Set the cards to progress to step 3
    $( ".cardradio" ).live( "click", function() {
        if ( validate_personal( document.payment ) && validateAmount() ) {
            $( "#payment" ).animate( { height:'314px' }, 1000 );
            displayCreditCardForm();
            // hide the continue button so that people don't get confused with two of them
            $( "#paymentContinue" ).hide();
        }
        else {
            // show the continue button to indicate how to get to step 3 since they
            // have already clicked on a card image
            $( "#paymentContinue" ).show();
        }
    } );
} );
