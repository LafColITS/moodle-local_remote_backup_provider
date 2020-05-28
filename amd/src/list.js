/**
 * Edit items in feedback module
 *
 * @module     local_remote_backup_provider/list
 * @package    local_remote_backup_provider
 * @copyright  2020 Wunderbyte GmbH
 */

define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification) {

    return {
        init: function () {
            //we add listener to dropdown function
            $(".rbp_dropdown").change(function () {

                //collect all the values
                var firstusername = $('#rbp_userrow_' + this.id + '_username').text();

                alert("change " + this.id + " - " + firstusername);
            });

            $(".rbp_delete").click(function () {

                var id = this.id;
                ajax.call([{
                    methodname: "local_remote_backup_provider_delete_user_entry_from_backup",
                    args: {'id': this.id},
                    done: function () {
                        $('#rbp_userrow_' + id).remove();
                        alert("we have deleted "+id);
                    },
                    fail: notification.exception
                }]);


            });
        }
    };
});