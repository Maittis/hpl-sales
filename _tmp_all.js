
    (function () {
      var sessionId = 'sess_' + Math.random().toString(36).substr(2, 16) + Date.now().toString(36);
      var cookiesAccepted = localStorage.getItem('hpl_cookies_accepted');

      function trackEvent(eventType, eventData) {
        if (cookiesAccepted !== 'true') return;
        var data = {
          event_type: eventType,
          event_data: eventData || {},
          session_id: sessionId
        };
        fetch('admin/analytics.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        }).catch(function() {});
      }

      if (cookiesAccepted === 'true') {
        trackEvent('page_view', { page: 'landing' });
      }

      var cookieBanner = document.getElementById('cookieBanner');
      var acceptBtn = document.getElementById('acceptCookies');
      var denyBtn = document.getElementById('denyCookies');

      if (!cookiesAccepted && cookieBanner) {
        cookieBanner.classList.remove('hidden');
      }

      if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
          localStorage.setItem('hpl_cookies_accepted', 'true');
          cookiesAccepted = 'true';
          if (cookieBanner) cookieBanner.classList.add('hidden');
          trackEvent('page_view', { page: 'landing' });
        });
      }

      if (denyBtn) {
        denyBtn.addEventListener('click', function() {
          localStorage.setItem('hpl_cookies_accepted', 'false');
          if (cookieBanner) cookieBanner.classList.add('hidden');
        });
      }

      var buttons = document.querySelectorAll('.button');
      for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
          trackEvent('cta_click', { text: this.textContent.trim(), href: this.getAttribute('href') });
        });
      }

      var promoVideo = document.getElementById('promoVideo');
      if (promoVideo) {
        promoVideo.addEventListener('play', function() {
          trackEvent('video_play', { video: 'promo' });
        });
      }

      var scrollTracked = { 25: false, 50: false, 75: false, 100: false };
      window.addEventListener('scroll', function() {
        var scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
        if (scrollPercent >= 25 && !scrollTracked[25]) {
          scrollTracked[25] = true;
          trackEvent('scroll', { depth: 25 });
        }
        if (scrollPercent >= 50 && !scrollTracked[50]) {
          scrollTracked[50] = true;
          trackEvent('scroll', { depth: 50 });
        }
        if (scrollPercent >= 75 && !scrollTracked[75]) {
          scrollTracked[75] = true;
          trackEvent('scroll', { depth: 75 });
        }
        if (scrollPercent >= 100 && !scrollTracked[100]) {
          scrollTracked[100] = true;
          trackEvent('scroll', { depth: 100 });
        }
      });
    })();
    (function () {
      var v = document.getElementById('promoVideo');
      var b = document.getElementById('soundBtn');
      if (!v || !b) return;
      v.volume = 0.7;
      v.play().catch(function () {});
      b.addEventListener('click', function () {
        if (v.muted) { v.muted = false; b.textContent = 'Mute'; }
        else { v.muted = true; b.textContent = 'Unmute'; }
      });
    })();
    (function () {
      var form = document.getElementById('leadForm');
      if (!form) return;
      var steps = form.querySelectorAll('.form-step');
      var next = document.getElementById('leadNext');
      var back = document.getElementById('leadBack');
      var msg = document.getElementById('formMsg');
      var f = document.getElementById('leadFirst');
      var l = document.getElementById('leadLast');
      var p = document.getElementById('leadPhone');
      var cc = document.getElementById('leadCc');
      var flag = document.getElementById('ccFlag');
      var ccText = document.getElementById('ccText');

      function paintCc() {
        var o = cc && cc.options ? cc.options[cc.selectedIndex] : null;
        if (!o) return;
        var flagIso = o.getAttribute('data-flag');
        if (flag && flagIso) {
          flag.onerror = function () {
            flag.onerror = null;
            flag.src = 'https://flagcdn.com/w20/' + flagIso + '.png';
          };
          flag.src = 'img/flags/' + flagIso + '.png';
        }
        if (ccText) ccText.textContent = '+' + o.value;
        if (cc) cc.title = o.textContent.replace(/^\+\d+\s*/, '') || 'Country code';
      }

      if (cc) {
        cc.addEventListener('change', function () { userPicked = true; paintCc(); });
        paintCc();
      }

      var userPicked = false;
      cc.addEventListener('pointerdown', function () { userPicked = true; });

      (function detectCountry() {
        if (userPicked) return;
        var providers = ['https://ipwho.is/', 'https://ipapi.co/json/'];
        var attempt = function (i) {
          if (i >= providers.length || userPicked) return;
          var ctrl = new AbortController();
          var timer = setTimeout(function () { ctrl.abort(); }, 2600);
          fetch(providers[i], { mode: 'cors', signal: ctrl.signal })
            .then(function (r) { return r.json(); })
            .then(function (d) {
              clearTimeout(timer);
              if (userPicked) return;
              var code = d && (d.country_calling_code || d.calling_code) ? String(d.country_calling_code || d.calling_code).replace(/\D/g, '') : '';
              if (!code) return attempt(i + 1);
              var rawCountry = d && d.country ? String(d.country) : '';
              var iso = (d && d.country_code ? String(d.country_code) : (rawCountry.length === 2 ? rawCountry : '')).toLowerCase();
              var cname = d && d.country_name ? String(d.country_name) : (rawCountry.length > 2 ? rawCountry : '');
              var match = null;
              for (var k = 0; k < cc.options.length; k++) {
                if (cc.options[k].value === code) { match = cc.options[k]; break; }
              }
              if (!match) {
                match = document.createElement('option');
                match.value = code;
                match.textContent = '+' + code + (cname ? ' ' + cname : '');
                match.setAttribute('data-flag', iso || 'un');
                cc.insertBefore(match, cc.firstChild);
              } else if (cc.firstChild !== match) {
                cc.insertBefore(match, cc.firstChild);
              }
              cc.value = code;
              paintCc();
              trackEvent('country_detected', { cc: code });
            })
            .catch(function () { clearTimeout(timer); attempt(i + 1); });
        };
        if (window.fetch) attempt(0);
      })();

      function show(n) {
        for (var i = 0; i < steps.length; i++) {
          if (i === n) { steps[i].removeAttribute('hidden'); }
          else { steps[i].setAttribute('hidden', ''); }
        }
        if (msg) msg.classList.remove('on');
        var first = steps[n].querySelector('input, textarea, select');
        if (first) first.focus();
        if (n === 0 && window.scrollY > 0) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function fail(text) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.add('on');
        trackEvent('form_error', { field: text });
      }

      next.addEventListener('click', function () {
        if (!f.value.trim()) return fail('Please enter your first name.');
        if (!l.value.trim()) return fail('Please enter your last name.');
        if (p.value.replace(/\D/g, '').length < 6) return fail('Please enter a valid phone number.');
        if (!document.getElementById('leadTerrain').value) return fail('Please choose where you will be searching.');
        if (!document.getElementById('leadTarget').value) return fail('Please tell us what you are hoping to find.');
        show(1);
      });

      back.addEventListener('click', function () { show(0); });

      form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
          e.preventDefault();
          if (!e.target.closest('.form-step').hasAttribute('hidden')) next.click();
        }
      });

      form.addEventListener('submit', function () {
        trackEvent('form_submit', { form: 'lead' });
      });
    })();
    (function () {
      var t = document.getElementById('footerMenuBtn');
      var n = document.getElementById('footerNav');
      if (!t || !n) return;
      t.addEventListener('click', function () {
        var open = n.classList.toggle('open');
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    })();
    (function () {
      var ring = document.getElementById('ring');
      if (!ring) return;
      var items = Array.prototype.slice.call(ring.querySelectorAll('[data-ring-item]'));
      if (!items.length) return;
      var wrap = document.getElementById('ringWrap');
      var playBtn = document.getElementById('ringPlay');
      var toggleBtn = document.getElementById('ringToggle');
      var prevBtn = document.getElementById('ringPrev');
      var nextBtn = document.getElementById('ringNext');
      var n = items.length;
      var idx = 0;

      var R = 300, size = 300;

      function readVars() {
        var cs = getComputedStyle(ring);
        R = parseFloat(cs.getPropertyValue('--ring-r')) || 300;
        size = parseFloat(cs.getPropertyValue('--ring-size')) || 300;
      }

      function frontVideo() { return items[idx].querySelector('video'); }

      function layout(animate) {
        readVars();
        items.forEach(function (el, i) {
          var off = i - idx;
          if (off > n / 2) off -= n;
          if (off < -n / 2) off += n;
          // one player faces the viewer, its neighbours sit edge-on around the cylinder
          var ang = off * 90;
          ang = ((ang + 180) % 360 + 360) % 360 - 180;
          var isFront = off === 0;
          if (!animate) el.style.transition = 'none';
          // rotateY spins the player around the ring so it hands over to the next one
          el.style.transform = 'rotateY(' + ang.toFixed(2) + 'deg) translateZ(' + R.toFixed(1) + 'px) translate(-50%,-50%)';
          el.style.opacity = Math.abs(ang) >= 88 ? '0' : '1';
          el.style.zIndex = String(100 - Math.abs(ang));
          el.classList.toggle('front', isFront);
          if (!animate) { void el.offsetWidth; el.style.transition = ''; }
        });
      }

      function syncBtn() {
        var v = frontVideo();
        var playing = v && !v.paused;
        if (toggleBtn) {
          toggleBtn.innerHTML = playing ? '<span aria-hidden="true">&#10073;&#10073;</span>' : '<span aria-hidden="true">&#9654;</span>';
          toggleBtn.setAttribute('aria-label', playing ? 'Pause videos' : 'Play videos');
        }
        if (playBtn) playBtn.style.display = playing ? 'none' : '';
      }

      function playFront() {
        var v = frontVideo();
        if (!v) return;
        v.muted = true;
        var pr = v.play();
        if (pr && pr.catch) pr.catch(function () {});
        syncBtn();
      }

      function pauseAll() {
        items.forEach(function (el) {
          var v = el.querySelector('video');
          if (v && !v.paused) v.pause();
        });
        syncBtn();
      }

      function show(i, autoplay) {
        if (i === idx) return;
        var old = items[idx].querySelector('video');
        if (old) { old.pause(); old.currentTime = 0; }
        idx = ((i % n) + n) % n;
        layout(true);
        syncBtn();
        if (autoplay !== false) playFront();
        if (wrap) wrap.classList.add('moved');
      }

      function next() { show(idx + 1, true); }
      function prev() { show(idx - 1, true); }

      items.forEach(function (el, i) {
        el.addEventListener('click', function () {
          if (i === idx) { togglePlay(); } else { show(i, true); }
        });
        el.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter' && e.key !== ' ') return;
          e.preventDefault();
          if (i === idx) { togglePlay(); } else { show(i, true); }
        });
        var v = el.querySelector('video');
        if (v) v.addEventListener('play', syncBtn);
        if (v) v.addEventListener('pause', syncBtn);
      });

      function togglePlay() {
        var v = frontVideo();
        if (!v) return;
        if (v.paused) { playFront(); } else { v.pause(); syncBtn(); }
      }

      if (playBtn) playBtn.addEventListener('click', playFront);
      if (toggleBtn) toggleBtn.addEventListener('click', togglePlay);
      if (prevBtn) prevBtn.addEventListener('click', prev);
      if (nextBtn) nextBtn.addEventListener('click', next);

      var tx = 0, ty = 0;
      ring.addEventListener('touchstart', function (e) {
        tx = e.changedTouches[0].clientX; ty = e.changedTouches[0].clientY;
      }, { passive: true });
      ring.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - tx;
        var dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) next(); else prev();
      }, { passive: true });

      var wlock = false;
      window.addEventListener('wheel', function (e) {
        if (wlock || Math.abs(e.deltaX) < 10) return;
        var r = ring.getBoundingClientRect();
        if (r.bottom < 80 || r.top > window.innerHeight - 80) return;
        e.preventDefault();
        wlock = true;
        window.setTimeout(function () { wlock = false; }, 650);
        if (e.deltaX > 0) next(); else prev();
      }, { passive: false });

      document.addEventListener('keydown', function (e) {
        var r = ring.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) return;
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
      });

      window.addEventListener('resize', function () { layout(false); });

      layout(false);
      if (n === 1) {
        if (wrap) wrap.classList.add('moved');
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
      }
      syncBtn();
    })();
    (function () {
      var stage = document.getElementById('proofStage');
      if (!stage) return;
      var slides = Array.prototype.slice.call(stage.querySelectorAll('.proof-slide'));
      if (!slides.length) return;
      var dotsWrap = document.getElementById('proofDots');
      var wrap = document.getElementById('proofWrap');
      var cue = document.getElementById('proofCue');
      var idx = 0;

      var dots = slides.map(function (s, i) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Go to video ' + (i + 1));
        d.addEventListener('click', function () { show(i); });
        if (dotsWrap) dotsWrap.appendChild(d);
        return d;
      });

      function show(i) {
        if (i === idx) return;
        var prev = slides[idx];
        prev.classList.remove('on');
        var pv = prev.querySelector('video');
        if (pv) { pv.pause(); pv.currentTime = 0; }
        idx = i;
        var cur = slides[idx];
        cur.classList.add('on');
        var cv = cur.querySelector('video');
        if (cv) { cv.play().catch(function () {}); }
        dots.forEach(function (d, n) { d.classList.toggle('on', n === idx); });
        if (wrap) wrap.classList.add('moved');
      }

      function next() { show((idx + 1) % slides.length); }
      function prev() { show((idx - 1 + slides.length) % slides.length); }

      slides[0].classList.add('on');
      dots[0].classList.add('on');
      var first = slides[0].querySelector('video');
      if (first) first.play().catch(function () {});

      if (cue) cue.addEventListener('click', next);

      var lock = false;
      window.addEventListener('wheel', function (e) {
        if (lock || Math.abs(e.deltaY) < 8) return;
        var r = stage.getBoundingClientRect();
        var visible = r.bottom > 80 && r.top < window.innerHeight - 80;
        if (!visible) return;
        var down = e.deltaY > 0;
        if (down && idx === slides.length - 1) return;
        if (!down && idx === 0) return;
        e.preventDefault();
        lock = true;
        window.setTimeout(function () { lock = false; }, 600);
        if (down) next(); else prev();
      }, { passive: false });

      var tx = 0, ty = 0;
      stage.addEventListener('touchstart', function (e) {
        tx = e.changedTouches[0].clientX; ty = e.changedTouches[0].clientY;
      }, { passive: true });
      stage.addEventListener('touchend', function (e) {
        var dx = e.changedTouches[0].clientX - tx;
        var dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dx) < 45 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) next(); else prev();
      }, { passive: true });

      document.addEventListener('keydown', function (e) {
        var r = stage.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) return;
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
      });
    })();
    (function () {
      var frame = document.getElementById('slidesFrame');
      if (!frame) return;
      var imgs = Array.prototype.slice.call(frame.querySelectorAll('img'));
      if (imgs.length < 2) { if (imgs.length === 1) imgs[0].classList.add('on'); return; }
      var cap = document.getElementById('slidesCap');
      var dotsWrap = document.getElementById('slidesDots');
      var i = 0;
      var timer = null;

      var dots = imgs.map(function (img, n) {
        var d = document.createElement('button');
        d.type = 'button';
        d.setAttribute('aria-label', 'Show photo ' + (n + 1));
        d.addEventListener('click', function () { show(n); restart(); });
        if (dotsWrap) dotsWrap.appendChild(d);
        return d;
      });

      function show(n) {
        imgs[i].classList.remove('on');
        i = n;
        imgs[i].classList.add('on');
        dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
        if (cap) {
          var text = imgs[i].getAttribute('data-caption') || '';
          cap.innerHTML = text ? '<span>Field result</span>' + text : '';
          cap.classList.toggle('on', !!text);
        }
      }

      function next() { show((i + 1) % imgs.length); }
      function prev() { show((i - 1 + imgs.length) % imgs.length); }

      function start() { timer = window.setInterval(next, 4200); }
      function stop() { if (timer) { window.clearInterval(timer); timer = null; } }
      function restart() { stop(); start(); }

      show(0);
      start();

      var stage = frame.parentNode;
      stage.addEventListener('mouseenter', stop);
      stage.addEventListener('mouseleave', start);
      stage.addEventListener('click', function (e) { next(); restart(); });
      stage.addEventListener('touchstart', function () { stop(); }, { passive: true });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop(); else start();
      });
    })();
  