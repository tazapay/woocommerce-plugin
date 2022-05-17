<?php
global $WCFM, $wp_query;
?>
<div class="collapse wcfm-collapse" id="wcfm_tazapay_information">
	<div class="wcfm-page-headig">
		<span class="fa fa-cubes"></span>
		<span class="wcfm-page-heading-text"><?php esc_html_e('Tazapay Information', 'wc-tp-payment-gateway'); ?></span>
		<?php do_action('wcfm_page_heading'); ?>
	</div>
	<div class="wcfm-collapse-content">
		<div id="wcfm_page_load"></div>
		<div class="wcfm-container wcfm-top-element-container">
			<h2><?php esc_html_e('Tazapay Information', 'wc-tp-payment-gateway'); ?></h2>
			<div class="wcfm-clearfix"></div>
		</div>
		<div class="wcfm-clearfix"></div><br />
		<div class="wcfm-container">
			<div id="wcfm_service_listing_expander" class="wcfm-content">
				<?php
				echo do_shortcode('[tazapay-account]');
				?>
				<div class="wcfm-clearfix"></div>
			</div>
			<div class="wcfm-clearfix"></div>
		</div>
		<div class="wcfm-clearfix"></div>
	</div>
</div>