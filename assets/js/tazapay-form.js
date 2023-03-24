jQuery(document).ready(function($) {
    function toggelMode(){
        if( $("#woocommerce_tz_tazapay_select_env_mode").val() === 'Production' ){
            $(".tazapay_live_mode_fields").closest("tr").show();
            $(".tazapay_test_mode_fields").closest("tr").hide();
        }else{
            $(".tazapay_test_mode_fields").closest("tr").show();
            $(".tazapay_live_mode_fields").closest("tr").hide();
        }
    }
    toggelMode();
    $("#woocommerce_tz_tazapay_select_env_mode").change(function(){
        toggelMode();
    });
});
