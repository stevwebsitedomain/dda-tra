(function() {
    'use strict';
    var message = 'Your not authorized to access this page.';
    var reportUrl = 'report_unauthorized.php';

    function showModal(actionType) {
        try {
            var existing = document.getElementById('tra-unauth-modal');
            if (existing) {
                existing.style.display = 'flex';
                report(actionType);
                return;
            }
            var wrap = document.createElement('div');
            wrap.id = 'tra-unauth-modal';
            wrap.setAttribute('role', 'alert');
            wrap.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;';
            var box = document.createElement('div');
            box.style.cssText = 'position:relative;overflow:hidden;background:#fff;color:#0b1e3b;padding:52px 64px;max-width:720px;width:100%;text-align:center;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.5);border-top:5px solid #dc3545;';
            var logoUrl = 'dda.jpg';
            try {
                var p = window.location.pathname || '';
                var idx = p.lastIndexOf('/');
                if (idx >= 0) logoUrl = p.substring(0, idx + 1) + 'dda.jpg';
            } catch (e) {}
            box.innerHTML = '<div style="position:absolute;inset:0;background:url(\'' + logoUrl.replace(/'/g, '%27') + '\') center/22% no-repeat;opacity:0.08;pointer-events:none;z-index:0;"></div>' +
                '<div style="position:relative;z-index:1;"><div style="margin-bottom:24px;"><span style="font-size:64px;color:#dc3545;">&#9888;</span></div>' +
                '<p style="margin:0 0 28px;font-size:19px;line-height:1.65;font-weight:600;color:#1f2937;">' + message.replace(/</g, '&lt;') + '</p>' +
                '<button type="button" id="tra-unauth-close" style="margin-top:12px;padding:14px 32px;background:#0b1e3b;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:16px;">OK</button></div>';
            wrap.appendChild(box);
            wrap.addEventListener('click', function(e) {
                if (e.target === wrap || e.target.id === 'tra-unauth-close') wrap.style.display = 'none';
            });
            if (document.body) document.body.appendChild(wrap);
            report(actionType);
        } catch (err) {
            alert(message);
            report(actionType || 'modal_error');
        }
    }

    function report(actionType) {
        try {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', reportUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({ action: actionType || 'unknown', page: window.location.href || window.location.pathname || '' }));
        } catch (e) {}
    }

    function onContextMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        showModal('right_click');
        return false;
    }
    function onKeyDown(e) {
        if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) {
            e.preventDefault();
            e.stopPropagation();
            showModal('view_source');
            return false;
        }
        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) {
            e.preventDefault();
            e.stopPropagation();
            showModal('inspect');
            return false;
        }
        if (e.key === 'F12') {
            e.preventDefault();
            e.stopPropagation();
            showModal('devtools');
            return false;
        }
    }
    document.addEventListener('contextmenu', onContextMenu, true);
    document.addEventListener('keydown', onKeyDown, true);

    (function apiStatusWidget() {
        var icon = document.getElementById('api-status-icon');
        var text = document.getElementById('api-status-text');
        var headerIcon = document.getElementById('header-api-icon');
        var headerLabel = document.getElementById('header-api-label');
        if (!icon && !headerIcon) return;
        function setLive(live) {
            var c = live ? 'live' : 'offline';
            var label = live ? 'API Live' : 'API Offline';
            if (icon) { icon.className = 'fas fa-circle api-status-icon mr-2 ' + c; }
            if (text) { text.textContent = label; text.className = 'api-status-text small ' + c; }
            if (headerIcon) { headerIcon.className = 'fas fa-circle api-dot-icon mr-2 ' + c; }
            if (headerLabel) { headerLabel.textContent = label; headerLabel.className = c; }
        }
        function check() {
            fetch('api_status.php', { cache: 'no-store' }).then(function(r) { return r.json(); }).then(function(d) { setLive(!!d.live); }).catch(function() { setLive(false); });
        }
        check();
        setInterval(check, 30000);
    })();

    (function exportRecordsGuard() {
        var sweetAlertUrl = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';

        function isExportRecordsLink(link) {
            if (!link || !link.classList) return false;
            try {
                var url = new URL(link.getAttribute('href') || '', window.location.href);
                if (!/\/download\.php$/i.test(url.pathname)) return false;
                if (link.classList.contains('sidebar-link') && /Export\s+Records/i.test(link.textContent || '')) {
                    return url.search === '';
                }
                if (link.classList.contains('tile-box')) {
                    var view = url.searchParams.get('view');
                    return view === 'main' || view === 'categorized' || view === 'categories';
                }
                return false;
            } catch (e) {
                return false;
            }
        }

        function loadSweetAlert() {
            if (window.Swal) return Promise.resolve(window.Swal);
            return new Promise(function(resolve, reject) {
                var existing = document.querySelector('script[data-tra-sweetalert]');
                if (existing) {
                    existing.addEventListener('load', function() { resolve(window.Swal); }, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                    return;
                }
                var script = document.createElement('script');
                script.src = sweetAlertUrl;
                script.async = true;
                script.setAttribute('data-tra-sweetalert', '1');
                script.onload = function() { resolve(window.Swal); };
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        function unlockExport(password) {
            return fetch('export_auth.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password: password || '' })
            }).then(function(response) {
                return response.json().catch(function() { return {}; }).then(function(data) {
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Incorrect password.');
                    }
                    return data;
                });
            });
        }

        function fallbackPasswordPrompt(targetUrl) {
            var password = window.prompt('Enter the password to open Export Records:');
            if (password === null) return;
            unlockExport(password).then(function() {
                window.location.href = targetUrl;
            }).catch(function(err) {
                window.alert(err.message || 'Incorrect password.');
            });
        }

        function requestPassword(targetUrl) {
            loadSweetAlert().then(function(Swal) {
                if (!Swal) {
                    fallbackPasswordPrompt(targetUrl);
                    return;
                }
                Swal.fire({
                    title: 'Export Records Password',
                    text: 'Enter the password to open Export Records.',
                    input: 'password',
                    inputPlaceholder: 'Enter password',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocomplete: 'current-password'
                    },
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Open',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#0b1e3b',
                    showLoaderOnConfirm: true,
                    preConfirm: function(password) {
                        if (!password) {
                            Swal.showValidationMessage('Please enter the password.');
                            return false;
                        }
                        return unlockExport(password).catch(function(err) {
                            Swal.showValidationMessage(err.message || 'Incorrect password.');
                            return false;
                        });
                    },
                    allowOutsideClick: function() {
                        return !Swal.isLoading();
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        window.location.href = targetUrl;
                    }
                });
            }).catch(function() {
                fallbackPasswordPrompt(targetUrl);
            });
        }

        document.addEventListener('click', function(e) {
            var link = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!isExportRecordsLink(link)) return;
            e.preventDefault();
            e.stopPropagation();
            requestPassword(link.href);
        }, true);
    })();

    (function inactivityAutoLogout() {
        var path = (window.location.pathname || '').toLowerCase();
        if (/\/(index|login)\.php$/.test(path) || path === '/' || path === '') return;

        var timeoutMs = 5 * 60 * 1000;
        var checkEveryMs = 15 * 1000;
        var lastActivity = Date.now();
        var logoutStarted = false;

        function markActivity() {
            lastActivity = Date.now();
        }

        function isVisible(el) {
            if (!el) return false;
            var style = window.getComputedStyle(el);
            var rect = el.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        }

        function isSearchOrLoadingActive() {
            try {
                if (window.Swal && typeof window.Swal.isLoading === 'function' && window.Swal.isLoading()) {
                    return true;
                }
            } catch (e) {}

            var activeSelectors = [
                '#search-overlay',
                '#save-category-overlay',
                '.search-overlay',
                '.swal2-container.swal2-shown'
            ];
            for (var i = 0; i < activeSelectors.length; i++) {
                var nodes = document.querySelectorAll(activeSelectors[i]);
                for (var j = 0; j < nodes.length; j++) {
                    if (isVisible(nodes[j])) return true;
                }
            }
            return false;
        }

        function checkIdle() {
            if (logoutStarted) return;
            if (isSearchOrLoadingActive()) {
                markActivity();
                return;
            }
            if (Date.now() - lastActivity >= timeoutMs) {
                logoutStarted = true;
                window.location.href = 'logout.php?reason=inactive';
            }
        }

        ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function(evt) {
            document.addEventListener(evt, markActivity, { passive: true });
        });

        setInterval(checkIdle, checkEveryMs);
    })();

    var devToolsOpen = false;
    var threshold = 160;
    setInterval(function() {
        var w = window.outerWidth - window.innerWidth;
        var h = window.outerHeight - window.innerHeight;
        if (w > threshold || h > threshold) {
            if (!devToolsOpen) {
                devToolsOpen = true;
                showModal('devtools');
            }
        } else {
            devToolsOpen = false;
        }
    }, 1500);
})();
