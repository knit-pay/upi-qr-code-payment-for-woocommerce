( function( $ ) {
    "use strict";

    if ( typeof upiwc_params === 'undefined' ) {
        return false;
    }

    if ( upiwc_params.prevent_reload == 1 ) {
        window.onbeforeunload = function() {
            return "Are you sure you want to leave?";
        }
    }

    let modalSize = '375px';
    let offset = 40;
    if ( upiwc_params.is_mobile == 'yes' ) {
        modalSize = '100%';
        offset = 0;
    }

    let payment_link = 'upi://pay?pa=' + upiwc_params.payee_vpa + '&pn=' + upiwc_params.payee_name + '&am=' + upiwc_params.order_amount + '&tr=' + upiwc_params.order_key.replace( 'wc_order_', '' ) + '&mc=' + upiwc_params.mc_code + '&cu=INR&tn=ORDER ID ' + upiwc_params.order_number;
    let elText = encodeURI( payment_link );

    $( 'body' ).on( 'contextmenu', '#upiwc-payment-qr-code img', function( e ) {
        return false;
    } );

    $( 'body' ).on( 'click', '#upiwc-cancel-payment', function( e ) {
        e.preventDefault();
        window.onbeforeunload = null;
        window.location = upiwc_params.cancel_url;
    } );

    $( 'body' ).on( 'click', '.upiwc-return-link', function( e ) {
        e.preventDefault();
        window.onbeforeunload = null;
        window.location = upiwc_params.payment_url;
    } );
    $( 'body' ).on( 'click', '#upiwc-confirm-payment', function( e ) {
        e.preventDefault();

        let amountContent = '<span class="payment-amount">₹ ' + upiwc_params.order_amount + '</span>';
        if ( upiwc_params.payer_vpa != '' ) {
            amountContent += '<span class="upi-id">' + upiwc_params.payer_vpa + '</span>';
        }
        let paymentModal = $.confirm( {
            title: $( 'body' ).find( '.upiwc-modal-header' ).html(),
            content: $( 'body' ).find( '.upiwc-modal-content' ).html(),
            useBootstrap: false,
            animation: 'scale',
            boxWidth: modalSize,
            draggable: false,
            offsetBottom: offset,
            offsetTop: offset,
            closeIcon: true,
            onContentReady: function () {
                let self = this;
                let timeout;

                self.$content.find( '.upiwc-payment-upi-id' ).on( 'click', function( e ) {
                    e.preventDefault();
                    clearTimeout( timeout );

                    navigator.clipboard.writeText( upiwc_params.payee_vpa ).then( function() {
                        console.log( 'Async: Copying to clipboard was successful!' );
                    }, function( err ) {
                        console.error( 'Async: Could not copy text: ', err );
                    } );

                    self.$content.find( '.upiwc-payment-upi-id' ).text( 'Copied!' );
                    timeout = setTimeout( function() {
                        self.$content.find( '.upiwc-payment-upi-id' ).text( upiwc_params.payee_vpa );
                    }, 1000 );
                } );

                self.$content.find( '#upiwc-payment-transaction-number' ).on( 'input', function( e ) {
                    self.$content.find( '.upiwc-payment-error' ).hide();
                } );

                self.$content.find( '#upi-pay' ).on( 'click', function( e ) {
                    e.preventDefault();
                    window.onbeforeunload = null;
                    window.open( elText );
                } );

                let qr_code = self.$content.find( '#upiwc-payment-qr-code img' ).attr( 'src' );
                self.$content.find( '#upi-download' ).on( 'click', function( e ) {
                    e.preventDefault();

                    let a = document.createElement( 'a' ); //Create <a>
                    a.href = qr_code; //Image Base64 Goes here
                    a.download = "QR Code.png"; //File name Here
                    a.click();
                } );

                if ( upiwc_params.can_intent ) {
                    setTimeout( function() {
                        window.onbeforeunload = null;
                        window.open( elText );
                    }, 1000 );
                }

                let btnShowInterval = parseInt( upiwc_params.btn_show_interval );
                if ( btnShowInterval && btnShowInterval >= 1000 ) {
                    if ( upiwc_params.btn_timer == 1 ) {
                        upiwcStartTimer( btnShowInterval / 1000, document.querySelector( '.btn.next' ) );
                    }
                    setTimeout( function() {
                        self.buttons.nextStep.setText( 'Proceed to Next' );
                        self.buttons.nextStep.enable();
                    }, btnShowInterval );
                } else {
                    self.buttons.nextStep.setText( 'Proceed to Next' );
                    self.buttons.nextStep.enable();
                }
            },
            onClose: function () {
                $( '#upiwc-processing' ).hide();
                $( '#upiwc-confirm-payment, #upiwc-cancel-payment, .upiwc-return-link' ).fadeIn( 'slow' );
                $( '.upiwc-waiting-text' ).text( 'Please click the Pay Now button below to complete the payment against this order.' );
            },
            buttons: {
                amount: {
                    text: amountContent,
                    btnClass: 'amount',
                    action: function() {
                        return false;
                    }
                },
                nextStep: {
                    text: 'Waiting...',
                    btnClass: 'next',
                    isDisabled: true,
                    action: function() {
                        let self = this;
                        self.$content.find( '.upiwc-payment-confirm' ).show();
                        self.$content.find( '.upiwc-payment-info, .upiwc-payment-qr-code' ).hide();
                        self.$closeIcon.hide();

                        self.buttons.amount.hide();
                        self.buttons.nextStep.hide();
                        self.buttons.back.show();
                        self.buttons.confirm.show();

                        return false;
                    }
                },
                back: {
                    text: 'Back',
                    isHidden: true,
                    btnClass: 'back',
                    action: function() {
                        let self = this;
                        self.$content.find( '.upiwc-payment-confirm' ).hide();
                        self.$content.find( '.upiwc-payment-info, .upiwc-payment-qr-code' ).show();
                        self.buttons.amount.show();
                        self.buttons.nextStep.show();
                        self.buttons.back.hide();
                        self.buttons.confirm.hide();
                        self.$closeIcon.show();

                        return false;
                    }
                },
                confirm: {
                    text: 'Confirm',
                    isHidden: true,
                    action: function() {
                        let self = this;

                        let tran_id = self.$content.find( '#upiwc-payment-transaction-number' ).val();
                        if ( tran_id !== undefined && typeof( tran_id ) !== 'undefined' ) {
                            if ( tran_id != '' && tran_id.length != 12 ) {
                                self.$content.find( '.upiwc-payment-error' ).text( 'Transaction ID should be of 12 digits!' ).show();
                                return false;
                            }
                            if ( upiwc_params.transaction_id === 'show_require' && tran_id == '' ) {
                                self.$content.find( '.upiwc-payment-error' ).text( 'Transaction ID is required!' ).show();
                                return false;
                            }
                        }

                        self.buttons.confirm.disable();
                        self.buttons.back.disable();
                        self.buttons.confirm.setText( 'Processing...' );

                        let tran_id_field = '';
                        if ( tran_id !== undefined && typeof( tran_id ) !== 'undefined' && tran_id != '' ) {
                            tran_id_field = '<input type="hidden" name="wc_transaction_id" value="' + tran_id + '"></input>';
                        }
                        window.onbeforeunload = null;

                        $( '#upiwc-payment-success-container' ).html( '<form method="POST" action="' + upiwc_params.callback_url + '" id="UPIJSCheckoutForm" style="display: none;"><input type="hidden" name="wc_order_id" value="' + upiwc_params.order_id + '"><input type="hidden" name="wc_order_key" value="' + upiwc_params.order_key + '">' + tran_id_field + '</form>' );
                        $( 'body' ).find( '#UPIJSCheckoutForm' ).submit();

                        return false;
                    }
                }
            }
        } );
        
        $( '#upiwc-processing' ).show();
        $( '#upiwc-confirm-payment, #upiwc-cancel-payment, .upiwc-return-link' ).hide();
        $( '.upiwc-waiting-text' ).text( 'Please wait and don\'t press back or refresh this page while we are processing your payment...' );
    } );

    if ( $( '#upiwc-payment-qr-code' ).length ) {
        new QRCode( 'upiwc-payment-qr-code', {
            text: elText,
            width: 200,
            height: 200,
            correctLevel: QRCode.CorrectLevel.H,
            quietZone: 8,
            onRenderingEnd: function() {
                $( '#upiwc-confirm-payment' ).trigger( 'click' );
            },
        } );
    } else {
        $( '#upiwc-confirm-payment' ).trigger( 'click' );
    }
} )( jQuery );

function upiwcIsNumber( evt ) {
    evt = (evt) ? evt : window.event;
    let charCode = (evt.which) ? evt.which : evt.keyCode;
    if (8 != charCode && 0 != charCode && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

function upiwcStartTimer( duration, display ) {
    let start = Date.now(),
        diff,
        minutes,
        seconds,
        timerInterval;

    function timer() {
        // get the number of seconds that have elapsed since 
        // upiwcStartTimer() was called
        diff = duration - ( ( ( Date.now() - start ) / 1000 ) | 0 );

        // does the same job as parseInt truncates the float
        minutes = (diff / 60) | 0;
        seconds = (diff % 60) | 0;

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds; 

        if ( diff <= 0 ) {
            // add one second so that the count down starts at the full duration
            // example 05:00 not 04:59
            start = Date.now() + 1000;
        }
    };

    clearTimeout( timerInterval )

    // we don't want to wait a full second before the timer starts
    timer();
    
    timerInterval = setInterval( timer, 1000 );
    setTimeout( function() { 
        clearTimeout( timerInterval )
    }, duration * 1000 );
}