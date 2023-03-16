(function ($) {
    /**
     * Library management object for adding and deleting libraries in the form.
     */
    var libsManagement = {
        init: function () {
            $(document).ready(function () {
                for (var i in libs) {
                    libsManagement.add(libs[i]);
                }
                $('#addLib').bind('click', function () {
                    libsManagement.add();
                });
                $('#libs-form').on('click', '.delbutton', function () {
                    if (confirm('delete this lib ?')) {
                        $(this).parent().remove();
                    }
                });
            });
        },
        add: function (data) {
            if (data == undefined) {
                data = {
                    name: '',
                    website: '',
                    version: '',
                    dependencies: '',
                    url: '',
                    files: '',
                    active: true,
                    admin: true,
                    front: true,
                    condition: '',
                    adminCondition:''
                }
            }
            var n = $('#libs-form fieldset').length + 1;
            function field(type, n, name, value, title, hint) {
                return '<div class="mb-3">' +
                    (title != undefined ? '<label for="lib-' + n + '-' + name + '" class="form-label">' + title + '</label>' : '') +
                    '<input type="' + type + '" name="' + n + '[' + name + ']" id="lib-' + n + '-' + name + '" value="' + String(value) + '" placeholder="' + name + '" class="form-control"/>    <div class="form-text">' + hint + '</div></div>';
            }
            $('#libs-form').append(
                '<fieldset>'

                + field('text', n, 'name', data.name, 'Lib name', 'This name is an unique identifier of the lib.')
                + field('url', n, 'website', data.website, 'Lib website', 'The website of the lib')
                + '<label><input type="checkbox" name="' + n + '[active]" ' + (data.active ? 'checked' : '') + '/> Active </label> '
                + '<label><input type="checkbox" name="' + n + '[admin]" ' + (data.admin ? 'checked' : '') + '/> Admin </label> '
                + '<label><input type="checkbox" name="' + n + '[front]" ' + (data.front ? 'checked' : '') + '/> Front </label> '
                + '<div class="input-group"><span class="input-group-text">URL</span><input type="text" name="' + n + '[url]" value="' + data.url + '" placeholder="url" required class="form-control" /><span class="input-group-text">Version</span><input type="text" name="' + n + '[version]" value="' + data.version + '" placeholder="version" class="form-control" /></div><div class="form-text mb-3">%1$s are replaced by the version number of the lib</div>'
                + field('text', n, 'dependencies', data.dependencies, 'Lib dependencies', 'Separate dependencies by coma or space.')
                + field('text', n, 'condition', data.condition, 'Lib front condition', 'Condition to include lib. Is valid php, should return boolean. Leave blank to include everywhere in front.')
                + field('text', n, 'adminCondition', data.adminCondition, 'Lib admin condition', 'Condition to include lib in admin. Is valid php, should return boolean. Leave blank to include everywhere in admin.')
                + '<div class="mb-3"><label for="lib-' + n + '-files" class="form-label">Files to include</label>'
                + '<textarea id="lib-' + n + '-files"name="' + n + '[files]" placeholder="files to link" style="display:block;width:100%">' + data.files + '</textarea><div class="form-text">%1$s are replaced by the version number of the lib</div></div>'
                + '<button  class="btn btn-sm btn-danger delbutton" type="button">delete</button>\
                    </fieldset><br/>');
        }
    }
    libsManagement.init();
})(jQuery);