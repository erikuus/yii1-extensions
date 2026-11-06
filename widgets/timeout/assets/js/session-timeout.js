/* Server-authoritative companion to timeout-dialog.js (MIT; see ../../LICENSE).
 * Opt in with XTimeoutDialog.statusUrl. Legacy consumers retain their protocol.
 */
(function ($) {
  $.timeoutDialog = function (options) {
    var settings = $.extend({
      countdown: 60,
      title: 'Your session is about to expire!',
      message: 'You will be logged out in {0} seconds.',
      question: 'Do you want to stay signed in?',
      keep_alive_button_text: 'Yes, Keep me signed in',
      sign_out_button_text: 'No, Sign me out',
      network_error_message: 'Unable to check your session. Please try again.',
      expired_redirect_url: '/',
      logout_redirect_url: '/',
      logout_url: null,
      dialog_width: 350
    }, options);
    var timer, request, deadline, dialog, stopped = false, lastCheck = 0;
    var storageKey = 'timeout-dialog:' + settings.session_key;
    var namespace = '.timeoutDialog';

    if ($.timeoutDialogInstance) {
      $.timeoutDialogInstance.stop();
    }
    $.timeoutDialogInstance = {stop: stop};

    function now() {
      return new Date().getTime();
    }

    function destroyDialog() {
      if (dialog) {
        dialog.dialog('destroy').remove();
        dialog = null;
      }
    }

    function stop() {
      stopped = true;
      window.clearTimeout(timer);
      $(window).unbind(namespace);
      $(document).unbind(namespace);
      if (request) {
        request.abort();
      }
      destroyDialog();
    }

    function redirect(url, expired) {
      var separator = url.indexOf('?') === -1 ? '?' : '&';
      window.location = url + separator + 'next=' +
        encodeURIComponent(window.location.pathname + window.location.search) +
        (expired ? '&timeout=t' : '');
    }

    function expired() {
      stop();
      // Never call logout here: another tab may already have renewed or logged in.
      redirect(settings.expired_redirect_url, true);
    }

    function signOut() {
      stop();
      if (settings.logout_url) {
        $.post(settings.logout_url).always(function () {
          redirect(settings.logout_redirect_url, false);
        });
      } else {
        redirect(settings.logout_redirect_url, false);
      }
    }

    function showDialog(error) {
      if (!dialog) {
        dialog = $('<div id="timeout-dialog">').appendTo('body');
        $('<p id="timeout-message">').appendTo(dialog);
        $('<p id="timeout-question">').text(settings.question).appendTo(dialog);
        $('<p id="timeout-error" role="alert">').appendTo(dialog);
        dialog.dialog({
          modal: true,
          width: settings.dialog_width,
          minHeight: 'auto',
          zIndex: 10000,
          closeOnEscape: false,
          draggable: false,
          resizable: false,
          dialogClass: 'timeout-dialog',
          title: settings.title,
          buttons: {
            'keep-alive-button': {
              text: settings.keep_alive_button_text,
              id: 'timeout-keep-signin-btn',
              click: function () { check(true); }
            },
            'sign-out-button': {
              text: settings.sign_out_button_text,
              id: 'timeout-sign-out-button',
              click: signOut
            }
          }
        });
      }
      $('#timeout-error').text(error ? settings.network_error_message : '');
      $('#timeout-keep-signin-btn').prop('disabled', !!request);
      if (error) {
        $('#timeout-message').text(settings.title);
      } else {
        $('#timeout-message').text(settings.message.replace('{0}', Math.max(0, Math.ceil((deadline - now()) / 1000))));
      }
    }

    function schedule() {
      window.clearTimeout(timer);
      var remaining = deadline - now();
      if (remaining <= 0) {
        check(false);
      } else if (remaining <= settings.countdown * 1000) {
        showDialog(false);
        timer = window.setTimeout(schedule, Math.min(1000, remaining));
      } else {
        destroyDialog();
        // Recheck before warning: ordinary AJAX requests may have renewed expiry.
        timer = window.setTimeout(function () { check(false); }, remaining - settings.countdown * 1000);
      }
    }

    function announce() {
      try {
        window.localStorage.setItem(storageKey, String(now()) + ':' + Math.random());
      } catch (error) {
        // Storage can be disabled. Server checks still prevent stale-tab logout.
      }
    }

    function retry() {
      showDialog(true);
      timer = window.setTimeout(function () { check(false); }, 15000);
    }

    function check(renew, broadcast) {
      if (stopped || request) {
        return;
      }
      window.clearTimeout(timer);
      var started = now();
      lastCheck = started;
      request = $.ajax({
        url: renew ? settings.keep_alive_url : settings.status_url,
        type: 'GET',
        data: {format: 'json', sessionKey: settings.session_key},
        dataType: 'json',
        cache: false,
        global: false,
        timeout: 15000,
        success: function (data) {
          request = null;
          if (stopped) {
            return;
          }
          if (!data || typeof data.serverTime !== 'number' || typeof data.expiresAt !== 'number' || !data.sessionKey) {
            retry();
            return;
          }
          if (data.sessionKey !== settings.session_key || data.expiresAt <= data.serverTime) {
            expired();
            return;
          }
          // Use server-relative time; local clock offsets and network delay cannot grant extra time.
          deadline = started + (data.expiresAt - data.serverTime) * 1000;
          if (renew || broadcast) {
            announce();
          }
          schedule();
        },
        error: function (xhr) {
          request = null;
          if (stopped) {
            return;
          }
          if (xhr.status === 401 || xhr.status === 409) {
            expired();
          } else {
            retry();
          }
        }
      });
      if (dialog) {
        $('#timeout-keep-signin-btn').prop('disabled', true);
      }
    }

    $(window).bind('storage' + namespace, function (event) {
      var change = event.originalEvent || event;
      if (change.key === storageKey) {
        check(false);
      }
    });
    $(window).bind('focus' + namespace, function () {
      if (now() - lastCheck > 1000) {
        check(false);
      }
    });
    $(document).bind('visibilitychange' + namespace, function () {
      if (!document.hidden && now() - lastCheck > 1000) {
        check(false);
      }
    });
    check(false, true);
  };
}(window.jQuery));
