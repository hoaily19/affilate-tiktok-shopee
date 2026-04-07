/**
 * Trang chủ: menu mobile + header cuộn + Google Translate
 */
(function () {
    // ---- Menu mobile (drawer + overlay) ----
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('main-nav');
    var backdrop = document.getElementById('nav-backdrop');
    var navClose = document.querySelector('.main-nav__close');
    if (toggle && nav) {
        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.style.overflow = open ? 'hidden' : '';
            if (backdrop) {
                backdrop.classList.toggle('is-open', open);
                backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
        }
        toggle.addEventListener('click', function () {
            setOpen(!nav.classList.contains('is-open'));
        });
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setOpen(false);
            });
        }
        if (navClose) {
            navClose.addEventListener('click', function () {
                setOpen(false);
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                setOpen(false);
            }
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setOpen(false);
            });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                setOpen(false);
            }
        });
    }

    // ---- Header cuộn ----
    var header = document.querySelector('.header');
    if (header && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var ticking = false;
        function onScroll() {
            if (window.scrollY > 12) {
                header.classList.add('header--scrolled');
            } else {
                header.classList.remove('header--scrolled');
            }
            ticking = false;
        }
        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(onScroll);
                ticking = true;
            }
        }, { passive: true });
    }

    // ---- Google Translate: cookie googtrans + reload (ổn định hơn sync select nội bộ) ----
    var gtSelect = document.getElementById('gt-select');
    if (gtSelect) {
        function getCookie(name) {
            var m = document.cookie.match(
                new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
            );
            return m ? decodeURIComponent(m[1].trim()) : '';
        }

        function googTransTargetLang() {
            var v = getCookie('googtrans');
            if (!v) return 'vi';
            var parts = v.split('/');
            if (parts.length >= 3 && parts[2]) return parts[2];
            return 'vi';
        }

        function clearGoogTransCookie() {
            var exp = 'expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            var host = location.hostname;
            document.cookie = 'googtrans=;path=/;' + exp;
            if (host) {
                document.cookie = 'googtrans=;path=/;domain=' + host + ';' + exp;
                document.cookie = 'googtrans=;path=/;domain=.' + host + ';' + exp;
            }
        }

        var lang = googTransTargetLang();
        if (gtSelect.querySelector('option[value="' + lang + '"]')) {
            gtSelect.value = lang;
        }

        gtSelect.addEventListener('change', function () {
            var v = this.value;
            if (v === 'vi') {
                clearGoogTransCookie();
            } else {
                document.cookie = 'googtrans=/vi/' + v + ';path=/';
            }
            location.reload();
        });
    }
})();