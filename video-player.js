/* Custom video player behaviour. One class serves every player on the page.
   Everything is scoped to the .hpl-player element, so several can coexist and
   the page's own code is untouched.

   Autoplay is a three-step ladder, per the browsers' actual rules:
     A  audible autoplay allowed  -> plays with sound, no notice
     B  only muted allowed       -> plays muted, shows the unmute notice
     C  nothing allowed          -> shows the centre play button
   Both play() calls are awaited inside try/catch, so a refusal never surfaces
   as an unhandled rejection in the console. */
(function () {
  'use strict';

  /* Only one player may be heard at a time. A player that starts making noise
     tells the others to shut up, rather than each one tracking the rest. */
  var audible = [];

  function claimAudio(p) {
    for (var i = 0; i < audible.length; i++) {
      if (audible[i] !== p) audible[i].forceMute();
    }
    var at = audible.indexOf(p);
    if (at > -1) audible.splice(at, 1);
    audible.push(p);
  }

  function releaseAudio(p) {
    var at = audible.indexOf(p);
    if (at > -1) audible.splice(at, 1);
  }

  function fmt(s) {
    if (!isFinite(s) || s < 0) s = 0;
    var m = Math.floor(s / 60);
    var sec = Math.floor(s % 60);
    return m + ':' + (sec < 10 ? '0' : '') + sec;
  }

  /* Pointer events cover mouse, touch and pen in one path, which is what keeps
     dragging the progress bar working on phones. */
  function pointX(ev) {
    return ev.clientX !== undefined ? ev.clientX : (ev.touches && ev.touches[0] ? ev.touches[0].clientX : 0);
  }

  function Player(root) {
    this.root = root;
    this.v = root.querySelector('.hpl-player-video');
    if (!this.v) return;

    this.q = function (s) { return root.querySelector(s); };
    this.center = this.q('.hpl-center-play');
    this.unmute = this.q('.hpl-unmute');
    this.cc = this.q('.hpl-cc');
    this.bar = this.q('.hpl-controls');
    this.playBtn = this.q('.hpl-playpause');
    this.rewBtn = this.q('.hpl-rew');
    this.volBtn = this.q('.hpl-vol');
    this.volPopBtn = this.q('.hpl-vol-slider-btn');
    this.volPop = this.q('.hpl-vol-pop');
    this.volRange = this.q('.hpl-vol-range');
    this.setBtn = this.q('.hpl-settings-btn');
    this.menu = this.q('.hpl-menu');
    this.ccBtn = this.q('.hpl-ccbtn');
    this.cur = this.q('.hpl-time-cur');
    this.dur = this.q('.hpl-time-dur');
    this.track = this.q('.hpl-progress');
    this.played = this.q('.hpl-progress-played');
    this.buffer = this.q('.hpl-progress-buffer');
    this.knob = this.q('.hpl-progress-knob');

    this.idle = 0;
    this.seeking = false;
    this.wasPlayingBeforeSeek = false;
    this.ready = false;
    this.hiddenTimer = null;
    this.wasPlaying = false;
    this.userPaused = false;
    this.userMuted = false;
    this.retryArmed = false;
    this.retryOff = [];
    this.inView = false;

    this.loop = root.dataset.loop === '1';
    this.title = root.dataset.title || 'Video';
    this.wantsAutoplay = root.dataset.autoplay === '1';

    this.init();
  }

  Player.prototype.init = function () {
    var self = this;
    var v = this.v;

    /* Nothing is fetched until the player is near the viewport. With 30+ videos
       on a page this is the difference between one request and thirty. */
    this.observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        self.inView = en.isIntersecting;
        if (en.isIntersecting) self.enterView();
        else self.leaveView();
      });
    }, { rootMargin: '250px 0px' });

    this.observer.observe(this.root);

    ['play', 'pause', 'ended', 'timeupdate', 'progress', 'loadedmetadata', 'volumechange', 'ratechange'].forEach(function (ev) {
      v.addEventListener(ev, function () { self.sync(ev); });
    });

    v.addEventListener('loadedmetadata', function () { self.markReady(); });

    /* A fast video on a fast connection can finish loading before this deferred
       script runs, in which case loadedmetadata has already been and gone. */
    if (v.readyState >= 1) this.markReady();

    this.center.addEventListener('click', function () { self.toggle(); });
    this.playBtn.addEventListener('click', function () { self.toggle(); });
    /* Clicking the picture itself plays/pauses, as players normally do. The
       bar sits above the video, so its buttons never reach this handler. */
    v.addEventListener('click', function (e) {
      if (e.target.closest('.hpl-controls, .hpl-unmute, .hpl-menu, .hpl-vol-pop')) return;
      self.toggle();
    });
    v.addEventListener('dblclick', function () { self.toggleFullscreen(); });
    this.rewBtn.addEventListener('click', function () { self.seekBy(-10); });
    this.volBtn.addEventListener('click', function () { self.toggleMute(); });
    this.unmute.addEventListener('click', function () { self.unmuteNow(); });

    if (this.volPopBtn) {
      this.volPopBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = self.volPop.hidden;
        self.volPop.hidden = !open;
        self.volPopBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        self.wake();
      });
      this.volRange.addEventListener('input', function () {
        var val = Number(this.value) / 100;
        self.setVolume(val);
      });
      this.volRange.addEventListener('change', function () { self.closePopups(); });
    }

    this.setBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = self.menu.hidden;
      self.menu.hidden = !open;
      self.setBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        var on = self.menu.querySelector('[aria-checked="true"]');
        if (on) on.focus();
      }
      self.wake();
    });

    if (this.menu) {
      this.menu.querySelectorAll('[data-rate]').forEach(function (b) {
        b.addEventListener('click', function () {
          var r = Number(this.dataset.rate);
          self.v.playbackRate = r;
          self.menu.querySelectorAll('[data-rate]').forEach(function (o) {
            o.setAttribute('aria-checked', o === b ? 'true' : 'false');
          });
          self.closePopups();
          self.wake();
        });
      });
    }

    if (this.ccBtn) {
      var track = v.textTracks && v.textTracks[0];
      if (track) {
        track.mode = 'showing';
        this.root.classList.add('cc-on');
        this.ccBtn.setAttribute('aria-pressed', 'true');
        track.addEventListener('cuechange', function () {
          self.cc.textContent = track.activeCues && track.activeCues.length
            ? Array.prototype.map.call(track.activeCues, function (c) { return c.text; }).join(' ')
            : '';
        });
      }
      this.ccBtn.addEventListener('click', function () {
        var t = self.v.textTracks && self.v.textTracks[0];
        if (!t) return;
        var on = t.mode !== 'showing';
        t.mode = on ? 'showing' : 'hidden';
        self.root.classList.toggle('cc-on', on);
        this.setAttribute('aria-pressed', on ? 'true' : 'false');
        self.wake();
      });
    }

    /* Seeking: click and drag anywhere on the track. */
    this.track.addEventListener('pointerdown', function (e) {
      self.seeking = true;
      self.wasPlayingBeforeSeek = !self.v.paused;
      self.track.setPointerCapture(e.pointerId);
      self.seekFromEvent(e);
      self.wake();
    });
    this.track.addEventListener('pointermove', function (e) {
      if (self.seeking) self.seekFromEvent(e);
    });
    this.track.addEventListener('pointerup', function (e) {
      self.seeking = false;
      try { self.track.releasePointerCapture(e.pointerId); } catch (err) {}
    });
    this.track.addEventListener('pointercancel', function () { self.seeking = false; });

    this.track.addEventListener('keydown', function (e) {
      var d = e.key === 'ArrowRight' || e.key === 'ArrowUp' ? 5
        : e.key === 'ArrowLeft' || e.key === 'ArrowDown' ? -5
          : e.key === 'Home' ? -1e9 : e.key === 'End' ? 1e9 : 0;
      if (!d) return;
      e.preventDefault();
      self.seekBy(d);
    });

    /* Auto-hide the bar while playing and idle; always visible when paused. */
    ['mousemove', 'touchstart', 'pointerdown', 'focusin'].forEach(function (ev) {
      this.root.addEventListener(ev, function () { self.wake(); }, true);
    }, this);
    this.root.addEventListener('mouseleave', function () { self.poke(); });

    this.root.addEventListener('keydown', function (e) { self.keys(e); });

    document.addEventListener('click', function (e) {
      if (!self.root.contains(e.target)) self.closePopups();
    });

    this.sync();
  };

  /* ---------- autoplay ladder ---------- */

  Player.prototype.markReady = function () {
    if (this.ready) return;
    this.ready = true;
    this.dur.textContent = fmt(this.v.duration);
    if (this.wantsAutoplay && !this.blocked) this.tryAutoplay();
  };

  Player.prototype.enterView = function () {
    if (this.v.preload === 'none') this.v.preload = 'metadata';
    if (this.wantsAutoplay && !this.blocked) {
      if (!this.tried) this.tryAutoplay();
      /* Only pick playback back up if it was the scroll that stopped it.
         A visitor who pressed pause keeps it paused. */
      else if (this.wasPlaying && !this.userPaused && this.v.paused) {
        var p = this.v.play();
        if (p && p.catch) p.catch(function () {});
      }
    }
  };

  Player.prototype.leaveView = function () {
    /* Do not keep spending bandwidth on a video nobody can see. */
    this.wasPlaying = !this.v.paused && !this.userPaused;
    if (!this.v.paused) this.v.pause();
  };

  Player.prototype.tryAutoplay = function () {
    var self = this;
    this.tried = true;
    if (this.v.readyState < 1) {
      var wait = function () { if (!self.tried) return; self.ladder(); };
      this.v.addEventListener('loadedmetadata', wait, { once: true });
      return;
    }
    this.ladder();
  };

  /* Audible autoplay is refused outright until the page has been interacted
     with. Rather than settling for muted, keep knocking: browsers lift the
     restriction the instant a gesture happens, and also on tab focus, so this
     turns the audible attempt into "the earliest moment the browser allows"
     instead of "only if the visitor finds the unmute button". */
  Player.prototype.armAudibleRetry = function () {
    if (this.retryArmed || this.userMuted) return;
    this.retryArmed = true;
    var self = this;

    var knobs = function () {
      var v = self.v;
      if (v.paused || v.readyState < 1 || !self.inView) return;
      if (v.muted || v.volume === 0) self.attemptSound(1);
    };

    ['canplay', 'loadeddata', 'playing', 'volumechange'].forEach(function (type) {
      v_event(self, type, knobs);
    });

    /* Returning to the tab means a gesture has practically always happened,
       which is the most common moment for the policy to lift. */
    v_event(self, 'visibilitychange', function () {
      if (!document.hidden) knobs();
    });
    document.addEventListener('focus', knobs);

    /* One-shot cleanup once audio is actually running, so a paused hero is not
       holding three listeners open for the life of the page. */
    v_event(self, 'playing', function () {
      if (self.v.muted) return;
      self.disarmAudibleRetry();
    }, { once: true });
  };

  Player.prototype.disarmAudibleRetry = function () {
    if (!this.retryArmed) return;
    this.retryArmed = false;
    this.retryOff.forEach(function (fn) { fn(); });
    this.retryOff.length = 0;
  };

  /* Small helper: bind, and remember how to undo it. */
  function v_event(player, type, fn, opts) {
    var target = type === 'visibilitychange' ? document : player.v;
    target.addEventListener(type, fn, opts || false);
    player.retryOff.push(function () { target.removeEventListener(type, fn, opts || false); });
  }

  Player.prototype.ladder = function () {
    var self = this;
    var v = this.v;
    this.state = 'trying';
    this.armAudibleRetry();

    /* State A: ask for sound first. */
    v.muted = false;
    v.volume = 1;
    if (this.volRange) this.volRange.value = '100';
    claimAudio(this);

    v.play()
      .then(function () {
        self.state = 'A';
        self.root.classList.remove('is-blocked', 'show-unmute');
        claimAudio(self);
        self.wake();
      })
      .catch(function () {
        /* State B: the refusal means audible autoplay is not permitted here,
           so step down to muted rather than showing a dead play button. */
        v.muted = true;
        releaseAudio(self);
        v.play()
          .then(function () {
            self.state = 'B';
            self.root.classList.add('show-unmute');
            self.wake();
            self.armFirstGestureUnmute();
          })
          .catch(function () {
            /* State C: autoplay is off entirely. Wait for a click. */
            self.state = 'C';
            self.blocked = true;
            self.root.classList.add('is-blocked');
            self.sync();
          });
      });
};

  /* Try to make sound. Puts the player back to muted if the browser refuses,
     so a refusal never leaves a stopped or half-unmuted video behind. */
  Player.prototype.attemptSound = function (level) {
    var self = this;
    var v = this.v;
    /* Once the visitor has chosen silence, nothing here may override it. */
    if (this.userMuted) return;
    v.muted = false;
    v.volume = level;
    if (this.volRange) this.volRange.value = String(Math.round(level * 100));
    claimAudio(this);

    var revert = function () {
      v.muted = true;
      self.sync();
      self.wake();
    };

    var p = v.play();
    if (p && p.then) p.then(null, revert);
  };

  /* Every browser refuses audible autoplay until the visitor has interacted
     with the page, so State B can only ever step down to muted. Once a real
     gesture lands we are allowed sound, so restore it without making the
     visitor hunt for the unmute button. Listeners are torn down together
     because the first gesture satisfies the requirement for all of them. */
  Player.prototype.armFirstGestureUnmute = function () {
    if (this.gestureArmed) return;
    this.gestureArmed = true;
    var self = this;
    var types = ['pointerdown', 'keydown', 'touchstart'];
    var fire = function (ev) {
      if (self.state !== 'B') return;
      /* If the visitor has since pressed mute, that decision outranks us. */
      if (self.userMuted) {
        types.forEach(function (type) { document.removeEventListener(type, fire); });
        self.gestureArmed = false;
        return;
      }
      /* Deliberate clicks on the volume controls must win. Without this the
         mute button would mute and this handler would immediately unmute it
         again on the same click, which looks like a broken control. */
      if (ev && ev.target && ev.target.closest) {
        if (ev.target.closest('.hpl-vol, .hpl-vol-slider-btn, .hpl-vol-range')) return;
      }
      types.forEach(function (type) {
        document.removeEventListener(type, fire);
      });
      self.gestureArmed = false;
      self.unmuteNow();
    };
    types.forEach(function (type) {
      document.addEventListener(type, fire, { passive: true });
    });
  };

  Player.prototype.unmuteNow = function () {
    var self = this;
    var v = this.v;
    this.userMuted = false;
    this.attemptSound(1);
    this.root.classList.remove('show-unmute');
    /* Wait for the verdict so the notice can come back if audio was refused. */
    var p = v.play();
    if (p && p.then) {
      p.then(function () { self.root.classList.remove('show-unmute'); })
        .catch(function () { self.root.classList.add('show-unmute'); self.sync(); });
    }
    this.wake();
  };

  Player.prototype.toggle = function () {
    if (this.v.paused) {
      this.userPaused = false;
      if (this.v.muted) claimAudio(this);
      var p = this.v.play();
      if (p && p.catch) p.catch(function () {});
    } else {
      /* Remember the choice so scrolling back into view does not override it. */
      this.userPaused = true;
      this.v.pause();
    }
    this.wake();
  };

  Player.prototype.seekBy = function (d) {
    if (!this.v.duration) return;
    this.v.currentTime = Math.max(0, Math.min(this.v.duration, this.v.currentTime + d));
    this.sync();
    this.wake();
  };

  Player.prototype.seekFromEvent = function (e) {
    var r = this.track.getBoundingClientRect();
    if (!r.width) return;
    var pct = (pointX(e) - r.left) / r.width;
    pct = Math.max(0, Math.min(1, pct));
    if (this.v.duration) this.v.currentTime = pct * this.v.duration;
    this.paint();
  };

  Player.prototype.setVolume = function (val) {
    this.v.volume = val;
    this.v.muted = val === 0;
    /* Dragging the slider to zero is a mute decision, so stop the retries
       from immediately turning the sound back on behind their back. */
    this.userMuted = val === 0;
    if (this.userMuted) this.disarmAudibleRetry();
    if (!this.v.muted) claimAudio(this);
    this.sync();
    this.wake();
  };

  Player.prototype.toggleMute = function () {
    if (this.v.muted) {
      /* Coming back from muted at a stored 0 would look broken, so fall back
         to a sensible level rather than staying silent. */
      this.userMuted = false;
      this.disarmAudibleRetry();
      this.attemptSound(this.v.volume || 0.7);
    } else {
      this.userMuted = true;
      this.disarmAudibleRetry();
      this.forceMute();
    }
    this.sync();
    this.wake();
  };

  /* Called by another player claiming the audio channel. */
  Player.prototype.forceMute = function () {
    if (this.v.muted) return;
    this.v.muted = true;
    this.root.classList.remove('show-unmute');
    this.sync();
  };

  Player.prototype.closePopups = function () {
    if (this.volPop) this.volPop.hidden = true;
    if (this.volPopBtn) this.volPopBtn.setAttribute('aria-expanded', 'false');
    if (this.menu) this.menu.hidden = true;
    if (this.setBtn) this.setBtn.setAttribute('aria-expanded', 'false');
  };

  /* ---------- auto-hide ---------- */

  Player.prototype.wake = function () {
    var self = this;
    this.root.classList.remove('hide-controls');
    clearTimeout(this.hiddenTimer);
    this.hiddenTimer = setTimeout(function () { self.poke(); }, 2600);
  };

  Player.prototype.poke = function () {
    /* Bar only disappears during playback; when paused it stays put. */
    if (!this.v.paused) this.root.classList.add('hide-controls');
  };

  /* ---------- keyboard ---------- */

  Player.prototype.keys = function (e) {
    if (e.target.matches('input,button,[role="slider"]') && e.key === ' ') return;
    var k = e.key;
    if (k === ' ' || k === 'k') { e.preventDefault(); this.toggle(); }
    else if (k === 'm') { e.preventDefault(); this.toggleMute(); }
    else if (k === 'ArrowLeft') { e.preventDefault(); this.seekBy(-5); }
    else if (k === 'ArrowRight') { e.preventDefault(); this.seekBy(5); }
    else if (k === 'f') { this.toggleFullscreen(); }
  };

  Player.prototype.toggleFullscreen = function () {
    if (document.fullscreenElement) document.exitFullscreen();
    else if (this.root.requestFullscreen) this.root.requestFullscreen().catch(function () {});
  };

  /* ---------- paint ---------- */

  Player.prototype.paint = function () {
    var d = this.v.duration;
    var pct = d ? (this.v.currentTime / d) * 100 : 0;
    this.played.style.width = pct + '%';
    this.knob.style.left = pct + '%';
    this.track.setAttribute('aria-valuenow', Math.round(pct));
    this.track.setAttribute('aria-valuetext', fmt(this.v.currentTime) + ' of ' + fmt(d));
    this.cur.textContent = fmt(this.v.currentTime);

    if (this.v.buffered.length) {
      var end = this.v.buffered.end(this.v.buffered.length - 1);
      this.buffer.style.width = d ? (end / d) * 100 + '%' : '0%';
    }
  };

  Player.prototype.sync = function () {
    var v = this.v;
    var paused = v.paused;
    this.root.classList.toggle('is-paused', paused);
    this.root.classList.toggle('is-muted', v.muted);

    this.playBtn.setAttribute('aria-label', paused ? 'Play' : 'Pause');
    this.volBtn.setAttribute('aria-label', v.muted ? 'Unmute' : 'Mute');
    this.volBtn.classList.toggle('is-muted', v.muted);
    this.volBtn.setAttribute('aria-pressed', v.muted ? 'true' : 'false');
    if (this.volRange && document.activeElement !== this.volRange) {
      this.volRange.value = String(Math.round((v.muted ? 0 : v.volume) * 100));
    }

    this.paint();
    if (paused) this.wake();
  };

  /* ---------- boot ---------- */

  function boot() {
    document.querySelectorAll('[data-hpl-player]').forEach(function (root) {
      if (root.dataset.hplBound) return;
      root.dataset.hplBound = '1';
      root.player = new Player(root);
      /* Preload a little early for the hero player, nothing for the rest. */
      if (root.dataset.autoplay === '1') root.player.v.preload = 'metadata';
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
