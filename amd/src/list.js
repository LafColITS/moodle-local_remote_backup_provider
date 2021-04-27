/**
 * Edit items in feedback module
 *
 * @module     local_remote_backup_provider/list
 * @package    local_remote_backup_provider
 * @copyright  2020 Wunderbyte GmbH
 */

define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification) {
    var exportTableToCSV = function ($table, filename) {

        var $rows = $table.find('tr:has(td)'),

            // Temporary delimiter characters unlikely to be typed by keyboard
            // This is to avoid accidentally splitting the actual contents
            tmpColDelim = String.fromCharCode(11), // vertical tab character
            tmpRowDelim = String.fromCharCode(0), // null character

            // actual delimiter characters for CSV format
            colDelim = '","',
            rowDelim = '"\r\n"',

            // Grab text from table into CSV formatted string
            csv = '"' + $rows.map(function (i, row) {
                var $row = $(row),
                    $cols = $row.find('td.includeincsv');

                return $cols.map(function (j, col) {
                    var $col = $(col),
                        text = $col.text();

                    return text.replace(/"/g, '""'); // escape double quotes

                }).get().join(tmpColDelim);

            }).get().join(tmpRowDelim)
                .split(tmpRowDelim).join(rowDelim)
                .split(tmpColDelim).join(colDelim) + '"';

        if (window.navigator.msSaveBlob) {

            var blob = new Blob([decodeURIComponent(csv)], {
                type: 'text/csv;charset=utf8'
            });

            window.navigator.msSaveBlob(blob, filename);

        } else if (window.Blob && window.URL) {
            // HTML5 Blob
            var blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8'
            });
            var csvUrl = URL.createObjectURL(blob);

            $(this)
                .attr({
                    'download': filename,
                    'href': csvUrl
                });
        } else {
            // Data URI
            var csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

            $(this)
                .attr({
                    'download': filename,
                    'href': csvData,
                    'target': '_blank'
                });
        }
    };

    return {
        init: function () {
            //we add listener to dropdown function
            $(".rbp_dropdown").change(function () {

                //collect all the values
                var optionaluserelement = $(this).find('.optionvalue:selected');
                var firstuserelement = $('#rbp_userrow_' + this.id);
                var restoreid = $('#tabletoexport').data('restoreid');

                var optionaluser = {
                    'id': $(this).val(),
                    'username': optionaluserelement.data('username'),
                    'firstname': optionaluserelement.data('firstname'),
                    'lastname': optionaluserelement.data('lastname'),
                    'useremail': optionaluserelement.data('useremail')
                };

                var firstuser = {
                    'id': this.id,
                    'username': firstuserelement.data('username'),
                    'firstname': firstuserelement.data('firstname'),
                    'lastname': firstuserelement.data('lastname'),
                    'useremail': firstuserelement.data('useremail')
                };

                if (!optionaluser['username']) {
                    // Now we change everything in our users.xml file in our backupt
                    ajax.call([{
                        methodname: "local_remote_backup_provider_update_user_entry_in_backup",
                        args: {
                            'id': this.id,
                            'restoreid': restoreid,
                            'username': firstuser['username'],
                            'firstname': firstuser['firstname'],
                            'lastname': firstuser['lastname'],
                            'useremail': firstuser['useremail']
                        },
                        done: function () {
                            //$('#continue').attr("href", link);
                            firstuserelement.find('td').eq(4).html('create as new user');
                        },
                        fail: notification.exception
                    }]);

                } else {
                    // Now we change everything in our users.xml file in our backup
                    ajax.call([{
                        methodname: "local_remote_backup_provider_update_user_entry_in_backup",
                        args: {
                            'id': this.id,
                            'restoreid': restoreid,
                            'username': optionaluser['username'],
                            'firstname': optionaluser['firstname'],
                            'lastname': optionaluser['lastname'],
                            'useremail': optionaluser['useremail']
                        },
                        done: function () {
                            //$('#continue').attr("href", link);
                            firstuserelement.find('td').eq(4).html('merge with existing user');
                        },
                        fail: notification.exception
                    }]);
                }
            });

            $(".rbp_delete").click(function () {

                var restoreid = $('#tabletoexport').data('restoreid');

                var id = this.id;
                ajax.call([{
                    methodname: "local_remote_backup_provider_delete_user_entry_from_backup",
                    args: {'id': this.id, 'restoreid': restoreid},
                    done: function () {
                        //$('#continue').attr("href", link);
                        $('#rbp_userrow_' + id).remove();
                    },
                    fail: notification.exception
                }]);
            });
            $('#linktodownload').click(function () {

                //get the coursename for name of csv file
                var coursename = $('#remote_course_name').text();
                var args = [$('#tabletoexport>table'), 'userlist_import_'+coursename+'.csv'];
                exportTableToCSV.apply(this, args);
            });

            $('#continue').click(function () {

                var restorelink = $('#continue').data('href');
                var restoreid = $('#tabletoexport').data('restoreid');
                var link = restorelink+"&filename=updated_backup.mbz";

                ajax.call([{
                    methodname: "local_remote_backup_provider_create_updated_backup",
                    args: {'restoreid': restoreid},
                    done: function () {
                        window.location = link;
                    },
                    fail: notification.exception
                }]);
            });
        },
    };
});