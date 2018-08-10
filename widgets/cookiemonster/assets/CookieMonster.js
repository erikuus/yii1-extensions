/*!
 * CookieMonster v1.0.1
 * Pawel Bizley Brzozowski
 * https://github.com/bizley-code/yii2-cookiemonster
 */
var CookieMonster = {
    init: function(options) {
        if (options === undefined) {
            options = {};
        }
        this.options(options);
        this.monit = jQuery('.' + this.classOuter);
        this.button = this.monit.find('.' + this.classButton);
        this.checkCookie();
    },
    defaults: {
        classOuter: 'CookieMonsterBox',
        classButton: 'CookieMonsterOk',
        domain: null,
        maxAge: null,
        expires: 30,
        name: 'CookieMonsterAgreed',
        path: '/',
        secure: false,
        value: 'true'
    },
    options: function(options) {
        this.classOuter = options.classOuter || this.defaults.classOuter;
        this.classButton = options.classButton || this.defaults.classButton;
        this.domain =  options.domain || this.defaults.domain;
        this.maxAge = options.maxAge || this.defaults.maxAge;
        this.expires = options.expires || this.defaults.expires;
        this.name = this.defaults.name;
        this.secure = options.secure || this.defaults.secure;
        this.value = this.defaults.value;
    },
    agree: function(e) {
        e.preventDefault();
        this.createCookie();
        this.hideMonit();
    },
    bind: function() {
        this.button.on('click', jQuery.proxy(this.agree, this));
    },
    checkCookie: function() {
        if (this.readCookie() !== this.value) {
            this.showMonit();
        }
    },
    createCookie: function() {
        document.cookie = this.name 
                            + '=' 
                            + this.value 
                            + this.setExpires() 
                            + this.setPath() 
                            + this.setDomain() 
                            + this.setMaxAge() 
                            + this.setSecure();
    },
    hideMonit: function() {
        this.monit.remove();
    },
    readCookie: function() {
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(this.name + '=') === 0) {
                return c.substring(this.name.length + 1, c.length);
            }
        }
        return null;
    },
    setDomain: function() {
        if (this.domain) {
            return '; domain=' + encodeURIComponent(this.domain);
        }
        return '';
    },
    setExpires: function() {
        if (this.expires) {
            var date = new Date();
            date.setTime(date.getTime() + (this.expires * 24 * 60 * 60 * 1000));
            return '; expires=' + date.toGMTString();
        }
        return '';
    },
    setMaxAge: function() {
        if (this.maxAge) {
            return '; max-age=' + this.maxAge;
        }
        return '';
    },
    setPath: function() {
        return '; path=' + (this.path ? this.path : '/');
    },
    setSecure: function() {
        if (this.secure) {
            return '; secure';
        }
        return '';
    },
    showMonit: function() {
        this.monit.css('display', 'block');
        this.bind();
    }    
};
