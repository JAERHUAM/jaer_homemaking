// tarae embed helpers ([embed](url))
(function () {
    function normalizeUrl(rawUrl) {
        try {
            var url = new URL(rawUrl, window.location.href);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return null;
            return url;
        } catch (err) {
            return null;
        }
    }

    function replaceEmbeds(root) {
        var targets = root.querySelectorAll('.log20_tarae_text, .log20_tarae_thread_text');
        targets.forEach(function (el) {
            if (el.getAttribute('data-embed-processed') === '1') return;
            var html = el.innerHTML;
            var replaced = html.replace(/\[embed\]\((https?:\/\/[^\s)]+)\)/gi, function (match, url) {
                var safeUrl = String(url).replace(/"/g, '&quot;');
                return '<div class="log20_tarae_embed" data-url="' + safeUrl + '"></div>';
            });
            if (replaced !== html) {
                el.innerHTML = replaced;
            }
            el.setAttribute('data-embed-processed', '1');
        });
    }

    function buildCard(url, meta) {
        var card = document.createElement('a');
        card.className = 'log20_tarae_embed_card';
        card.href = url;
        card.target = '_blank';
        card.rel = 'noopener noreferrer';

        if (meta && meta.image) {
            var imgWrap = document.createElement('div');
            imgWrap.className = 'log20_tarae_embed_image';
            var img = document.createElement('img');
            img.src = meta.image;
            img.alt = '';
            imgWrap.appendChild(img);
            card.appendChild(imgWrap);
        }

        var metaWrap = document.createElement('div');
        metaWrap.className = 'log20_tarae_embed_meta';

        var titleEl = document.createElement('div');
        titleEl.className = 'log20_tarae_embed_title';
        titleEl.textContent = (meta && meta.title) ? meta.title : url;
        metaWrap.appendChild(titleEl);

        var desc = (meta && meta.desc) ? meta.desc : '';
        if (desc) {
            var descEl = document.createElement('div');
            descEl.className = 'log20_tarae_embed_desc';
            descEl.textContent = desc;
            metaWrap.appendChild(descEl);
        }

        var urlEl = document.createElement('div');
        urlEl.className = 'log20_tarae_embed_url';
        urlEl.textContent = (meta && meta.domain) ? meta.domain : url;
        metaWrap.appendChild(urlEl);

        card.appendChild(metaWrap);
        return card;
    }

    function isTwitterUrl(urlObj) {
        var host = urlObj.host.toLowerCase();
        if (
            host === 'twitter.com' ||
            host === 'www.twitter.com' ||
            host === 'mobile.twitter.com' ||
            host === 'm.twitter.com' ||
            host === 'x.com' ||
            host === 'www.x.com'
        ) {
            return /\/status\/\d+/.test(urlObj.pathname) || /\/i\/web\/status\/\d+/.test(urlObj.pathname);
        }
        return false;
    }

    function isBlueskyUrl(urlObj) {
        var host = urlObj.host.toLowerCase();
        return host === 'bsky.app' || host === 'www.bsky.app';
    }

    function loadScriptOnce(id, src, onload) {
        var existing = document.getElementById(id);
        if (existing) {
            if (onload) {
                if (existing.getAttribute('data-loaded') === '1') {
                    onload();
                } else {
                    existing.addEventListener('load', onload, { once: true });
                }
            }
            return;
        }
        var script = document.createElement('script');
        script.id = id;
        script.async = true;
        script.src = src;
        if (onload) {
            script.addEventListener('load', function () {
                script.setAttribute('data-loaded', '1');
                onload();
            }, { once: true });
        } else {
            script.addEventListener('load', function () {
                script.setAttribute('data-loaded', '1');
            }, { once: true });
        }
        document.body.appendChild(script);
    }

    function getTweetId(urlObj) {
        var m = urlObj.pathname.match(/\/status\/(\d+)/);
        return m ? m[1] : null;
    }
//
    function ensureTwitterScript() {
        if (window.twttr && window.twttr.widgets) return;
        if (!window.twttr) {
            window.twttr = { _e: [], ready: function (f) { this._e.push(f); } };
        }

        if (!document.getElementById('tarae-twitter-widgets')) {
            var fjs = document.getElementsByTagName('script')[0];
            var js = document.createElement('script');
            js.id = 'tarae-twitter-widgets';
            js.src = 'https://platform.twitter.com/widgets.js';
            js.async = true;
            fjs.parentNode.insertBefore(js, fjs);
        }
    }

    function renderTwitterEmbed(el, url) {
        var urlObj = normalizeUrl(url);
        var tweetId = urlObj ? getTweetId(urlObj) : null;

        if (!tweetId) {
            var fallback = document.createElement('a');
            fallback.href = url;
            fallback.textContent = url;
            fallback.target = '_blank';
            fallback.rel = 'noopener noreferrer';
            el.appendChild(fallback);
            return;
        }

        ensureTwitterScript();
        window.twttr.ready(function (twttr) {
            twttr.widgets.createTweet(tweetId, el, { lang: 'ko' });
        });
    }

    function renderBlueskyEmbed(el, url) {
        var oembedUrl = 'https://embed.bsky.app/oembed?url=' + encodeURIComponent(url);
        fetch(oembedUrl)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.html) throw new Error('no html');
                var parser = new DOMParser();
                var doc = parser.parseFromString(data.html, 'text/html');
                var block = doc.querySelector('blockquote');
                if (!block) throw new Error('no blockquote');
                el.appendChild(block);
                loadScriptOnce('tarae-bsky-embed', 'https://embed.bsky.app/static/embed.js');
            })
            .catch(function () {
                el.appendChild(buildCard(url, { title: url, desc: '', image: '', domain: (new URL(url)).host }));
            })
            .finally(function () {
                el.setAttribute('data-rendered', '1');
            });
    }

    function renderEmbed(el) {
        if (!el || el.getAttribute('data-rendered') === '1') return;
        var rawUrl = el.getAttribute('data-url') || '';
        var urlObj = normalizeUrl(rawUrl);
        if (!urlObj) {
            el.textContent = rawUrl;
            el.setAttribute('data-rendered', '1');
            return;
        }

        var url = urlObj.toString();
        var sameOrigin = urlObj.origin === window.location.origin;
        var meta = { title: url, desc: '', image: '', domain: urlObj.host };

        if (isTwitterUrl(urlObj)) {
            renderTwitterEmbed(el, url);
            el.setAttribute('data-rendered', '1');
            return;
        }

        if (isBlueskyUrl(urlObj)) {
            renderBlueskyEmbed(el, url);
            return;
        }

        if (!sameOrigin) {
            el.appendChild(buildCard(url, meta));
            el.setAttribute('data-rendered', '1');
            return;
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var ogTitle = doc.querySelector('meta[property="og:title"]');
                var ogDesc = doc.querySelector('meta[property="og:description"]');
                var ogImage = doc.querySelector('meta[property="og:image"]');
                var pageTitle = doc.querySelector('title');
                meta.title = (ogTitle && ogTitle.content) ? ogTitle.content : (pageTitle ? pageTitle.textContent : url);
                meta.desc = (ogDesc && ogDesc.content) ? ogDesc.content : '';
                meta.image = (ogImage && ogImage.content) ? ogImage.content : '';
                meta.domain = urlObj.host;
            })
            .catch(function () {
                return;
            })
            .finally(function () {
                el.appendChild(buildCard(url, meta));
                el.setAttribute('data-rendered', '1');
            });
    }

    function renderAll() {
        replaceEmbeds(document);
        document.querySelectorAll('.log20_tarae_embed').forEach(function (el) {
            renderEmbed(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderAll);
    } else {
        renderAll();
    }
})();
