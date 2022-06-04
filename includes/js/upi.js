jQuery(document).ready(function($) {

    if ( typeof upiwc_params === 'undefined' ) {
        return false;
    }

    if ( upiwc_params.prevent_reload == 1 ) {
        window.onbeforeunload = function() {
            return "Are you sure you want to leave?";
        }
    }

    if ( upiwc_params.is_mobile == 'yes' ) {
        var qr_size = 140;
        var pay_size = '100%';
        var confirm_size = '100%';
        var redirect_size = '95%';
        var offset = 0;
    } else {
        var qr_size = 250;
        var pay_size = '60%';
        var confirm_size = '50%';
        var redirect_size = '40%';
        var offset = 40;
    }

    //var theme = $( '#upiwc-confirm-payment' ).data( 'theme' );
    var payment_link = 'upi://pay?pa=' + upiwc_params.payee_vpa + '&pn=' + upiwc_params.payee_name + '&am=' + upiwc_params.order_amount.replace( '.00', '' ) + '&tr=' + upiwc_params.order_key.replace( 'wc_order_', '' ) + '&mc=' + upiwc_params.mcc + '&cu=INR&tn=ORDER ID ' + upiwc_params.order_number;
    var elText = encodeURI( payment_link );

    if( $('#upiwc-qrcode').length ) {
        new QRCode( 'upiwc-qrcode', {
            text: elText,
            width: qr_size,
            height: qr_size,
            correctLevel: QRCode.CorrectLevel.H,
            quietZone: 8,
            onRenderingEnd: function() {
                $( '#upiwc-confirm-payment' ).trigger( 'click' );
                /*$( '#upi-copy-link' ).click(function(e) {
                    var $temp = $( '<input>' );
                    $( 'body').append( $temp );
                    $temp.val( upiwc_params.payee_vpa ).select();
                    document.execCommand( 'copy' );
                    $temp.remove();
                    e.preventDefault();
                });*/
            },
        } );
    } //else {*/
        //if ( upiwc_params.qrcode_mobile != 'no' ) {
           // $("#upiwc-confirm-payment").trigger("click");
        //}
    //}

    $( 'body' ).on( 'contextmenu', '#upiwc-qrcode img', function( e ) {
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
        
        var colorTheme = $( this ).data( 'theme' );
        var cd = $.confirm( {
            title: '<span class="upiwc-popup-title-' + upiwc_params.app_theme + '">Scan this QR Code</span>',
            content: $( 'body' ).find( '#js_qrcode' ).html(),
            useBootstrap: false,
            theme: upiwc_params.app_theme,
            animation: 'scale',
            type: colorTheme,
            boxWidth: pay_size,
            draggable: false,
            offsetBottom: offset,
            offsetTop: offset,
            closeIcon: true,
            onContentReady: function () {
                var self = this;
                this.$content.find( '.btn-upi-copy' ).on( 'click', function( e ) {
                    e.preventDefault();

                    navigator.clipboard.writeText( upiwc_params.payee_vpa ).then( function() {
                        console.log( 'Async: Copying to clipboard was successful!' );
                    }, function( err ) {
                        console.error( 'Async: Could not copy text: ', err );
                    } );

                    $btn = self.$content.find( '.btn-upi-copy' );
                    $old_value = $btn.text();
                    $btn.text( 'Copied !' );
                    setTimeout( function() {
                        $btn.text( $old_value );
                    }, 1000 );
                } );

                this.$content.find( '.btn-upi-pay' ).on( 'click', function( e ) {
                    e.preventDefault();
                    window.onbeforeunload = null;
                    window.location = elText;
                } );

                var qr_code = this.$content.find( '#upiwc-qrcode img' ).attr( 'src' );
                this.$content.find( '.btn-upi-download' ).on( 'click', function( e ) {
                    e.preventDefault();
                    var a = document.createElement( 'a' ); //Create <a>
                    a.href = qr_code; //Image Base64 Goes here
                    a.download = "QR Code.png"; //File name Here
                    a.trigger( 'click' );
                } );

                setTimeout( function() {
                    self.buttons.nextStep.show();
                }, upiwc_params.btn_show_interval );
            },
            //containerFluid: true,
            onClose: function () {
                $( '#upiwc-processing' ).hide();
                $( '.upiwc-buttons, .upiwc-return-link' ).fadeIn( 'slow' );
                $( '.upiwc-waiting-text' ).text( 'Please click the Pay Now button below to complete the payment against this order.' );
            },
            buttons: {
                nextStep: {
                    text: 'Proceed to Next Step',
                    btnClass: 'btn-' + colorTheme,
                    isHidden: true,
                    action: function() {
                        cd.toggle();
                        $( '#upiwc-processing' ).show();
                        $( '.upiwc-buttons, .upiwc-return-link' ).hide();
                        $( '.upiwc-waiting-text' ).text( 'Please wait and don\'t press back or refresh this page while we are processing your payment...' );
                        $.confirm( {
                            title: '<span class="upiwc-popup-title-' + upiwc_params.app_theme + '">Confirm your Payment!</span>',
                            content: upiTransactionIDField() + '<div id="upiwc-confirm-text" class="upiwc-confirm-text">' + upiwc_params.confirm_message + '</div>',
                            useBootstrap: false,
                            theme: upiwc_params.app_theme,
                            type: colorTheme,
                            boxWidth: redirect_size,
                            draggable: false,
                            buttons: {
                                confirm: {
                                    text: 'Confirm',
                                    btnClass: 'btn-' + colorTheme,
                                    action: function() {
                                        var tran_id = this.$content.find('#upiwc-transaction-number').val();
                                        if( upiwc_params.transaction_id == 'show_require' ) {
                                            if( tran_id == '' || ( tran_id.length != upiwc_params.tran_id_length ) ) {
                                                $.alert( {
                                                    title: '<span class="upiwc-popup-title-' + upiwc_params.app_theme + '">Error!</span>',
                                                    content: '<div id="upiwc-error-text">Please enter a valid Transaction / UTR / Reference ID and try again.</div>',
                                                    useBootstrap: false,
                                                    draggable: false,
                                                    theme: upiwc_params.app_theme,
                                                    type: 'red',
                                                    boxWidth: redirect_size,
                                                } );
                                                return false;
                                            }
                                        }

                                        var tran_id_field = '';
                                        if ( tran_id != '' ) {
                                            tran_id_field = '<input type="hidden" name="wc_transaction_id" value="' + tran_id + '"></input>';
                                        }

                                        window.onbeforeunload = null;

                                        $( '#payment-success-container' ).html( '<form method="POST" action="' + upiwc_params.callback_url + '" id="UPIJSCheckoutForm" style="display: none;"><input type="hidden" name="wc_order_id" value="' + upiwc_params.order_id + '"><input type="hidden" name="wc_order_key" value="' + upiwc_params.order_key + '">' + tran_id_field + '</form>');
                                        
                                        $.dialog( {
                                            title: '<span class="upiwc-popup-title-' + upiwc_params.app_theme + '">Processing...<span>',
                                            content: '<div id="upiwc-confirm-text" class="upiwc-confirm-text">' + upiwc_params.processing_text + '</div>',
                                            useBootstrap: false,
                                            draggable: false,
                                            theme: upiwc_params.app_theme,
                                            type: 'blue',
                                            closeIcon: false,
                                            offsetBottom: offset,
                                            offsetTop: offset,
                                            boxWidth: confirm_size,
                                        } );

                                        $( '#UPIJSCheckoutForm' ).submit();
                                    }
                                },
                                goBack: {
                                    text: 'Go Back',
                                    action: function() {
                                        cd.toggle();
                                    }
                                }
                            }
                        });
                        return false;
                    }
                }
            }
        } );
        $( '#upiwc-processing' ).show();
        $( '.upiwc-buttons, .upiwc-return-link' ).hide();
        $( '.upiwc-waiting-text' ).text( 'Please wait and don\'t press back or refresh this page while we are processing your payment...' );
    } );
} );

function upiTransactionIDField() {
    var d = new Date(),
    required = content = '';

    if( upiwc_params.transaction_id == 'show_require' ) {
        required = '<span class="upiwc-required">*</span>';
    }

    if( upiwc_params.transaction_id != 'hide' ) {
       var css_class = 'upiwc-' + upiwc_params.app_theme + '-input';
       content = '<form id="upiwc-form"><div class="upiwc-form-group"><label for="upiwc-transaction-number"><strong>' + upiwc_params.transaction_text + '</strong> ' + required + '</label><div class="upiwc-clear-area"></div>' +
       '<input type="text" id="upiwc-transaction-number" class="' + css_class + '" placeholder="e.g. ' + d.getFullYear().toString().slice(-1) + '01422121258 (starts with ' + d.getFullYear().toString().slice(-1) + ')" maxlength="12" style="width: 60%;" onkeypress="return isNumber(event)" /></div>' +
       '<div class="upiwc-clear-area"></div></div>';
    } 
    return content;
}

function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (8 != charCode && 0 != charCode && charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}