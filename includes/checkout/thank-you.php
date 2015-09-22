<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Klarna Checkout Thank You page
 */

// Debug
if ( $this->debug == 'yes' ) {
	$this->log->add( 'klarna', 'Rendering Thank you page...' );
}

// Shared secret
$merchantId   = $this->klarna_eid;
$sharedSecret = $this->klarna_secret;
$orderUri     = $_GET['klarna_order'];

// Connect to Klarna
if ( $this->is_rest() ) {
	require_once( KLARNA_LIB . 'vendor/autoload.php' );
	if ( $this->testmode == 'yes' ) {
		if ( 'gb' == $this->klarna_country ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
		} elseif ( 'us' == $this->klarna_country ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
		}
	} else {
		if ( 'gb' == $this->klarna_country ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
		} elseif ( 'us' == $this->klarna_country ) {
			$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
		}
	}

	$connector = \Klarna\Rest\Transport\Connector::create(
		$merchantId,
		$sharedSecret,
		$klarna_server_url
	);
	$klarna_order = new Klarna\Rest\Checkout\Order( $connector, $orderUri );
} else {
	// Klarna_Checkout_Order::$contentType = 'application/vnd.klarna.checkout.aggregated-order-v2+json';  
	$connector = Klarna_Checkout_Connector::create( $sharedSecret );  
	$klarna_order = new Klarna_Checkout_Order( $connector, $orderUri );
}

try {
	$klarna_order->fetch();
} catch( Exception $e ) {
	if ( is_user_logged_in() && $this->debug ) {
		// The purchase was denied or something went wrong, print the message:
		echo '<div>';
		print_r( $e->getMessage() );
		echo '</div>';
	}
}

if ( $klarna_order['status'] == 'checkout_incomplete' ) {
	wp_redirect( $this->klarna_checkout_url );
	exit;  
}

// Display Klarna iframe
if ( $this->is_rest() ) {
	$snippet = "<div>{$klarna_order['html_snippet']}</div>";
} else {
	$snippet = '<div class="klarna-thank-you-snippet">' . $klarna_order['gui']['snippet'] . '</div>';	
}

do_action( 'klarna_before_kco_confirmation', $_GET['sid'] );
echo $snippet;	
do_action( 'klarna_after_kco_confirmation', $_GET['sid'] );
do_action( 'woocommerce_thankyou', $_GET['sid'] );

// Clear session and empty cart
WC()->session->__unset( 'klarna_checkout' );
WC()->session->__unset( 'klarna_checkout_country' );
WC()->session->__unset( 'ongoing_klarna_order' );
WC()->session->__unset( 'klarna_order_note' );
WC()->cart->empty_cart(); // Remove cart

/* DEBUG
$orderid = $_GET['sid'];
$billing_country = get_post_meta( $orderid, '_billing_country', true );
$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
if ( $this->testmode == 'yes' ) {
	if ( 'gb' == strtolower( $billing_country ) ) {
		$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
	} elseif ( 'us' == strtolower( $billing_country ) ) {
		$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
	}
} else {
	if ( 'gb' == strtolower( $billing_country ) ) {
		$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
	} elseif ( 'us' == strtolower( $billing_country ) ) {
		$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
	}
}

if ( 'gb' == strtolower( $billing_country ) ) {
	$connector = Klarna\Rest\Transport\Connector::create(
		$this->eid_uk,
		$this->secret_uk,
		$klarna_server_url
	);
} elseif ( 'us' == strtolower( $billing_country ) ) {
	$connector = Klarna\Rest\Transport\Connector::create(
		$this->eid_us,
		$this->secret_us,
		$klarna_server_url
	);
}
$k_order = new Klarna\Rest\OrderManagement\Order(
	$connector,
	$klarna_order_id
);

$k_order->fetch();
echo '<pre>';
print_r( $k_order );
echo '</pre>';
*/