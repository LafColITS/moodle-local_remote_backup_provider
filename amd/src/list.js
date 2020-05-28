/**
 * Edit items in feedback module
 *
 * @module     local_remote_backup_provider/list
 * @package    local_remote_backup_provider
 * @copyright  2020 Wunderbyte GmbH
 */

define(['jquery'], function ($) {

    return {
        init: function () {
            //test
            $(".form-control").change(function ($value) {
                alert($value);
            });
        }
    };
});