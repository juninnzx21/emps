
var EMPS = {
    enum_cache: {},
    scroll_data: {},
    vue3_components: [],
    sp_id: 0,
    get_path_vars: function(){
        var l = window.location.href;
        return this.path_vars(l);
    },
    path_vars: function(l) {
        var x = l.split("?");
        l = x[0];
        var p = l.split('//');
        if (p.length > 1) {
            l = p[1].split('/');
        } else {
            l = l.split('/');
        }

        var last = l.pop();
        if (last != ''){
            l.push(last);
        }
        var rv = {};
        rv['pp'] = l[1];
        rv['key'] = l[2];
        rv['start'] = l[3];
        rv['ss'] = l[4];
        rv['sd'] = l[5];
        rv['sk'] = l[6];
        rv['sm'] = l[7];
        rv['sx'] = l[8];
        rv['sy'] = l[9];
        for(var v in rv){
            if(rv[v] == '-'){
                rv[v] = undefined;
            }
        }
        return rv;
    },
    elink: function(define, undefine){
        var path = this.get_path_vars();
        for (var v in define) {
            path[v] = define[v];
        }
        for (var i = 0; i < undefine.length; i++) {
            path[undefine[i]] = undefined;
        }
        return this.link(path);
    },
    link: function(path){
        var rv = [];
        var vars = ['pp', 'key', 'start', 'ss', 'sd', 'sk', 'sm', 'sx', 'sy'];
        for (var i = 0; i < vars.length; i++){
            if (path[vars[i]] !== undefined) {
                rv.push(path[vars[i]])
            }else{
                rv.push('-');
            }
        }
        while (rv.length > 0) {
            var c = rv.pop();
            if (c != '-') {
                rv.push(c);
                break;
            }
        }
        var url = rv.join("/");
        if (url) {
            return "/" + url + "/";
        }
        return "/";
    },
    soft_navi: function(title, href) {
        window.history.pushState({}, title, href);
    },
    load_css: function(href) {
        var head  = document.getElementsByTagName('head')[0];
        var link  = document.createElement('link');
        link.rel  = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        link.media = 'all';
        head.appendChild(link);
    },
    load_js: function(src, target, after) {
        var tag = document.createElement('script');
        tag.src = src;

        tag.onload = after;
        tag.onreadystatechange = after;

        target.appendChild(tag);
    },
    load_module: function(url, varname, module = false) {
        return new Promise((resolve, reject) => {
            if (module) {
                import(url).then((obj) => {
                    console.log("RESOLVED", obj);
                    resolve(obj);
                });
            } else {
                const script = document.createElement("script");
                script.src = url;
                script.onload = () => {
                    resolve(window[varname]);
                }

                script.onerror = () => reject(new Error(`Failed to load script: ${url}`));
                document.head.appendChild(script);
            }

        });
    },
    load_all_post_scripts: function() {
        $(".post-script").each(function(){
            var src = $(this).data("src");
            EMPS.load_js(src, document.body);
        });
    },
    format_size: function(bytes) {
        var units = [
            {size: 1000000000, suffix: ' GB'},
            {size: 1000000, suffix: ' MB'},
            {size: 1000, suffix: ' KB'}
        ];

        if (typeof bytes !== 'number') {
            return '';
        }
        var unit = true,
            i = 0,
            prefix,
            suffix;
        while (unit) {
            unit = units[i];
            prefix = unit.prefix || '';
            suffix = unit.suffix || '';
            if (i === units.length - 1 || bytes >= unit.size) {
                return prefix + (bytes / unit.size).toFixed(2) + suffix;
            }
            i += 1;
        }
    },
    load_enum: function(code, then) {
        if (this.enum_cache[code] !== undefined) {
            then(this.enum_cache[code]);
            return;
        }
        var that = this;
        axios
            .get("/json-loadenum/" + code + "/" + css_reset)
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        that.enum_cache[code] = data.enum;
                        then(data.enum);
                    }
                }else{
                    alert(data.message);
                }
            });
    },
    load_enum_str: function(code, then) {
        if (this.enum_cache[code] !== undefined) {
            then(this.enum_cache[code]);
            return;
        }
        var that = this;
        axios
            .get("/json-loadenum/" + code + "/?string=1")
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        that.enum_cache[code] = data.enum;
                        then(data.enum);
                    }
                }else{
                    alert(data.message);
                }
            });
    },
    vue_load_enum: function(that, code) {
        this.load_enum(code, function(e) {
            that.enums[code] = e;
        });
    },
    vue_load_enums: function(that, codes) {
        var x = codes.split(",");
        var l = x.length;
        for (var i = 0; i < l; i++) {
            this.vue_load_enum(that, x[i]);
        }
    },
    login: function() {
        $("#siteLoginModal").addClass("is-active");
        $.ajax({url: '/ensure_session/'});
    },
    open_modal: function(s) {
        $(s).addClass("is-active");
    },
    close_modal: function(s) {
        $(s).removeClass("is-active");
    },
    into_view: function(selector) {
        var $target = $(selector);
        if ($target.position()) {
            console.log($target, $target.position());
            if (


                (
                    $target.position().top + ((
                        window.innerHeight || document.documentElement.clientHeight
                    ) / 3) >
                    $(window).scrollTop() + (
                        window.innerHeight || document.documentElement.clientHeight
                    )
                ) ||
                (
                    $(window).scrollTop() > ($target.offset().top + 50)
                )


            )
            {
                $(window).scrollTop($(selector).offset().top - 50);
            }
        }
    },
    is_in_view: function(elem) {
        var $elem = $(elem);

        // Get the scroll position of the page.
        var scrollElem = ((navigator.userAgent.toLowerCase().indexOf('webkit') != -1) ? 'body' : 'html');
        var viewportTop = $(scrollElem).scrollTop();
        var viewportBottom = viewportTop + $(window).height();

        // Get the position of the element on the page.
        var elemTop = Math.round( $elem.offset().top );
        var elemBottom = elemTop + $elem.height();

        return ((elemTop < viewportBottom) && (elemBottom > viewportTop));
    },
    scroll_to_pos: function(selector) {
        var key = JSON.stringify(this.get_path_vars());
        var value = this.scroll_data[key];
        if (value !== undefined) {
            $(window).scrollTop(value.pos);
            console.log("Scroll restored: " + value + " / " + key);
        } else {
            this.into_view(selector);
        }
    },
    save_scroll_pos: function() {
        var key = JSON.stringify(this.get_path_vars());
        var value = $(window).scrollTop();
        this.scroll_data[key] = {pos: value, id: this.sp_id++};
        var keys = Object.keys(this.scroll_data);
        var l = keys.length;
        if (l > 3) {
            var min_sp = this.sp_id - 3;
            for (var i = 0; i < l; i++) {
                var ckey = keys[i];
                var o = this.scroll_data[ckey];
                if (o.sp_id < min_sp) {
                    this.scroll_data[ckey] = undefined;
                }
            }
        }
//        console.log("Scroll saved: " + value + " / " + key);
    },
    get_context: function(ref_type, ref_sub, ref_id, then) {
        if (ref_type <= 0) {
            return 0;
        }
        if (ref_sub <= 0) {
            return 0;
        }
        if (ref_id <= 0) {
            return 0;
        }
        axios
            .get("/json-context/" + ref_type + "-" + ref_sub + "-" + ref_id + "/")
            .then(function(response){
                var data = response.data;
                if (data.code == 'OK') {
                    if (then !== undefined) {
                        then(data.context_id);
                    }
                }else{
                    alert(data.message);
                }
            });
    },
    isIE: function() {
        ua = navigator.userAgent;
        /* MSIE used to detect old browsers and Trident used to newer ones*/
        var is_ie = ua.indexOf("MSIE ") > -1 || ua.indexOf("Trident/") > -1;

        return is_ie;
    },
    vue_component: function(name, url, obj) {
        let app = Vue;
        let loader = (resolve) => {
            obj.template = '#' + name + "-component-template";
            axios
                .get(url + css_reset)
                .then(function(response){
                    let data = response.data;
                    $(obj.template).html(data);
                    resolve(obj);
                });
        };
        if (EMPS.vue_version() == 3) {
            //console.log(Vue);
            app = EMPS.mainapp;
            let asyncComponent = Vue.defineAsyncComponent(
                () =>
                    new Promise(loader));
            this.vue3_components.push({name: name, comp: asyncComponent});
        } else {
            Vue.component(name, loader);
        }
    },
    vue_component_direct: function(name, comp) {
        if (EMPS.vue_version() == 3) {
            this.vue3_components.push({name: name, comp: comp});
        } else {
            Vue.component(name, comp);
        }
    },
    vue3_attach_components: function() {
        let app = EMPS.mainapp;
        while (this.vue3_components.length > 0) {
            let x = this.vue3_components.shift();
            console.log("ADDING COMPONENT", x.name, x.comp);
            app.component(x.name, x.comp);
        }
    },
    after_all_templates: null,
    after_template_loaded: function() {
        var all_loaded = true;
        $(".vue-template").each(function(){
            if (!$(this).data('loaded')) {
                all_loaded = false;
            }
        });
        if (all_loaded) {
            this.after_all_templates();
        }
    },
    load_vue_templates: function(after) {
        this.after_all_templates = after;
        if ($(".vue-template").length == 0) {
            after();
            return;
        }
        $(".vue-template").each(function(){
            var element = $(this);
            var url = element.data('src');
            axios
                .get(url + css_reset)
                .then(function(response){
                    var data = response.data;
                    element.html(data);
                    element.data('loaded', true);
                    EMPS.after_template_loaded();
                });
        });
    },
    guid: function() {
        function _p8(s) {
            var p = (Math.random().toString(16)+"000000000").substr(2,8);
            return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
        }
        return _p8() + _p8(true) + _p8(true) + _p8();
    },
    navigate: function(href) {
        window.location = href;
    },
    vue_version: function() {
        let version = Vue.version;
        let x = version.split(".");
        return parseInt(x[0]);
    }
};

