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
                //var restoreid = $('#tabletoexport').data('restoreid');

                var optionaluserelement = $(this).find('.optionvalue:selected');

                if (optionaluserelement) {
                    //we only do the work if we selected somebody
                    var optionaluser = {
                        'id':$(this).val(),
                        'username':optionaluserelement.data('username'),
                        'firstname':optionaluserelement.data('firstname'),
                        'lastname':optionaluserelement.data('lastname'),
                        'useremail':optionaluserelement.data('useremail')
                    };

                    var firstuserelement = $('#rbp_userrow_'+this.id);
                    var firstuser = {
                        'id':this.id,
                        'username':firstuserelement.data('username'),
                        'firstname':firstuserelement.data('firstname'),
                        'lastname':firstuserelement.data('lastname'),
                        'useremail':firstuserelement.data('useremail')
                    };

                    optionaluserelement.text(firstuser['username']
                        +" "+firstuser['firstname']
                        +" "+firstuser['lastname']
                        +" "+firstuser['useremail']);

                    // Now we change the appearance.
                    firstuserelement.find('td').eq(0).html(optionaluser['username']);
                    firstuserelement.find('td').eq(1).html(optionaluser['firstname']);
                    firstuserelement.find('td').eq(2).html(optionaluser['lastname']);
                    firstuserelement.find('td').eq(3).html(optionaluser['useremail']);

                    // And now we switch the database.
                    firstuserelement.data('username', optionaluser['username']);
                    firstuserelement.data('firstname', optionaluser['firstname']);
                    firstuserelement.data('lastname', optionaluser['lastname']);
                    firstuserelement.data('useremail', optionaluser['useremail']);

                    optionaluserelement.data('username', firstuser['username']);
                    optionaluserelement.data('firstname', firstuser['firstname']);
                    optionaluserelement.data('lastname', firstuser['lastname']);
                    optionaluserelement.data('useremail', firstuser['useremail']);

                    var blankelement = $(this).find('.blankvalue');
                    blankelement.text('Merged with existing user');
                    blankelement.attr('selected','selected');
                    // We have to remove the selected Attribute right away to make it work more than once
                    blankelement.removeAttr('selected');
                }
                else {
                    alert("we drew blank");
                }
            });

            $(".rbp_delete").click(function () {

                var restoreid = $('#tabletoexport').data('restoreid');

                var link = $('#continue').attr("href");
                if (link.indexOf("&pathnamehash") != -1) {
                    link = link.substring(0, link.indexOf("&pathnamehash"));
                    link = link+"&filename=updated_backup.mbz";
                }

                var id = this.id;
                ajax.call([{
                    methodname: "local_remote_backup_provider_delete_user_entry_from_backup",
                    args: {'id': this.id, 'restoreid':restoreid},
                    done: function () {
                        $('#continue').attr("href", link);
                        $('#rbp_userrow_' + id).remove();
                    },
                    fail: notification.exception
                }]);
            });
            $('#linktodownload').click(function () {

                var args = [$('#tabletoexport>table'), 'userlist.csv'];
                exportTableToCSV.apply(this, args);
            });
        },
    };
});