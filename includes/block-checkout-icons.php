<?php
$logoPath = TZP_PUBLIC_ASSETS_DIR . '/images/tazapay-logo-dark.png';
$paymentMethodsPath = TZP_PUBLIC_ASSETS_DIR . '/images/payment_methods.png';
?>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const checkElementAndAddIcons = () => {
      const labelElement = document.querySelector("#radio-control-wc-payment-method-options-tazapay__label");

      if (labelElement) {
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
        labelElement.insertAdjacentHTML('afterEnd', htmlContent);
      } else {
        // If the element is not found, try again after a short delay
        setTimeout(checkElementAndAddIcons, 100); // Check again after 100ms
      }
    };

    checkElementAndAddIcons(); // Initial check
  });

</script>