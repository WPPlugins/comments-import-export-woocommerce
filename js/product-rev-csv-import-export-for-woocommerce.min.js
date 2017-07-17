jQuery(document).ready(function(a) {
    if(jQuery("#auto_import").val()=='Disabled')
    {
      jQuery(".import_section").hide();
    }
    else
    {
      jQuery(".import_section").show();
    }
    if(jQuery("#auto_export").val()=='Disabled')
    {
      jQuery(".export_section").hide();
    }
    else
    {
      jQuery(".export_section").show();
    }
    jQuery( "#datepicker1" ).datepicker({
        dateFormat: "yy-mm-dd",
      changeMonth: true,//this option for allowing user to select month
      changeYear: true //this option for allowing user to select from year range
    });
    jQuery( "#datepicker2" ).datepicker({
        dateFormat: "yy-mm-dd",
      changeMonth: true,//this option for allowing user to select month
      changeYear: true //this option for allowing user to select from year range
    });
    "use strict";
    a("select[name=auto_export]").change(function() {
        if("Disabled" === a(this).val()){
            a(".export_section").hide();
        }else{
            a(".export_section").show();
        }
    })
    // if(woocommerce_product_csv_importer_params.auto_export === 'Disabled'){
    //     a(".export_section").hide();
    // };
    a("select[name=auto_export]").change(function() {
        if("Disabled" === a(this).val()){
            a(".export_section").hide();
        }else{
            a(".export_section").show();
        }
    })

    a("select[name=auto_import]").change(function() {
        if("Disabled" === a(this).val()){
            a(".import_section").hide();
        }else{
            a(".import_section").show();
        }
    })
    // if(woocommerce_product_csv_importer_params.auto_import === 'Disabled'){
    //     a(".import_section").hide();
    // }
});