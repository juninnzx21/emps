EMPS.vue_component('block-preview', '/mjs/comp-block-preview/preview.vue',
    {
        props: ['id'],
        data: function(){
            return {
                html: "",
                error: ""
            };
        },
        methods: {
            load_preview: function() {
                if (!this.id) {
                    return;
                }
                var that = this;
                if (!this.$refs.viewer.srcdoc) {
                    axios
                        .get("/comp-block-preview/" + this.id + "/")
                        .then(function(response){
                            var data = response.data;
                            that.html = data;
                            that.$refs.viewer.srcdoc = that.html;
                        });

                } else {
                    axios
                        .get("/comp-block-preview/" + this.id + "/?inner=1")
                        .then(function(response){
                            var data = response.data;

                            that.html = data;
                            that.$refs.viewer.contentWindow.postMessage({
                                type: 'updateHTML',
                                newHTML: that.html
                            }, '*');
                        });


                }
            }
        },
        computed: {
        },
        watch: {
            id: function(newval) {
                setTimeout(this.load_preview, 100);
            }
        },
        mounted: function(){
            this.load_preview();
        }
    }
);
