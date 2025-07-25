var vuev, app;

emps_scripts.push(function(){
    Vue.component('dynamic', {
        props: ['template'],
        data() {
            return { compiled: null }
        },
        watch: {
            template: {
                immediate: true,
                handler(newTemplate) {
                    // Compile the template string into render functions
                    this.compiled = Vue.compile("<div>" + newTemplate + "</div>")
                    // Force a re-render by replacing the component’s render function
                    this.$options.render = this.compiled.render
                    this.$options.staticRenderFns = this.compiled.staticRenderFns
                    this.$forceUpdate();
                    console.log("FORCING COMPONENT UPDATE");
                }
            }
        },
        render(createElement) {
            // If the template hasn’t been compiled yet, render an empty div.
            return this.compiled ? this.compiled.render.call(this, createElement) : createElement('div')
        }
    });

    vuev = new Vue();
    app = new Vue({
        el: '#preview-app',
        data: function() {
            return {
                current: {},
                html: "",
                dckey: "",
                parents: [],
            };
        },
        mounted: function(){
            let e = $("#preview-app")[0];
            this.html = window.html;
            this.dckey = EMPS.guid();
            e.addEventListener("mousemove", this.mousemove);
            e.addEventListener("mouseout", this.mouseout);
            e.addEventListener("click", this.click);

            var that = this;
            window.addEventListener('message', function(event) {
                console.log("MESSAGE", event);
                if (event.data && event.data.type === 'updateHTML') {
                    // Update the content based on the received message
                    that.html = event.data.newHTML;
                    that.dckey = EMPS.guid();
                    that.$forceUpdate();
                    console.log("NEW HTML", that.html);
                    //document.getElementById('contents').innerHTML = event.data.newHTML;
                }
            });
        },
        methods: {
            mouseout: function(e) {
                this.$refs.l1.style.display = "none";
                this.$refs.l2.style.display = "none";
                this.$refs.l3.style.display = "none";
                this.$refs.l4.style.display = "none";
            },
            mousemove: function(e) {
                if (this.current.id === e.target.id) {
                    return;
                }
                this.$refs.l1.style.display = "none";
                this.$refs.l2.style.display = "none";
                this.$refs.l3.style.display = "none";
                this.$refs.l4.style.display = "none";
                if (e.target.id == "preview-app") {
                    this.current = {};
                    return;
                }
                let c = e.target;
                let id = c.id;
                while (!this.valid_id(id)) {
                    c = ($(c).parent())[0];
                    if (!c) {
                        return;
                    }
                    id = c.id;
                }
                this.current = c;
//                console.log("CURRENT", c);

                let f = this.$refs.l1;
                f.style.top = this.current.offsetTop + "px";
                f.style.left = this.current.offsetLeft + "px";
                f.style.width = this.current.offsetWidth + "px";
                f.style.height = this.current.offsetHeight + "px";
                f.style.display = "block";

                f = this.$refs.plst;
                f.style.top = (this.current.offsetTop + this.current.offsetHeight - 34) + "px";
                f.style.left = (this.current.offsetLeft + 4) + "px";
                f.style.display = "block";

                this.parents = [];
                this.parents.push(this.current);
                let p = this.highlight_parent(($(this.current).parent())[0], 2);
                if (!p) {
                    return;
                }
                p = this.highlight_parent(($(p).parent())[0], 3);
                if (!p) {
                    return;
                }
                this.highlight_parent(($(p).parent())[0], 4);
            },
            highlight_parent: function(o, level) {
                if (!o) {
                    return;
                }
                let c = o;
                let id = c.id;
                while (!this.valid_id(id)) {
                    c = ($(c).parent())[0];
                    if (!c) {
                        return;
                    }
                    id = c.id;
                }
                let f = this.$refs['l' + level];
                f.style.top = c.offsetTop + "px";
                f.style.left = c.offsetLeft + "px";
                f.style.width = c.offsetWidth + "px";
                f.style.height = c.offsetHeight + "px";
                f.style.display = "block";
//                console.log("F", f.style);
                this.parents.push(c);
                return c;
            },
            valid_id: function(id) {
                if (!id) {
                    return false;
                }
                let x = id.split("el_");
                if (x[1]) {
                    return true;
                }
                return false;
            },
            click: function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!this.current.id) {
                    return;
                }
                window.parent.postMessage({code: 'click', id: this.current.id}, '*');
                //alert(this.current.id);
            },
            click_parent: function(el) {
                if (!el.id) {
                    return;
                }
                window.parent.postMessage({code: 'click', id: el.id}, '*');
            }
        }
    });
    EMPS.load_css("/mjs/comp-block-preview/preview.css?3");
});

