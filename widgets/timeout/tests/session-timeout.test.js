// node widgets/timeout/tests/session-timeout.test.js
// Run the actual browser script against a shared server and deterministic clocks.
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/session-timeout.js'), 'utf8');

function environment(storageEnabled = true) {
  let now = 1000000, sequence = 0;
  const timers = new Map(), tabs = [];
  const server = {deadline: now + 2700000, key: 'login-one', calls: [], fail: false, malformed: false};
  function later(fn, delay) {
    const id = ++sequence;
    timers.set(id, {fn, at: now + delay});
    return id;
  }
  function advance(ms) {
    const end = now + ms;
    let steps = 0;
    while (true) {
      const next = [...timers.entries()].sort((a, b) => a[1].at - b[1].at)[0];
      if (!next || next[1].at > end) break;
      assert(++steps < 20000, 'Runaway timer loop');
      now = next[1].at;
      timers.delete(next[0]);
      next[1].fn();
    }
    now = end;
  }
  function tab(offset = 0) {
    const elements = {}, windowEvents = {}, documentEvents = {};
    const result = {redirects: [], dialog: null, requests: []};
    const document = {hidden: false};
    function element(id) {
      const el = {
        id, value: '', properties: {},
        text(value) { this.value = value; return this; },
        prop(key, value) { this.properties[key] = value; return this; },
        appendTo() { return this; },
        remove() { return this; },
        dialog(options) {
          if (options === 'destroy') result.dialog = null;
          else result.dialog = options;
          return this;
        }
      };
      if (id) elements[id] = el;
      return el;
    }
    function events(handlers) {
      return {
        bind(name, fn) { handlers[name] = fn; return this; },
        unbind(namespace) {
          Object.keys(handlers).filter(name => name.endsWith(namespace)).forEach(name => delete handlers[name]);
          return this;
        }
      };
    }
    const window = {
      setTimeout: later,
      clearTimeout(id) { timers.delete(id); },
      localStorage: {setItem(key, value) {
        if (!storageEnabled) throw new Error('Storage denied');
        tabs.filter(other => other !== result).forEach(other => other.event('storage', {key, newValue: value}));
      }}
    };
    let location = {pathname: '/form', search: '?id=1'};
    Object.defineProperty(window, 'location', {
      get: () => location,
      set: value => { result.redirects.push(value); }
    });
    function $(value) {
      if (value === window) return events(windowEvents);
      if (value === document) return events(documentEvents);
      if (value[0] === '<') {
        const match = value.match(/id="([^"]+)"/);
        return element(match && match[1]);
      }
      return elements[value.slice(1)] || element(value.slice(1));
    }
    $.extend = Object.assign;
    $.ajax = options => {
      result.requests.push(options);
      const id = later(() => {
        server.calls.push(options.url);
        if (server.fail) return options.error({status: 0});
        if (server.deadline <= now) return options.error({status: 401});
        if (options.url === '/keepalive') {
          if (options.data.sessionKey !== server.key) return options.error({status: 409});
          server.deadline = now + 2700000;
        }
        options.success(server.malformed ? '<html>login</html>' : {
          serverTime: Math.floor(now / 1000), expiresAt: Math.floor(server.deadline / 1000), sessionKey: server.key
        });
      }, 0);
      return {abort() { timers.delete(id); }};
    };
    window.jQuery = $;
    vm.runInNewContext(source, {window, document, Date: class {getTime() {return now + offset;}}, Math});
    const settings = {status_url: '/status', keep_alive_url: '/keepalive', session_key: server.key,
      expired_redirect_url: '/login', logout_redirect_url: '/logout', countdown: 60};
    result.event = (name, event = {}) => {
      const handlers = name === 'visibilitychange' ? documentEvents : windowEvents;
      if (handlers[name + '.timeoutDialog']) handlers[name + '.timeoutDialog'](event);
    };
    result.keepAlive = () => result.dialog.buttons['keep-alive-button'].click();
    result.signOut = () => result.dialog.buttons['sign-out-button'].click();
    result.stop = () => $.timeoutDialogInstance.stop();
    result.reinitialize = () => $.timeoutDialog(settings);
    result.text = id => elements[id].value;
    result.disabled = () => elements['timeout-keep-signin-btn'].properties.disabled;
    tabs.push(result);
    $.timeoutDialog(settings);
    advance(0);
    return result;
  }
  return {server, tab, advance, sleep(ms) { now += ms; }, now: () => now};
}

