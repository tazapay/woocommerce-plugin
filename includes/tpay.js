
var tokenRequest = function() {

	// here will be a payment gateway function that process all the card data from your form,
	// maybe it will need your Publishable API key which is misha_params.publishableKey
	// and fires successCallback() on success and errorCallback on failure
	return false;
};

document.addEventListener('DOMContentLoaded', function() {
	if(tpay_params?.token){

		let style = null

		if(tpay_params.style){
			style = JSON.parse(tpay_params.style)
		}

		let c = document.createElement('DIV')
		c.setAttribute('id','tz-checkout')
		c.style.zIndex = '5000';

		document.querySelector('body').append(c)
		window.tazapay.checkout({
			clientToken: tpay_params.token,
			style: style,
			callbacks: {
				onPaymentSuccess: () => window.location.href = tpay_params.complete_url,
				// TODO: skip failure callback for payment retry
				onPaymentFail: () => window.location.href = tpay_params.complete_url,
				onPaymentCancel: () => window.location.href = tpay_params.abort_url,
			},
			config: {
				popup: true
			}
		})
	}
}, false);

