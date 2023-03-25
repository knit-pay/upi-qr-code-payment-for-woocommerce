( function( $ ) {
    "use strict";
    
    if ( typeof upiwcData === 'undefined' ) {
        return false;
    }

    let paymentLink = 'pay?pa=' + upiwcData.payee_vpa + '&pn=' + upiwcData.payee_name.replace(/\s/g, '') + '&am=' + upiwcData.order_amount + '&tr=' + upiwcData.order_key.replace( 'wc_order_', '' ).replace(/\s/g, '') + '&mc=' + upiwcData.mc_code + '&cu=INR&tn=OrderId:' + upiwcData.order_number.replace(/\s/g, '');
    paymentLink = encodeURI( paymentLink );

    $( 'body' ).on( 'contextmenu', '.upiwc-payment-qr-code img', function( e ) {
        return false;
    } );

    $( 'body' ).on( 'click', '#upiwc-cancel-payment', function( e ) {
        e.preventDefault();
        window.location = upiwcData.cancel_url;
    } );

    $( 'body' ).on( 'click', '.upiwc-return-link', function( e ) {
        e.preventDefault();
        window.location = upiwcData.payment_url;
    } );
    $( 'body' ).on( 'click', '#upiwc-confirm-payment', function( e ) {
        e.preventDefault();

        let amountContent = '<span class="payment-amount">₹ ' + upiwcData.order_amount + '</span>';
        if ( upiwcData.payer_vpa != '' ) {
            amountContent += '<span class="upi-id">' + upiwcData.payer_vpa + '</span>';
        }
        let paymentModal = $.confirm( {
            title: $( 'body' ).find( '.upiwc-modal-header' ).html(),
            content: $( 'body' ).find( '.upiwc-modal-content' ).html(),
            useBootstrap: false,
            animation: 'scale',
            boxWidth: '375px',
            draggable: false,
            offsetBottom: 38,
            offsetTop: 38,
            closeIcon: true,
            bgOpacity: .8,
            lazyOpen: true,
            theme: upiwcData.theme,
            onOpenBefore: function () {
                this.$el.addClass( 'upiwc-payment-modal' );
            },
            onContentReady: function () {
                let self = this;
                let timeoutIntent, timeoutCopy;

                self.$content.find( '.upiwc-payment-upi-id' ).on( 'click', function( e ) {
                    e.preventDefault();
                    clearTimeout( timeoutCopy );

                    navigator.clipboard.writeText( upiwcData.payee_vpa ).then( function() {
                        console.log( 'Copying to clipboard was successful!' );
                    }, function( err ) {
                        console.error( 'Could not copy text: ', err );
                    } );

                    let el = $( this )
                    el.text( 'Copied!' );
                    timeoutCopy = setTimeout( function() {
                        el.text( upiwcData.payee_vpa );
                    }, 1000 );
                } );

                self.$content.find( '#upiwc-payment-transaction-number' ).on( 'input', function( e ) {
                    self.$content.find( '.upiwc-payment-error' ).hide();
                } );

                let qrCodeSrc = self.$content.find( '#upiwc-payment-qr-code img' ).attr( 'src' );
                self.$content.find( '#upi-download' ).on( 'click', function( e ) {
                    e.preventDefault();

                    let a = document.createElement( 'a' ); //Create <a>
                    a.href = qrCodeSrc; //Image Base64 Goes here
                    a.download = "QR Code.png"; //File name Here
                    a.click();
                } );

                self.$content.find( '.upiwc-payment-btn' ).on( 'click', function( e ) {
                    e.preventDefault();
                    clearTimeout( timeoutIntent );
                    self.$content.find( '.upiwc-payment-intent-error' ).hide();

                    let type = $( this ).data( 'type' );
                    let paymentWindow = window.open( upiwcIntent( paymentLink, type ) );
                    timeoutIntent = setTimeout( function() {
                        if ( ! paymentWindow.closed ) {
                            paymentWindow.close();
                            self.$content.find( '.upiwc-payment-intent-error' ).text( 'No specified UPI App on this device. Select other UPI option to proceed.' ).show();
                        }  
                    }, 2500 ); 
                } );

                let btnShowInterval = parseInt( upiwcData.btn_show_interval );
                if ( btnShowInterval && btnShowInterval >= 1000 ) {
                    if ( upiwcData.btn_timer == 1 ) {
                        upiwcStartTimer( btnShowInterval / 1000, document.querySelector( '.btn.upiwc-next' ) );
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
                $( '#upiwc-confirm-payment, #upiwc-cancel-payment, .upiwc-return-link' ).show();
                $( '.upiwc-waiting-text' ).text( 'Please click the Pay Now button below to complete the payment against this order.' );
            },
            buttons: {
                amount: {
                    text: amountContent,
                    btnClass: 'upiwc-amount',
                    action: function() {
                        return false;
                    }
                },
                nextStep: {
                    text: 'Waiting...',
                    btnClass: 'upiwc-next',
                    isDisabled: true,
                    action: function() {
                        let self = this;
                        self.$content.find( '.upiwc-payment-confirm' ).show();
                        self.$content.find( '.upiwc-payment-info, .upiwc-payment-qr-code.upiwc-show, .upiwc-payment-actions, .upiwc-payment-container' ).hide();
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
                    btnClass: 'upiwc-back',
                    action: function() {
                        let self = this;
                        self.$content.find( '.upiwc-payment-confirm' ).hide();
                        self.$content.find( '.upiwc-payment-info, .upiwc-payment-qr-code.upiwc-show, .upiwc-payment-actions, .upiwc-payment-container' ).show();
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
                    btnClass: 'upiwc-confirm',
                    isHidden: true,
                    action: function() {
                        let self = this;

                        let tran_id = self.$content.find( '#upiwc-payment-transaction-number' ).val();
                        if ( tran_id !== undefined && typeof( tran_id ) !== 'undefined' ) {
                            if ( tran_id != '' && tran_id.length != 12 ) {
                                self.$content.find( '.upiwc-payment-error' ).text( 'Transaction ID should be of 12 digits!' ).show();
                                return false;
                            }
                            if ( upiwcData.transaction_id === 'show_require' && tran_id == '' ) {
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

                        $( '#upiwc-payment-success-container' ).html( '<form method="POST" action="' + upiwcData.callback_url + '" id="UPIJSCheckoutForm" style="display: none;"><input type="hidden" name="wc_order_id" value="' + upiwcData.order_id + '"><input type="hidden" name="wc_order_key" value="' + upiwcData.order_key + '">' + tran_id_field + '</form>' );
                        $( 'body' ).find( '#UPIJSCheckoutForm' ).submit();

                        return false;
                    }
                }
            }
        } );
        paymentModal.open();
        
        $( '#upiwc-processing' ).show();
        $( '#upiwc-confirm-payment, #upiwc-cancel-payment, .upiwc-return-link' ).hide();
        $( '.upiwc-waiting-text' ).text( 'Please wait and don\'t press back or refresh this page while we are processing your payment...' );
    } );

    if ( $( '#upiwc-payment-qr-code' ).length ) {
        new QRCode( 'upiwc-payment-qr-code', {
            text: 'upi://' + paymentLink,
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

function upiwcIntent( link, type ) {
    switch ( type ) {
        case 'gpay':
            prefix = 'gpay://upi/';
            break;
        case 'phonepe':
            prefix = 'phonepe://';
            break;
        case 'paytm':
            prefix = 'paytmmp://';
            break;
        default:
            prefix = 'upi://';
    }

    return prefix + link;
}

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
        diff = duration - ( ( ( Date.now() - start ) / 1000 ) | 0 );

        minutes = (diff / 60) | 0;
        seconds = (diff % 60) | 0;

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = 'Waiting... (' + minutes + ":" + seconds + ')'; 

        if ( diff <= 0 ) {
            start = Date.now() + 1000;
        }
    };

    clearTimeout( timerInterval )

    timer();
    
    timerInterval = setInterval( timer, 1000 );
    setTimeout( function() { 
        clearTimeout( timerInterval )
    }, duration * 1000 );
}