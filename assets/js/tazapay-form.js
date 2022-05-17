jQuery(document).ready(function() {
    jQuery("#individual").hide();
    jQuery("#business").hide();

    jQuery("#indbustype").on("change", function() {
        if (this.value == "Individual") {
            jQuery("#individual").show();
            jQuery("#business").hide();
        } else if (this.value == "Business") {
            jQuery("#business").show();
            jQuery("#individual").hide();
        } else {
            jQuery("#individual").hide();
            jQuery("#business").hide();
        }
    });
});

jQuery(function() {
    jQuery("form[name='accountform']").validate({
        rules: {
            first_name: "required",
            last_name: "required",
            phone_number: "required",
            indbustype: "required",
            country: "required",
            business_name: "required",
            email: {
                required: true,
                email: true,
            },
        },
        messages: {
            first_name: "Please enter your firstname",
            last_name: "Please enter your lastname",
            phone_number: "Please enter your phonenumber",
            email: "Please enter a valid email address",
            indbustype: "Please select Ind Bus Type",
            country: "Please select country",
            business_name: "Please enter your businessname",
        },
        submitHandler: function(form) {
            form.submit();
        },
    });
});

jQuery(function() {
    var inputs = document.getElementsByTagName("INPUT");
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].oninvalid = function(e) {
            e.target.setCustomValidity("");
            if (!e.target.validity.valid) {
                e.target.setCustomValidity(e.target.getAttribute("data-error"));
            }
        };
    }
});

jQuery(document).ready(function($) {
    if (
        $("#woocommerce_tz_tazapay_tazapay_seller_type").val() == "singleseller"
    ) {
        $(".tazapay-multiseller").closest("tr").hide();
    }
    if ($("#woocommerce_tz_tazapay_tazapay_seller_type").val() == "multiseller") {
        $(".tazapay-multiseller").closest("tr").show();
    }
    $("#woocommerce_tz_tazapay_seller_id").attr("readonly", true);

    $("#woocommerce_tz_tazapay_title").attr("required", true);
    $("#woocommerce_tz_tazapay_title").attr("data-error", "Please add title");
    $("#woocommerce_tz_tazapay_seller_email").attr("required", true);
    $("#woocommerce_tz_tazapay_seller_email").attr(
        "data-error",
        "Please input the platform's email id"
    );
    if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "sandbox") {
        $(".tazapay-production").closest("tr").hide();

        $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", true);
        $("#woocommerce_tz_tazapay_sandbox_api_key").attr(
            "data-error",
            "Please add Sandbox API"
        );
        $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr("required", true);
        $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
            "data-error",
            "Please add Sandbox API Secret Key"
        );
    }
    if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "production") {
        $(".tazapay-sandbox").closest("tr").hide();

        $("#woocommerce_tz_tazapay_live_api_key").attr("required", true);
        $("#woocommerce_tz_tazapay_live_api_key").attr(
            "data-error",
            "Please add Production API"
        );
        $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", true);
        $("#woocommerce_tz_tazapay_live_api_secret_key").attr(
            "data-error",
            "Please add Production API Secret Key"
        );
    }

    $("#woocommerce_tz_tazapay_sandboxmode").change(function() {
        if (this.value == "sandbox") {
            $(".tz-signupurl").attr("href", "https://sandbox.tazapay.com/signup");
            $(".signup-help-text").text(
                "Request Sandbox credentials for accepting payments via Tazapay. Signup now and go to 'Request API Key'"
            );

            $(".tazapay-sandbox").closest("tr").show();
            $(".tazapay-production").closest("tr").hide();

            $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", true);
            $("#woocommerce_tz_tazapay_sandbox_api_key").attr(
                "data-error",
                "Please add Sandbox API"
            );
            $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
                "required",
                true
            );
            $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
                "data-error",
                "Please add Sandbox API Secret Key"
            );

            $("#woocommerce_tz_tazapay_live_api_key").attr("required", false);
            $("#woocommerce_tz_tazapay_live_api_key").attr("data-error", "");
            $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", false);
            $("#woocommerce_tz_tazapay_live_api_secret_key").attr("data-error", "");
        }
        if (this.value == "production") {
            $(".tz-signupurl").attr("href", "https://app.tazapay.com/signup");
            $(".signup-help-text").text(
                "Request Production credentials for accepting payments via Tazapay. Signup now and go to 'Request API Key'"
            );

            $(".tazapay-sandbox").closest("tr").hide();
            $(".tazapay-production").closest("tr").show();

            $("#woocommerce_tz_tazapay_live_api_key").attr("required", true);
            $("#woocommerce_tz_tazapay_live_api_key").attr(
                "data-error",
                "Please add Production API"
            );
            $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", true);
            $("#woocommerce_tz_tazapay_live_api_secret_key").attr(
                "data-error",
                "Please add Production API Secret Key"
            );

            $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", false);
            $("#woocommerce_tz_tazapay_sandbox_api_key").attr("data-error", "");
            $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
                "required",
                false
            );
            $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
                "data-error",
                ""
            );
        }
    });

    $("#woocommerce_tz_tazapay_tazapay_seller_type").change(function() {
        if (this.value == "singleseller") {
            $(".tazapay-multiseller").closest("tr").hide();
        }
        if (this.value == "multiseller") {
            $(".tazapay-multiseller").closest("tr").show();
        }
    });
});