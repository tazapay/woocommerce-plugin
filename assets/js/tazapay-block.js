const settings = window.wc.wcSettings.getSetting(
	"woocommerce_tz_tazapay_settings",
	{}
);
const Content = () => {
	return window.wp.htmlEntities.decodeEntities(settings.description || "");
};
const Block_Gateway = {
	name: "Tazapay",
	label: "Tazapay",
	content: Object(window.wp.element.createElement)(Content, null),
	edit: Object(window.wp.element.createElement)(Content, null),
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
