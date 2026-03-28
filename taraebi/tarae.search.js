// tarae search helpers
(function () {
    function adjustSublistHeightSafe() {
        if (window.taraeAdjustSublistHeightTarae && typeof window.taraeAdjustSublistHeightTarae === 'function') {
            window.taraeAdjustSublistHeightTarae();
            return;
        }
        var sublist = document.getElementById('log20_content_sublist');
        if (sublist) {
            sublist.style.height = 'auto';
        }
    }

    var searchInput = document.getElementById('log20_tarae_search_input');
    var searchBtn = document.getElementById('log20_tarae_search_btn');
    var searchReset = document.getElementById('log20_tarae_search_reset');
    var taraeArea = document.getElementById('log20_list_tarae_area');
    var emptyMessage = document.querySelector('.log20_empty');

    function performSearch() {
        if (!searchInput) return;
        var searchTerm = searchInput.value.trim().toLowerCase();
        var hasVisibleItems = false;
        var visibleCount = 0;
        var items = document.querySelectorAll('.log20_tarae_item');

        if (searchTerm === '') {
            items.forEach(function (item) {
                item.style.display = '';
                visibleCount++;
                var threadContainer = item.querySelector('.log20_tarae_thread');
                if (threadContainer) {
                    threadContainer.style.display = '';
                }
                var threadItems = item.querySelectorAll('.log20_tarae_thread_item');
                threadItems.forEach(function (threadItem) {
                    threadItem.style.display = '';
                });
            });
            hasVisibleItems = visibleCount > 0;

            if (taraeArea && emptyMessage) {
                if (hasVisibleItems) {
                    taraeArea.style.display = '';
                    emptyMessage.style.display = 'none';
                } else {
                    taraeArea.style.display = 'none';
                    emptyMessage.style.display = '';
                }
            }

            var noResultsMsg = document.getElementById('log20_tarae_no_results');
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        } else {
            items.forEach(function (item) {
                var titleEl = item.querySelector('.log20_tarae_title');
                var textEl = item.querySelector('.log20_tarae_text');
                var parentText = (titleEl ? titleEl.textContent : '') + ' ' + (textEl ? textEl.textContent : '');
                parentText = parentText.toLowerCase();

                var threadContainer = item.querySelector('.log20_tarae_thread');
                var threadItems = item.querySelectorAll('.log20_tarae_thread_item');
                var matchedThreads = 0;

                threadItems.forEach(function (threadItem) {
                    var threadTextEl = threadItem.querySelector('.log20_tarae_thread_text');
                    var threadTitleEl = threadItem.querySelector('.log20_tarae_thread_title');
                    var threadText = '';
                    if (threadTextEl) threadText += threadTextEl.textContent + ' ';
                    if (threadTitleEl) threadText += threadTitleEl.textContent;
                    threadText = threadText.toLowerCase();
                    if (threadText.indexOf(searchTerm) !== -1) {
                        threadItem.style.display = '';
                        matchedThreads++;
                    } else {
                        threadItem.style.display = 'none';
                    }
                });

                var parentMatch = parentText.indexOf(searchTerm) !== -1;
                if (parentMatch || matchedThreads > 0) {
                    item.style.display = '';
                    visibleCount++;
                    hasVisibleItems = true;
                    if (threadContainer) {
                        threadContainer.style.display = matchedThreads > 0 ? 'block' : '';
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            if (taraeArea) {
                if (hasVisibleItems) {
                    taraeArea.style.display = '';
                    var noResultsMsg = document.getElementById('log20_tarae_no_results');
                    if (noResultsMsg) {
                        noResultsMsg.remove();
                    }
                } else {
                    taraeArea.style.display = 'none';
                    var noResultsMsg = document.getElementById('log20_tarae_no_results');
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'log20_tarae_no_results';
                        noResultsMsg.className = 'log20_empty';
                        noResultsMsg.innerHTML = '<p>검색 결과가 없습니다.</p>';
                        if (taraeArea.parentNode) {
                            taraeArea.parentNode.insertBefore(noResultsMsg, taraeArea.nextSibling);
                        }
                    }
                }
            }
            adjustSublistHeightSafe();
            return;
        }

        adjustSublistHeightSafe();
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            performSearch();
        });
    }

    if (searchReset) {
        searchReset.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }
            try {
                sessionStorage.removeItem('tarae_scroll_latest_parent');
                sessionStorage.removeItem('tarae_scroll_restore');
            } catch (err) {
                // ignore
            }
            var url = new URL(window.location.href);
            url.hash = '';
            ['tarae_open', 'tarae_search', 'tarae_parent', 'tarae_page'].forEach(function (key) {
                url.searchParams.delete(key);
            });
            window.location.href = url.toString();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }

    if (searchInput) {
        var params = new URLSearchParams(window.location.search);
        var presetSearch = params.get('tarae_search');
        if (presetSearch) {
            searchInput.value = presetSearch;
            performSearch();
        }
    }

    function scrollToSharedItem() {
        var params = new URLSearchParams(window.location.search);
        var shareId = params.get('tarae_id');
        if (!shareId) return;
        var threadItem = document.querySelector('.log20_tarae_thread_item[data-thread-id="' + shareId + '"]');
        if (threadItem) {
            var parentId = threadItem.getAttribute('data-thread-parent');
            if (parentId) {
                var threadEl = document.getElementById('tarae-thread-' + parentId);
                if (threadEl) {
                    threadEl.style.display = 'block';
                    document.querySelectorAll('[data-toggle="tarae-thread-' + parentId + '"]').forEach(function (btn) {
                        btn.textContent = '타래 접기';
                    });
                }
            }
            threadItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
            adjustSublistHeightSafe();
            return;
        }
        var parentItem = document.querySelector('.log20_tarae_item[data-tarae-id="' + shareId + '"]');
        if (parentItem) {
            parentItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
            adjustSublistHeightSafe();
        }
    }

    window.addEventListener('load', function () {
        scrollToSharedItem();
    });

    function buildShareUrl(subject, parentId, shareId) {
        var url = new URL(window.location.href);
        url.hash = '';
        ['tarae_open', 'tarae_search', 'tarae_parent', 'tarae_page', 'tarae_id'].forEach(function (key) {
            url.searchParams.delete(key);
        });
        if (shareId) {
            url.searchParams.set('tarae_id', shareId);
        } else if (subject) {
            url.searchParams.set('tarae_search', subject || '');
            if (parentId) {
                url.searchParams.set('tarae_parent', parentId);
            }
        }
        return url.toString();
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            try {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (ok) {
                    resolve();
                } else {
                    reject(new Error('copy failed'));
                }
            } catch (err) {
                reject(err);
            }
        });
    }

    function showShareToast(btn) {
        var wrap = btn.closest('.log20_tarae_share_wrap');
        if (!wrap) return;
        var toast = wrap.querySelector('.log20_tarae_share_toast');
        if (!toast) return;
        if (toast._hideTimer) {
            clearTimeout(toast._hideTimer);
        }
        toast.classList.add('is-visible');
        toast._hideTimer = setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 800);
    }

    document.querySelectorAll('.log20_tarae_btn_share').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var subject = btn.getAttribute('data-share-subject') || '';
            var parentId = btn.getAttribute('data-share-parent') || '';
            var shareId = btn.getAttribute('data-share-id') || '';
            var shareUrl = buildShareUrl(subject, parentId, shareId);
            copyToClipboard(shareUrl)
                .then(function () {
                    showShareToast(btn);
                })
                .catch(function () {
                    showShareToast(btn);
                });
        });
    });
})();
