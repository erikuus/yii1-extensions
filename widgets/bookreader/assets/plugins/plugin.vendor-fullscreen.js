!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=22)}([function(e,t,n){(function(t){var n=function(e){return e&&e.Math==Math&&e};e.exports=n("object"==typeof globalThis&&globalThis)||n("object"==typeof window&&window)||n("object"==typeof self&&self)||n("object"==typeof t&&t)||Function("return this")()}).call(this,n(24))},function(e,t){e.exports=function(e){try{return!!e()}catch(e){return!0}}},function(e,t){var n={}.hasOwnProperty;e.exports=function(e,t){return n.call(e,t)}},function(e,t,n){var r=n(1);e.exports=!r((function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a}))},function(e,t,n){var r=n(12),o=n(13);e.exports=function(e){return r(o(e))}},function(e,t){e.exports=function(e){return"object"==typeof e?null!==e:"function"==typeof e}},function(e,t,n){var r=n(3),o=n(16),u=n(11);e.exports=r?function(e,t,n){return o.f(e,t,u(1,n))}:function(e,t,n){return e[t]=n,e}},function(e,t,n){var r=n(29),o=n(30);(e.exports=function(e,t){return o[e]||(o[e]=void 0!==t?t:{})})("versions",[]).push({version:"3.3.3",mode:r?"pure":"global",copyright:"© 2019 Denis Pushkarev (zloirock.ru)"})},function(e,t,n){var r=n(0),o=n(6);e.exports=function(e,t){try{o(r,e,t)}catch(n){r[e]=t}return t}},function(e,t,n){var r=n(0),o=n(10).f,u=n(6),i=n(28),c=n(8),l=n(35),f=n(46);e.exports=function(e,t){var n,s,a,p,d,v=e.target,h=e.global,y=e.stat;if(n=h?r:y?r[v]||c(v,{}):(r[v]||{}).prototype)for(s in t){if(p=t[s],a=e.noTargetGet?(d=o(n,s))&&d.value:n[s],!f(h?s:v+(y?".":"#")+s,e.forced)&&void 0!==a){if(typeof p==typeof a)continue;l(p,a)}(e.sham||a&&a.sham)&&u(p,"sham",!0),i(n,s,p,e)}}},function(e,t,n){var r=n(3),o=n(25),u=n(11),i=n(4),c=n(14),l=n(2),f=n(15),s=Object.getOwnPropertyDescriptor;t.f=r?s:function(e,t){if(e=i(e),t=c(t,!0),f)try{return s(e,t)}catch(e){}if(l(e,t))return u(!o.f.call(e,t),e[t])}},function(e,t){e.exports=function(e,t){return{enumerable:!(1&e),configurable:!(2&e),writable:!(4&e),value:t}}},function(e,t,n){var r=n(1),o=n(26),u="".split;e.exports=r((function(){return!Object("z").propertyIsEnumerable(0)}))?function(e){return"String"==o(e)?u.call(e,""):Object(e)}:Object},function(e,t){e.exports=function(e){if(null==e)throw TypeError("Can't call method on "+e);return e}},function(e,t,n){var r=n(5);e.exports=function(e,t){if(!r(e))return e;var n,o;if(t&&"function"==typeof(n=e.toString)&&!r(o=n.call(e)))return o;if("function"==typeof(n=e.valueOf)&&!r(o=n.call(e)))return o;if(!t&&"function"==typeof(n=e.toString)&&!r(o=n.call(e)))return o;throw TypeError("Can't convert object to primitive value")}},function(e,t,n){var r=n(3),o=n(1),u=n(27);e.exports=!r&&!o((function(){return 7!=Object.defineProperty(u("div"),"a",{get:function(){return 7}}).a}))},function(e,t,n){var r=n(3),o=n(15),u=n(17),i=n(14),c=Object.defineProperty;t.f=r?c:function(e,t,n){if(u(e),t=i(t,!0),u(n),o)try{return c(e,t,n)}catch(e){}if("get"in n||"set"in n)throw TypeError("Accessors not supported");return"value"in n&&(e[t]=n.value),e}},function(e,t,n){var r=n(5);e.exports=function(e){if(!r(e))throw TypeError(String(e)+" is not an object");return e}},function(e,t,n){var r=n(7);e.exports=r("native-function-to-string",Function.toString)},function(e,t){e.exports={}},function(e,t){var n=Math.ceil,r=Math.floor;e.exports=function(e){return isNaN(e=+e)?0:(e>0?r:n)(e)}},function(e,t){e.exports="\t\n\v\f\r                　\u2028\u2029\ufeff"},function(e,t,n){"use strict";n.r(t),n.d(t,"getFullscreenElement",(function(){return o})),n.d(t,"isFullscreenActive",(function(){return u})),n.d(t,"exitFullscreen",(function(){return i})),n.d(t,"requestFullscreen",(function(){return c})),n.d(t,"fullscreenAllowed",(function(){return l})),n.d(t,"bindFullscreenChangeListener",(function(){return f})),n.d(t,"isMobile",(function(){return s}));var r;n(23),n(48);if(!s()){jQuery.extend(BookReader.defaultOptions,{enableVendorFullscreenPlugin:!0}),BookReader.prototype.setup=(r=BookReader.prototype.setup,function(e){r.call(this,e),this.isVendorFullscreenActive=!1}),BookReader.prototype.getInitialMode=function(e){return function(t){var n=e.call(this,t);return this.isVendorFullscreenActive&&(n=this.constMode1up),n}}(BookReader.prototype.getInitialMode),BookReader.prototype.init=function(e){return function(){e.call(this),l()&&f(this,(function(e){e.data.resize(),e.data.updateBrClasses();var t=$("#cboxOverlay"),n=$("#colorbox");u()?$(o()).append(t).append(n):$(document.body).append(t).append(n)}))}}(BookReader.prototype.init),BookReader.prototype.enterFullWindow=function(){var e=this;this.refs.$brContainer.css("opacity",0),$(window).width()<=this.onePageMinBreakpoint&&this.switchMode(this.constMode1up),this.isVendorFullscreenActive=!0,this.updateBrClasses(),this.resize(),this.jumpToIndex(this.currentIndex()),this.refs.$brContainer.animate({opacity:1},400,"linear"),$(document).on("keyup.".concat(".bookreader_vendor-fullscreen"),(function(t){27===t.keyCode&&e.exitFullScreen()}))},BookReader.prototype.exitFullWindow=function(){this.refs.$brContainer.css("opacity",0),$(document).off("keyup.bookreader_vendor-fullscreen"),this.isFullscreenActive=!1,this.updateBrClasses(),this.resize(),this.refs.$brContainer.animate({opacity:1},400,"linear")},BookReader.prototype.isFullscreen=function(){return u()||this.isVendorFullscreenActive},BookReader.prototype.toggleFullscreen=function(){this.isFullscreen()?l()?i():this.exitFullWindow():l()?c(this.refs.$br[0]):this.enterFullWindow()},BookReader.util.isMobile=s,BookReader.util.getFullscreenElement=o,BookReader.util.bindFullscreenChangeListener=f,BookReader.util.fullscreenAllowed=l,BookReader.util.requestFullscreen=c,BookReader.util.exitFullscreen=i,BookReader.util.isFullscreenActive=u}function o(){return document.fullscreenElement||document.webkitFullscreenElement||document.mozFullScreenElement||document.msFullscreenElement}function u(){var e=o();return null!=e}function i(){document.exitFullscreen?document.exitFullscreen():document.webkitExitFullscreen?document.webkitExitFullscreen():document.mozCancelFullScreen?document.mozCancelFullScreen():document.msExitFullscreen&&document.msExitFullscreen()}function c(e){e.requestFullscreen?e.requestFullscreen():e.webkitRequestFullscreen?e.webkitRequestFullscreen():e.mozRequestFullScreen?e.mozRequestFullScreen():e.msRequestFullscreen&&e.msRequestFullscreen()}function l(){return document.fullscreenEnabled||document.webkitFullscreenEnabled||document.mozFullScreenEnabled||document.msFullScreenEnabled}function f(e,t){var n="fullscreenchange ",r=$.trim(n+["webkit","moz","ms"].join(n)+n);$(document).bind(r,e,t)}function s(){return void 0!==window.orientation||-1!==navigator.userAgent.indexOf("IEMobile")}},function(e,t,n){"use strict";var r=n(9),o=n(12),u=n(4),i=n(47),c=[].join,l=o!=Object,f=i("join",",");r({target:"Array",proto:!0,forced:l||f},{join:function(e){return c.call(u(this),void 0===e?",":e)}})},function(e,t){var n;n=function(){return this}();try{n=n||new Function("return this")()}catch(e){"object"==typeof window&&(n=window)}e.exports=n},function(e,t,n){"use strict";var r={}.propertyIsEnumerable,o=Object.getOwnPropertyDescriptor,u=o&&!r.call({1:2},1);t.f=u?function(e){var t=o(this,e);return!!t&&t.enumerable}:r},function(e,t){var n={}.toString;e.exports=function(e){return n.call(e).slice(8,-1)}},function(e,t,n){var r=n(0),o=n(5),u=r.document,i=o(u)&&o(u.createElement);e.exports=function(e){return i?u.createElement(e):{}}},function(e,t,n){var r=n(0),o=n(7),u=n(6),i=n(2),c=n(8),l=n(18),f=n(31),s=f.get,a=f.enforce,p=String(l).split("toString");o("inspectSource",(function(e){return l.call(e)})),(e.exports=function(e,t,n,o){var l=!!o&&!!o.unsafe,f=!!o&&!!o.enumerable,s=!!o&&!!o.noTargetGet;"function"==typeof n&&("string"!=typeof t||i(n,"name")||u(n,"name",t),a(n).source=p.join("string"==typeof t?t:"")),e!==r?(l?!s&&e[t]&&(f=!0):delete e[t],f?e[t]=n:u(e,t,n)):f?e[t]=n:c(t,n)})(Function.prototype,"toString",(function(){return"function"==typeof this&&s(this).source||l.call(this)}))},function(e,t){e.exports=!1},function(e,t,n){var r=n(0),o=n(8),u=r["__core-js_shared__"]||o("__core-js_shared__",{});e.exports=u},function(e,t,n){var r,o,u,i=n(32),c=n(0),l=n(5),f=n(6),s=n(2),a=n(33),p=n(19),d=c.WeakMap;if(i){var v=new d,h=v.get,y=v.has,m=v.set;r=function(e,t){return m.call(v,e,t),t},o=function(e){return h.call(v,e)||{}},u=function(e){return y.call(v,e)}}else{var b=a("state");p[b]=!0,r=function(e,t){return f(e,b,t),t},o=function(e){return s(e,b)?e[b]:{}},u=function(e){return s(e,b)}}e.exports={set:r,get:o,has:u,enforce:function(e){return u(e)?o(e):r(e,{})},getterFor:function(e){return function(t){var n;if(!l(t)||(n=o(t)).type!==e)throw TypeError("Incompatible receiver, "+e+" required");return n}}}},function(e,t,n){var r=n(0),o=n(18),u=r.WeakMap;e.exports="function"==typeof u&&/native code/.test(o.call(u))},function(e,t,n){var r=n(7),o=n(34),u=r("keys");e.exports=function(e){return u[e]||(u[e]=o(e))}},function(e,t){var n=0,r=Math.random();e.exports=function(e){return"Symbol("+String(void 0===e?"":e)+")_"+(++n+r).toString(36)}},function(e,t,n){var r=n(2),o=n(36),u=n(10),i=n(16);e.exports=function(e,t){for(var n=o(t),c=i.f,l=u.f,f=0;f<n.length;f++){var s=n[f];r(e,s)||c(e,s,l(t,s))}}},function(e,t,n){var r=n(37),o=n(39),u=n(45),i=n(17);e.exports=r("Reflect","ownKeys")||function(e){var t=o.f(i(e)),n=u.f;return n?t.concat(n(e)):t}},function(e,t,n){var r=n(38),o=n(0),u=function(e){return"function"==typeof e?e:void 0};e.exports=function(e,t){return arguments.length<2?u(r[e])||u(o[e]):r[e]&&r[e][t]||o[e]&&o[e][t]}},function(e,t,n){e.exports=n(0)},function(e,t,n){var r=n(40),o=n(44).concat("length","prototype");t.f=Object.getOwnPropertyNames||function(e){return r(e,o)}},function(e,t,n){var r=n(2),o=n(4),u=n(41).indexOf,i=n(19);e.exports=function(e,t){var n,c=o(e),l=0,f=[];for(n in c)!r(i,n)&&r(c,n)&&f.push(n);for(;t.length>l;)r(c,n=t[l++])&&(~u(f,n)||f.push(n));return f}},function(e,t,n){var r=n(4),o=n(42),u=n(43),i=function(e){return function(t,n,i){var c,l=r(t),f=o(l.length),s=u(i,f);if(e&&n!=n){for(;f>s;)if((c=l[s++])!=c)return!0}else for(;f>s;s++)if((e||s in l)&&l[s]===n)return e||s||0;return!e&&-1}};e.exports={includes:i(!0),indexOf:i(!1)}},function(e,t,n){var r=n(20),o=Math.min;e.exports=function(e){return e>0?o(r(e),9007199254740991):0}},function(e,t,n){var r=n(20),o=Math.max,u=Math.min;e.exports=function(e,t){var n=r(e);return n<0?o(n+t,0):u(n,t)}},function(e,t){e.exports=["constructor","hasOwnProperty","isPrototypeOf","propertyIsEnumerable","toLocaleString","toString","valueOf"]},function(e,t){t.f=Object.getOwnPropertySymbols},function(e,t,n){var r=n(1),o=/#|\.prototype\./,u=function(e,t){var n=c[i(e)];return n==f||n!=l&&("function"==typeof t?r(t):!!t)},i=u.normalize=function(e){return String(e).replace(o,".").toLowerCase()},c=u.data={},l=u.NATIVE="N",f=u.POLYFILL="P";e.exports=u},function(e,t,n){"use strict";var r=n(1);e.exports=function(e,t){var n=[][e];return!n||!r((function(){n.call(null,t||function(){throw 1},1)}))}},function(e,t,n){"use strict";var r=n(9),o=n(49).trim;r({target:"String",proto:!0,forced:n(50)("trim")},{trim:function(){return o(this)}})},function(e,t,n){var r=n(13),o="["+n(21)+"]",u=RegExp("^"+o+o+"*"),i=RegExp(o+o+"*$"),c=function(e){return function(t){var n=String(r(t));return 1&e&&(n=n.replace(u,"")),2&e&&(n=n.replace(i,"")),n}};e.exports={start:c(1),end:c(2),trim:c(3)}},function(e,t,n){var r=n(1),o=n(21);e.exports=function(e){return r((function(){return!!o[e]()||"​᠎"!="​᠎"[e]()||o[e].name!==e}))}}]);