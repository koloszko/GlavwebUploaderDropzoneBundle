(function ($) {

    Dropzone.autoDiscover = false;

    var isDebug = true,
        _log = function (message) {
            if (window.console && isDebug) {
                console.log(message);
            }
        };

    var uploader = {};
    var defaults = {};
    var dropzone = {};

    // Methods
    var methods = {
        init: function (options) {
            uploader = $.extend({}, defaults, options);
            var $template = $(uploader.dropzoneOptions.previewTemplate);
            $template.removeClass('hidden');
            uploader.dropzoneOptions.previewTemplate = $template.html();
            $template.remove();
            dropzone = new Dropzone(uploader.uploaderOptions.dropzoneContainer, uploader.dropzoneOptions);
            this.data('dropzone', dropzone);
            //Init popups
            if(uploader.uploaderOptions.popup) {
                $(uploader.uploaderOptions.popup).plainModal();
            }
            methods.bindEvents();
            _log('init');
            _log(dropzone);
            return this;
        },
        show: function () {
            _log(dropzone);
            return this;
        },
        hide: function () {
        },
        update: function (content) {
        },

        /**
         * Проверяем, валидный ли URL
         */
        isValidURL: function (url) {
            var regex = /^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i;
            return regex.test(url);
        },

        /**
         * Добавляем mock файлы в dropzone
         */
        addFile: function ( name, id, src, size) {
            var mockFile = {
                name: name,
                size: size,
                accepted: true
            };
            dropzone.emit('addedfile', mockFile);

            if (uploader.uploaderOptions.isThumbnail ) {
                dropzone.emit("thumbnail", mockFile, src);
                dropzone.createThumbnailFromUrl(mockFile, src);
            }
            // else {
            //     dropzone.emit('thumbnail', mockFile, src);
            // }

            var $mockFileTpl = $(mockFile.previewTemplate);

            $mockFileTpl.data('id',  id);
            $mockFileTpl.data('src', src);
            $mockFileTpl.addClass('dz-complete');
            $mockFileTpl.attr('id', 'dz-preview-' + id);

            mockFile.response = {
                id: id,
                contentPath: src
            };

            dropzone.files.push(mockFile);
        },

        /**
         * Добавляем mock файлы в dropzone
         */
        addMockFile: function (url) {

        },

        /**
         * Показываем все ошибки
         */
        showErrors: function () {
            $(uploader.uploaderOptions.upoloaderError).removeClass('hidden');
        },

        /**
         * Скрываем все ошибки
         */
        hideErrors: function () {
            $(uploader.uploaderOptions.upoloaderError).addClass('hidden');
        },

        /**
         * Изменение имени или описания
         *
         * @param fileId
         * @param name
         * @param description
         */
        renameFile: function(fileId, name, description) {
            if(uploader.uploaderOptions.popup) {
                var $popup = $(uploader.uploaderOptions.popup);
                $popup.find(uploader.uploaderOptions.previewShow.filename).val(name);

                $popup.find(uploader.uploaderOptions.description).val(description);
                $popup.data('file-id', fileId);
                $popup.plainModal('open');
            } else {
                var newName = prompt('Новое название', '');
                var newDescription = prompt('Новое описание', '');
                methods.saveRenamedFile(newName,newDescription);
            }
        },

        saveRenamedFile: function (name, description) {
            var newName,
                newDescription,
                $popup,
                fileId = $(uploader.uploaderOptions.popup).data('file-id');

            if(uploader.uploaderOptions.popup) {
                $popup = $(uploader.uploaderOptions.popup);
                newName = $popup.find(uploader.uploaderOptions.previewShow.filename).val();
                newDescription = $popup.find(uploader.uploaderOptions.description).val()
            } else {
                newName = name;
                newDescription = description
            }

            console.log(newName,newDescription);
            if (fileId) {
                $.post(uploader.uploaderOptions.urls.rename, {
                    id: fileId,
                    request_id: uploader.uploaderOptions.requestId,
                    name: newName,
                    description: newDescription
                }, function (response) {
                    if (response.success) {
                        //TODO: разобраться с контейтенром превью
                        var $preview = $('#dz-preview-' +  fileId);
                        $preview.find(uploader.uploaderOptions.filename).text(newName);
                        $preview.find(uploader.uploaderOptions.description).text(newDescription);
                    } else {
                        _log('error post');
                    }
                    $popup.plainModal('close');
                });
            } else {
                _log('error');
            }

        },



        /**
         * Добавление обработчиков событий
         */
        bindEvents: function () {

            dropzone.on("addedfile", function () {
                $('.dropzone-area-text').addClass('hidden');
            });
            dropzone.on("sending", function (file, xhr, formData) {
                $(uploader.uploaderOptions.preloader).removeClass('hidden');
                formData.append('_glavweb_uploader_request_id', uploader.uploaderOptions.requestId);
            });

            dropzone.on("success", function (file, response) {
                console.log(response);
                file.response = response;
                var $template = $(file.previewTemplate);
                $template.data('id', response.id);
                $template.data('src', response.contentPath);
                $template.attr('id', uploader.uploaderOptions.previewContainer.slice(1)  + '-' + response.id);
                //????$(file.previewTemplate).find('.dz-filename span').text('');
                //formSortedArray();
                $template.trigger('dropzone_success', [response]);
                methods.hideErrors();
            });

            dropzone.on("removedfile", function (file) {
                var id = file.response.id;

                if (id !== undefined) {
                    $.post(uploader.uploaderOptions.urls.delete, {
                        id: id,
                        request_id: uploader.uploaderOptions.requestId
                    });
                }

                // TODO: вернуть сортировку
                //formSortedArray();

                //TODO: поставить нормалный обработчик событий для скрытия ошибок
                methods.hideErrors();
                if ($('.dz-preview').length == 0) {
                    $('.dropzone-area-text').removeClass('hidden');
                }
            });

            $(document).on('click', uploader.uploaderOptions.rename , function () {
                var $preview = $(this).closest(uploader.uploaderOptions.previewContainer);
                var fileId = $preview.data('id');
                var name = $preview.find(uploader.uploaderOptions.filename).text().trim();
                var description = $preview.find(uploader.uploaderOptions.description).text().trim();
                methods.renameFile(fileId, name, description);
            });

            $(document).on('click', uploader.uploaderOptions.saveRename , function () {
                methods.saveRenamedFile();
            });
        }
    };

    $.fn.glavwebUploaderDropzone = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' not found in jQuery.glavwebUploaderDropzone');
        }
    };

})(jQuery);