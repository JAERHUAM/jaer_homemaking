// tarae media helpers (image modal + youtube input)
(function () {
    if (window.__taraeMediaInit) return;
    window.__taraeMediaInit = true;

    var modal = document.getElementById('tarae_image_modal');
    var modalImg = document.getElementById('tarae_image_modal_img');
    var modalBackdrop = modal ? modal.querySelector('.tarae_image_modal_backdrop') : null;
    var modalClose = modal ? modal.querySelector('.tarae_image_modal_close') : null;
    var modalPrev = modal ? modal.querySelector('.tarae_image_modal_prev') : null;
    var modalNext = modal ? modal.querySelector('.tarae_image_modal_next') : null;
    var taraeScrollTop = 0;
    var scrollLockHandler = null;
    var modalImages = [];
    var modalIndex = 0;

    function updateModalImage() {
        if (!modalImg) return;
        if (!modalImages.length) return;
        if (modalIndex < 0) modalIndex = 0;
        if (modalIndex >= modalImages.length) modalIndex = modalImages.length - 1;
        modalImg.src = modalImages[modalIndex];
        modalImg.alt = '';
        if (modalPrev) {
            modalPrev.style.display = modalIndex > 0 ? 'flex' : 'none';
        }
        if (modalNext) {
            modalNext.style.display = modalIndex < (modalImages.length - 1) ? 'flex' : 'none';
        }
    }

    function openModal(src, alt, images, index) {
        if (!modal || !modalImg) return;
        modalImages = Array.isArray(images) && images.length ? images : [src];
        modalIndex = typeof index === 'number' ? index : 0;

        taraeScrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

        scrollLockHandler = function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, taraeScrollTop);
            document.documentElement.scrollTop = taraeScrollTop;
            document.body.scrollTop = taraeScrollTop;
            return false;
        };

        window.addEventListener('scroll', scrollLockHandler, { passive: false, capture: true });
        window.addEventListener('wheel', scrollLockHandler, { passive: false, capture: true });
        window.addEventListener('touchmove', scrollLockHandler, { passive: false, capture: true });

        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        document.body.style.top = '-' + taraeScrollTop + 'px';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.body.style.left = '0';
        document.body.style.right = '0';

        updateModalImage();
        modal.style.display = 'flex';
        document.body.classList.add('tarae_modal_open');
        document.documentElement.classList.add('tarae_modal_open');

        var checkScroll = function () {
            var currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            if (Math.abs(currentScroll - taraeScrollTop) > 1) {
                window.scrollTo(0, taraeScrollTop);
                document.documentElement.scrollTop = taraeScrollTop;
                document.body.scrollTop = taraeScrollTop;
            }
        };
        checkScroll();
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function () {
                checkScroll();
                requestAnimationFrame(checkScroll);
            });
        }
    }

    function closeModal() {
        if (!modal || !modalImg) return;
        modal.style.display = 'none';
        modalImg.src = '';
        modalImg.alt = '';

        var savedScrollTop = taraeScrollTop || 0;

        if (scrollLockHandler) {
            window.removeEventListener('scroll', scrollLockHandler, { capture: true });
            window.removeEventListener('wheel', scrollLockHandler, { capture: true });
            window.removeEventListener('touchmove', scrollLockHandler, { capture: true });
            scrollLockHandler = null;
        }

        document.body.style.top = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';

        document.body.classList.remove('tarae_modal_open');
        document.documentElement.classList.remove('tarae_modal_open');

        var restoreScroll = function () {
            window.scrollTo(0, savedScrollTop);
            document.documentElement.scrollTop = savedScrollTop;
            document.body.scrollTop = savedScrollTop;
        };
        restoreScroll();
        if (window.requestAnimationFrame) {
            requestAnimationFrame(function () {
                restoreScroll();
                requestAnimationFrame(restoreScroll);
            });
        } else {
            setTimeout(restoreScroll, 0);
            setTimeout(restoreScroll, 10);
        }
    }

    window.taraeCloseModal = closeModal;

    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('click', '.log20_tarae_image_item img', function (e) {
            e.preventDefault();
            var $item = jQuery(this).closest('.log20_tarae_thread_item');
            var $scope = $item.length ? $item : jQuery(this).closest('.log20_tarae_body');
            if ($scope.length === 0) {
                $scope = jQuery(this).closest('.log20_tarae_item');
            }
            var images = [];
            $scope.find('.log20_tarae_image_item img').each(function () {
                if (this.src) images.push(this.src);
            });
            var idx = images.indexOf(this.src);
            if (idx < 0) idx = 0;
            openModal(this.src, this.getAttribute('alt') || '', images, idx);
        });
    } else {
        document.addEventListener('click', function (e) {
            var target = e.target;
            if (target && target.matches('.log20_tarae_image_item img')) {
                e.preventDefault();
                var item = target.closest('.log20_tarae_thread_item');
                var scope = item ? item : target.closest('.log20_tarae_body');
                if (!scope) {
                    scope = target.closest('.log20_tarae_item');
                }
                var images = [];
                if (scope) {
                    var nodeList = scope.querySelectorAll('.log20_tarae_image_item img');
                    nodeList.forEach(function (img) {
                        if (img.src) images.push(img.src);
                    });
                }
                var idx = images.indexOf(target.src);
                if (idx < 0) idx = 0;
                openModal(target.src, target.getAttribute('alt') || '', images, idx);
            }
        });
    }

    if (modalPrev) {
        modalPrev.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (modalIndex > 0) {
                modalIndex = modalIndex - 1;
            }
            updateModalImage();
        });
    }
    if (modalNext) {
        modalNext.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (modalIndex < (modalImages.length - 1)) {
                modalIndex = modalIndex + 1;
            }
            updateModalImage();
        });
    }
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', closeModal);
    }
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    if (modalImg) {
        modalImg.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            return;
        }
        if (!modal || modal.style.display !== 'flex') return;
        if (!modalImages || modalImages.length < 2) return;
        if (e.key === 'ArrowLeft') {
            modalIndex = (modalIndex - 1 + modalImages.length) % modalImages.length;
            updateModalImage();
        } else if (e.key === 'ArrowRight') {
            modalIndex = (modalIndex + 1) % modalImages.length;
            updateModalImage();
        }
    });

    document.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.log20_tarae_reply_youtube_toggle');
        if (!toggleBtn) return;
        var form = toggleBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var youtubeWrap = form.querySelector('.log20_tarae_reply_youtube');
        if (!youtubeWrap) return;
        youtubeWrap.style.display = '';
        var input = youtubeWrap.querySelector('.log20_tarae_reply_youtube_input');
        if (input) input.focus();
    });

    document.addEventListener('click', function (e) {
        var cancelBtn = e.target.closest('.log20_tarae_reply_youtube_cancel');
        if (!cancelBtn) return;
        var youtubeWrap = cancelBtn.closest('.log20_tarae_reply_youtube');
        if (!youtubeWrap) return;
        var input = youtubeWrap.querySelector('.log20_tarae_reply_youtube_input');
        if (input) input.value = '';
        youtubeWrap.style.display = 'none';
    });

    function updateReplyFileCount(form) {
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        var countEl = form.querySelector('.log20_tarae_reply_file_count');
        if (!countEl || !fileInput) return;
        var max = parseInt(fileInput.getAttribute('data-max'), 10);
        if (isNaN(max) || max < 0) max = 0;
        var existingCount = form.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length;
        var newCount = fileInput.files ? fileInput.files.length : 0;
        countEl.textContent = (existingCount + newCount) + ' / ' + max;
    }

    function renderReplyPreviews(fileInput) {
        var form = fileInput.closest('.log20_tarae_reply_form');
        if (!form) return;
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (!preview) return;
        var files = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
        var allItems = Array.prototype.slice.call(preview.querySelectorAll('.log20_tarae_reply_preview_item'));
        var fileIdx = 0;
        if (!files.length) {
            allItems.forEach(function (item) {
                if (item.getAttribute('data-existing') === '0') {
                    var oldUrl = item.getAttribute('data-url');
                    if (oldUrl) URL.revokeObjectURL(oldUrl);
                    item.remove();
                }
            });
            updateReplyFileCount(form);
            return;
        }
        allItems.forEach(function (item) {
            if (item.getAttribute('data-existing') !== '0') return;
            if (fileIdx >= files.length) {
                var oldUrl = item.getAttribute('data-url');
                if (oldUrl) URL.revokeObjectURL(oldUrl);
                item.remove();
                return;
            }
            var file = files[fileIdx];
            var url = URL.createObjectURL(file);
            var img = item.querySelector('img');
            if (img) img.src = url;
            item.setAttribute('data-index', String(fileIdx));
            item.setAttribute('data-url', url);
            var btn = item.querySelector('.log20_tarae_reply_preview_remove[data-index]');
            if (btn) {
                btn.setAttribute('data-index', String(fileIdx));
                btn.setAttribute('data-url', url);
            }
            fileIdx += 1;
        });
        while (fileIdx < files.length) {
            var nextFile = files[fileIdx];
            var nextUrl = URL.createObjectURL(nextFile);
            var newItem = document.createElement('div');
            newItem.className = 'log20_tarae_reply_preview_item';
            newItem.setAttribute('draggable', 'true');
            newItem.setAttribute('data-existing', '0');
            newItem.setAttribute('data-index', String(fileIdx));
            newItem.setAttribute('data-url', nextUrl);
            newItem.innerHTML = '<img src="' + nextUrl + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-index="' + fileIdx + '" data-url="' + nextUrl + '" aria-label="첨부 취소">×</button>';
            preview.appendChild(newItem);
            fileIdx += 1;
        }
        updateReplyFileCount(form);
    }

    function syncReplyFilesFromPreview(form) {
        if (!form) return;
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (!preview) return;
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (!fileInput || !fileInput.files) return;
        var oldFiles = Array.prototype.slice.call(fileInput.files);
        var items = Array.prototype.slice.call(preview.querySelectorAll('.log20_tarae_reply_preview_item'));
        var reordered = [];
        items.forEach(function (item) {
            if (item.getAttribute('data-existing') !== '0') return;
            var idx = parseInt(item.getAttribute('data-index'), 10);
            if (!isNaN(idx) && oldFiles[idx]) {
                reordered.push(oldFiles[idx]);
            }
        });
        var dt = new DataTransfer();
        reordered.forEach(function (file) { dt.items.add(file); });
        fileInput.files = dt.files;
        var fileIdx = 0;
        items.forEach(function (item) {
            if (item.getAttribute('data-existing') !== '0') return;
            item.setAttribute('data-index', String(fileIdx));
            var btn = item.querySelector('.log20_tarae_reply_preview_remove[data-index]');
            if (btn) btn.setAttribute('data-index', String(fileIdx));
            fileIdx += 1;
        });
        updateReplyFileCount(form);
    }

    function syncReplyOrderToFields(form) {
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (!preview) return;
        form.querySelectorAll('input[data-reply-order="1"]').forEach(function (el) { el.remove(); });
        var items = Array.prototype.slice.call(preview.querySelectorAll('.log20_tarae_reply_preview_item'));
        var order = [];
        items.forEach(function (item) {
            var idx = item.getAttribute('data-index');
            var url = item.getAttribute('data-url');
            var isExisting = item.getAttribute('data-existing') === '1';
            if (isExisting && url) {
                order.push(url);
                return;
            }
            if (idx !== null && idx !== '') {
                order.push('file:' + idx);
            } else if (url) {
                order.push(url);
            }
        });
        for (var i = 0; i < order.length; i++) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'wr_' + (11 + i);
            input.value = order[i];
            input.setAttribute('data-reply-order', '1');
            form.appendChild(input);
        }
        var countInput = document.createElement('input');
        countInput.type = 'hidden';
        countInput.name = 'tarae_image_count';
        countInput.value = order.length;
        countInput.setAttribute('data-reply-order', '1');
        form.appendChild(countInput);
    }

    function addPastedImageFile(form, fileInput, imageFile, input) {
        if (!form || !fileInput || !imageFile) return;
        var reader = new FileReader();
        reader.onload = function (evt) {
            var max = parseInt(fileInput.getAttribute('data-max'), 10);
            if (isNaN(max) || max < 0) max = 0;
            var existingCount = form.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length;
            var currentFiles = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
            var allowed = Math.max(0, max - existingCount);
            if (currentFiles.length >= allowed) {
                alert('첨부파일은 최대 ' + max + '개까지 업로드 가능합니다.');
                return;
            }
            var ext = 'png';
            if (imageFile.type === 'image/jpeg') ext = 'jpg';
            else if (imageFile.type === 'image/gif') ext = 'gif';
            else if (imageFile.type === 'image/webp') ext = 'webp';
            var uniqueName = 'paste_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8) + '.' + ext;
            var blob = new Blob([evt.target.result], { type: imageFile.type });
            var clonedFile = new File([blob], uniqueName, { type: imageFile.type });
            var dt = new DataTransfer();
            currentFiles.forEach(function (file) {
                dt.items.add(file);
            });
            dt.items.add(clonedFile);
            fileInput.files = dt.files;
            renderReplyPreviews(fileInput);
            updateReplyFileCount(form);
            if (input) {
                input.value = '';
                input.focus();
            }
        };
        reader.readAsArrayBuffer(imageFile);
    }

    window.taraeReplyMedia = window.taraeReplyMedia || {};
    window.taraeReplyMedia.updateReplyFileCount = updateReplyFileCount;
    window.taraeReplyMedia.renderReplyPreviews = renderReplyPreviews;
    window.taraeReplyMedia.syncReplyOrderToFields = syncReplyOrderToFields;

    document.addEventListener('click', function (e) {
        var attachBtn = e.target.closest('.log20_tarae_reply_attach_file');
        if (!attachBtn) return;
        var form = attachBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (fileInput) {
            fileInput._taraePrevFiles = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
            fileInput.click();
        }
    });

    document.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.log20_tarae_reply_url_toggle');
        if (!toggleBtn) return;
        var form = toggleBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var urlWrap = form.querySelector('.log20_tarae_reply_url');
        if (!urlWrap) return;
        urlWrap.style.display = '';
        var input = urlWrap.querySelector('.log20_tarae_reply_url_input');
        if (input) input.focus();
    });

    document.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.log20_tarae_reply_copy_toggle');
        if (!toggleBtn) return;
        var form = toggleBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var pasteWrap = form.querySelector('.log20_tarae_reply_paste');
        if (!pasteWrap) return;
        pasteWrap.style.display = '';
        var input = pasteWrap.querySelector('.log20_tarae_reply_paste_input');
        if (input) input.focus();
    });

    document.addEventListener('click', function (e) {
        var doneBtn = e.target.closest('.log20_tarae_reply_url_done');
        if (!doneBtn) return;
        var form = doneBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var urlWrap = form.querySelector('.log20_tarae_reply_url');
        var input = urlWrap ? urlWrap.querySelector('.log20_tarae_reply_url_input') : null;
        var url = input ? input.value.trim() : '';
        if (!url) {
            alert('이미지 URL을 입력해주세요.');
            return;
        }
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        var max = fileInput ? parseInt(fileInput.getAttribute('data-max'), 10) : 0;
        if (isNaN(max) || max < 0) max = 0;
        var existingCount = form.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length;
        var newCount = fileInput && fileInput.files ? fileInput.files.length : 0;
        if (existingCount + newCount >= max) {
            alert('첨부파일은 최대 ' + max + '개까지 업로드 가능합니다.');
            return;
        }
        var preview = form.querySelector('.log20_tarae_reply_preview');
        if (preview) {
            var item = document.createElement('div');
            item.className = 'log20_tarae_reply_preview_item';
            item.setAttribute('draggable', 'true');
            item.setAttribute('data-existing', '1');
            item.setAttribute('data-url', url);
            item.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-url="' + url + '" aria-label="첨부 취소">×</button>';
            preview.appendChild(item);
        }
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'tarae_reply_url[]';
        hidden.value = url;
        hidden.className = 'log20_tarae_reply_url_hidden';
        form.appendChild(hidden);
        updateReplyFileCount(form);
        if (input) {
            input.value = '';
            input.focus();
        }
    });

    document.addEventListener('click', function (e) {
        var cancelBtn = e.target.closest('.log20_tarae_reply_url_cancel');
        if (!cancelBtn) return;
        var urlWrap = cancelBtn.closest('.log20_tarae_reply_url');
        if (!urlWrap) return;
        var input = urlWrap.querySelector('.log20_tarae_reply_url_input');
        if (input) input.value = '';
        urlWrap.style.display = 'none';
    });

    document.addEventListener('click', function (e) {
        var cancelBtn = e.target.closest('.log20_tarae_reply_paste_cancel');
        if (!cancelBtn) return;
        var pasteWrap = cancelBtn.closest('.log20_tarae_reply_paste');
        if (!pasteWrap) return;
        var input = pasteWrap.querySelector('.log20_tarae_reply_paste_input');
        if (input) input.value = '';
        pasteWrap.style.display = 'none';
    });

    document.addEventListener('paste', function (e) {
        var input = e.target.closest('.log20_tarae_reply_paste_input');
        if (!input) return;
        var form = input.closest('.log20_tarae_reply_form');
        if (!form) return;
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (!fileInput) return;
        var items = (e.clipboardData || window.clipboardData).items || [];
        var imageFile = null;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image') === 0) {
                imageFile = items[i].getAsFile();
                break;
            }
        }
        if (imageFile) {
            addPastedImageFile(form, fileInput, imageFile, input);
            return;
        }
        if (navigator.clipboard && navigator.clipboard.read) {
            navigator.clipboard.read().then(function (clipboardItems) {
                if (!clipboardItems || !clipboardItems.length) return;
                var item = clipboardItems[0];
                var imageType = '';
                if (item.types && item.types.length) {
                    for (var t = 0; t < item.types.length; t++) {
                        if (item.types[t].indexOf('image') === 0) {
                            imageType = item.types[t];
                            break;
                        }
                    }
                }
                if (!imageType) return;
                return item.getType(imageType).then(function (blob) {
                    var ext = 'png';
                    if (imageType === 'image/jpeg') ext = 'jpg';
                    else if (imageType === 'image/gif') ext = 'gif';
                    else if (imageType === 'image/webp') ext = 'webp';
                    var name = 'paste_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8) + '.' + ext;
                    var fileFromClipboard = new File([blob], name, { type: imageType });
                    addPastedImageFile(form, fileInput, fileFromClipboard, input);
                });
            }).catch(function () {
                return;
            });
        }
    });

    (function () {
        var dragState = {
            item: null,
            form: null
        };

        function clearDragState() {
            if (dragState.item) {
                dragState.item.classList.remove('is-dragging');
            }
            document.querySelectorAll('.log20_tarae_reply_preview_item.is-drop-target').forEach(function (el) {
                el.classList.remove('is-drop-target');
            });
            dragState.item = null;
            dragState.form = null;
        }

        document.addEventListener('dragstart', function (e) {
            var item = e.target.closest('.log20_tarae_reply_preview_item');
            if (!item) return;
            var form = item.closest('.log20_tarae_reply_form');
            if (!form) return;
            dragState.item = item;
            dragState.form = form;
            item.classList.add('is-dragging');
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', '');
            }
        });

        document.addEventListener('dragover', function (e) {
            var item = e.target.closest('.log20_tarae_reply_preview_item');
            var preview = e.target.closest('.log20_tarae_reply_preview');
            if (!preview) return;
            var form = preview.closest('.log20_tarae_reply_form');
            if (!form || dragState.form !== form) return;
            e.preventDefault();
            document.querySelectorAll('.log20_tarae_reply_preview_item.is-drop-target').forEach(function (el) {
                el.classList.remove('is-drop-target');
            });
            if (dragState.item && item && item !== dragState.item) {
                item.classList.add('is-drop-target');
            }
        });

        document.addEventListener('drop', function (e) {
            var target = e.target.closest('.log20_tarae_reply_preview_item');
            var preview = e.target.closest('.log20_tarae_reply_preview');
            if (!preview || !dragState.item) return;
            var form = preview.closest('.log20_tarae_reply_form');
            if (!form || dragState.form !== form) return;
            e.preventDefault();
            if (target && target !== dragState.item) {
                preview.insertBefore(dragState.item, target);
            } else if (!target) {
                preview.appendChild(dragState.item);
            }
            syncReplyFilesFromPreview(form);
            clearDragState();
        });

        document.addEventListener('dragend', function () {
            clearDragState();
        });
    })();

    document.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.log20_tarae_reply_preview_remove');
        if (!removeBtn) return;
        var form = removeBtn.closest('.log20_tarae_reply_form');
        if (!form) return;
        var fileInput = form.querySelector('.log20_tarae_reply_file');
        if (removeBtn.hasAttribute('data-no')) {
            var fileNo = removeBtn.getAttribute('data-no');
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'bf_file_del[' + fileNo + ']';
            hidden.value = '1';
            form.appendChild(hidden);
            var previewItem = removeBtn.closest('.log20_tarae_reply_preview_item');
            if (previewItem) {
                previewItem.remove();
            }
        } else if (removeBtn.hasAttribute('data-index')) {
            if (!fileInput || !fileInput.files) return;
            var removeIndex = parseInt(removeBtn.getAttribute('data-index'), 10);
            if (isNaN(removeIndex)) return;
            var dt = new DataTransfer();
            Array.prototype.forEach.call(fileInput.files, function (file, idx) {
                if (idx !== removeIndex) {
                    dt.items.add(file);
                }
            });
            fileInput.files = dt.files;
        } else if (removeBtn.hasAttribute('data-url')) {
            var url = removeBtn.getAttribute('data-url');
            var urlHidden = form.querySelectorAll('.log20_tarae_reply_url_hidden');
            urlHidden.forEach(function (input) {
                if (input.value === url) {
                    input.remove();
                }
            });
            var previewItem = removeBtn.closest('.log20_tarae_reply_preview_item');
            if (previewItem) {
                previewItem.remove();
            }
        }
        var url = removeBtn.getAttribute('data-url');
        if (url) {
            URL.revokeObjectURL(url);
        }
        if (fileInput) {
            renderReplyPreviews(fileInput);
        }
        updateReplyFileCount(form);
    });

    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('log20_tarae_reply_file')) return;
        var fileInput = e.target;
        var form = fileInput.closest('.log20_tarae_reply_form');
        var prevFiles = fileInput._taraePrevFiles || [];
        var newFiles = fileInput.files ? Array.prototype.slice.call(fileInput.files) : [];
        var combinedFiles = prevFiles.concat(newFiles);
        var max = parseInt(fileInput.getAttribute('data-max'), 10);
        if (isNaN(max) || max < 0) max = 0;
        var existingCount = form ? form.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length : 0;
        var allowed = Math.max(0, max - existingCount);
        if (combinedFiles.length > allowed) {
            alert('첨부파일은 최대 ' + max + '개까지 업로드 가능합니다.');
            combinedFiles = combinedFiles.slice(0, allowed);
        }
        var dt = new DataTransfer();
        combinedFiles.forEach(function (file) {
            dt.items.add(file);
        });
        fileInput.files = dt.files;
        fileInput._taraePrevFiles = null;
        updateReplyFileCount(form);
        if (fileInput.files && fileInput.files.length > 0) {
            renderReplyPreviews(fileInput);
        } else {
            var preview = form ? form.querySelector('.log20_tarae_reply_preview') : null;
            if (preview) {
                preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="0"]').forEach(function (el) {
                    el.remove();
                });
            }
        }
    });

    function updateSingleThreadImageHeights() {
        var isNarrow = typeof window.innerWidth !== 'undefined' && window.innerWidth <= 768;
        var threadBodies = document.querySelectorAll('.log20_tarae_thread_body.has-images');
        threadBodies.forEach(function (body) {
            var imagesRows = body.querySelector('.log20_tarae_images_rows');
            if (!imagesRows) return;
            if (!imagesRows.classList.contains('log20_tarae_images_rows--1')) return;
            var text = body.querySelector('.log20_tarae_thread_text');
            if (!text) return;
            text.style.maxHeight = '';
            if (isNarrow) {
                imagesRows.style.setProperty('--tarae-image-max-height', '400px');
                return;
            }
            imagesRows.style.setProperty('--tarae-image-max-height', '400px');
        });
    }

    document.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('[data-toggle^="tarae-thread-"]');
        if (!toggleBtn) return;
        setTimeout(updateSingleThreadImageHeights, 0);
    });

    window.addEventListener('load', function () {
        updateSingleThreadImageHeights();
    });

    window.addEventListener('resize', function () {
        updateSingleThreadImageHeights();
    });
})();