for (const storageEnabled of [true, false]) {
  const env = environment(storageEnabled), a = env.tab();
  env.advance(20 * 60000);
  env.server.deadline = env.now() + 2700000; // Ordinary server-rendered page in tab B.
  const b = env.tab();
  env.advance(25 * 60000);
  assert.equal(a.redirects.length, 0, 'Old tab must not log out a renewed session');
  assert.equal(b.redirects.length, 0);
  assert(!a.dialog && !b.dialog, 'Fresh server deadline must defer old warnings');
  env.advance(19 * 60000);
  assert(a.dialog && b.dialog);
  b.keepAlive();
  env.advance(0);
  assert(!b.dialog);
  env.advance(60000);
  assert.equal(a.redirects.length, 0, 'Renewal in B must protect A at its old deadline');
  assert(!a.dialog);
  a.stop(); b.stop();
}

{
  const env = environment(), tab = env.tab(6 * 60 * 60000); // Six-hour clock skew.
  const deadline = env.server.deadline;
  env.advance(44 * 60000);
  assert(tab.dialog);
  assert.equal(env.server.deadline, deadline, 'Status/warning checks must not renew');
  env.advance(60000);
  assert(tab.redirects[0].startsWith('/login?'));
  assert(!tab.redirects.some(url => url.startsWith('/logout')));
  assert.equal(env.server.deadline, deadline);
}

{
  const env = environment(), tab = env.tab();
  env.advance(44 * 60000);
  env.server.fail = true;
  tab.keepAlive();
  assert(tab.dialog, 'Do not hide the dialog until renewal succeeds');
  assert(tab.disabled(), 'Prevent duplicate renewal requests');
  env.advance(0);
  assert(tab.dialog && !tab.disabled());
  assert(tab.text('timeout-error').length > 0);
  assert.equal(tab.redirects.length, 0, 'Network failure is not proof of expiry');
  env.server.fail = false;
  tab.keepAlive();
  env.advance(0);
  assert(!tab.dialog);
  tab.stop();
}

{
  const env = environment(), tab = env.tab();
  env.sleep(46 * 60000); // Browser suspended: no scheduled callbacks execute.
  tab.event('visibilitychange');
  env.advance(0);
  assert(tab.redirects[0].startsWith('/login?'), 'Resume must recheck expired authentication');
}

{
  const env = environment(), tab = env.tab();
  env.advance(44 * 60000);
  env.server.key = 'login-two';
  const deadline = env.server.deadline;
  tab.keepAlive();
  env.advance(0);
  assert(tab.redirects[0].startsWith('/login?'));
  assert.equal(env.server.deadline, deadline, 'Old page must not renew a replacement login');
}

{
  const env = environment(), tab = env.tab();
  env.server.malformed = true;
  env.advance(44 * 60000);
  assert(tab.dialog && tab.text('timeout-error'));
  assert.equal(tab.redirects.length, 0);
  tab.signOut();
  assert(tab.redirects[0].startsWith('/logout?'), 'Explicit sign-out remains destructive');
}

{
  const env = environment(), tab = env.tab();
  tab.reinitialize();
  env.advance(0);
  const calls = env.server.calls.length;
  env.advance(44 * 60000);
  assert.equal(env.server.calls.length, calls + 1, 'Reinitialization must cancel old timers');
  assert(tab.dialog);
  tab.stop();
}
console.log('PASS: cross-tab renewal, disabled storage, clock skew, expiry, resume, network failure, replacement login, malformed response and duplicate initialization.');
