jQuery(document).ready(function() {
  jQuery('#xtype').on('change', function() {
    if(this.value == 'user_defined'){
      jQuery('#xtype-hidden').prop('type','text');
    }else{
      jQuery('#xtype-hidden').prop('type','hidden');
    }
  });
});