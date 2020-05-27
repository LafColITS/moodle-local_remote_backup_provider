define(['jquery'], function($, ajax) {
 
     
    return {
        init: function() {
            //test
            $(".form-control").change(function($value) {
                alert($value);
            });
        }
    };
});