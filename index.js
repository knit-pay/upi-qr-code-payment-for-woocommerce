import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect, useState } from '@wordpress/element'
import CreatableSelect from 'react-select/creatable';

const settings = getSetting( 'wc-upi_data', {} );
const label = decodeEntities( settings.title ) || 'Pay via UPI QR Code';

const Content = () => {
	return decodeEntities( settings.description || '' );
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

const Form = ( props ) => {
	const { eventRegistration, emitResponse } = props;
	const { onPaymentSetup } = eventRegistration;
	const [ upiAddress, setUpiAddress ] = useState( '' );
	const [ upiHandle, setUpiHandle ] = useState( '' );

	useEffect( () => {
		const unsubscribe = onPaymentSetup( async () => {
			return {
				type: emitResponse.responseTypes.SUCCESS,
				meta: {
					paymentMethodData: {
						'customer_upiwc_address': upiAddress,
						'customer_upiwc_handle': upiHandle?.value,
					},
				},
			};
		} );

		return () => {
			unsubscribe();
		};
	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		onPaymentSetup,
		upiAddress,
		upiHandle
	] );

	return(
		<>
			<Content />
			<div className="upiwc-input block">
				<label>UPI Address {settings?.require_upi && <span class="required">*</span>}</label>
				<div className="upiwc-input-field">
					<input id="upiwc-address" pattern="[a-zA-Z0-9]+" className="upiwc-address block" type="text" autocomplete="off" placeholder={`e.g. ${settings?.placeholder}`} value={upiAddress} onChange={e => setUpiAddress(e.target.value)} />
					{ settings?.upi_address === 'show_handle' && 
						<CreatableSelect 
							isClearable
							value={ upiHandle }
							options={ settings?.handles }
							onChange={value => setUpiHandle( value )}
							className="upiwc-upi-handle"
							styles={{
								container: (baseStyles, state) => ({
									...baseStyles,
									width: '80%',
            						outline: 'none !important'
								}),
								control: (baseStyles, state) => ({
								   ...baseStyles,
								  	boxShadow: 'none',
									outline: 'none',
									borderLeft: '0',
									borderBottomLeftRadius: '0',
									borderTopLeftRadius: '0',
									borderColor: '#d0d0d0 !important',
									backgroundColor: 'transparent'
								}),
								valueContainer: (baseStyles, state) => ({
									...baseStyles,
									padding: '0 0 0 6px',
									fontSize: '13px',
								}),
								input: (baseStyles, state) => ({
									...baseStyles,
									padding: '0',
                					margin: '0',
								}),
								menu: (baseStyles, state) => ({
									...baseStyles,
									fontSize: '13px',
								}),
							}}
						/>
					}
				</div>
			</div>
		</>
	)
}

const UPIQRCode = {
	name: "wc-upi",
	label: <Label />,
	content: settings?.upi_address === 'hide' ? <Content /> : <Form />,
	edit: <Content />,
	placeOrderButtonLabel: settings?.button_text,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( UPIQRCode );