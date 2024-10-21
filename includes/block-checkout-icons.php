<?php
$logoPath = TZP_PUBLIC_ASSETS_DIR . '/images/tazapay-logo-dark.png';
$paymentMethodsPath = TZP_PUBLIC_ASSETS_DIR . '/images/payment_methods.png';
?>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const htmlContent = `
        <div class="tw-mt-2 tw-flex tw-flex-wrap tw-gap-2">
            <div>
                <img src="<?php echo $logoPath; ?>" alt="tazapay" />
            </div>
            <div>
                <img src="<?php echo $paymentMethodsPath; ?>" alt="local payment" />
            </div>
        </div>
    `;

    const labelElement = document.querySelector("#radio-control-wc-payment-method-options-tazapay__label");
    if (labelElement) {
      labelElement.insertAdjacentHTML('afterEnd', htmlContent);
    }
  });

</script>