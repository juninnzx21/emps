EMPS.vue_component('block-values', '/mjs/comp-block-values/values.vue?2',
    {
        props: ['id', 'prefix', 'ctx'],
        data: function(){
            return {
                obj: {value: [], template_title: 'test'},
                error: "",
            };
        },
        methods: {
            load_params: function() {
                var that = this;

                if (this.id === undefined) {
                    return;
                }

                axios
                    .get("/comp-block-values/" + this.id + "/")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.obj.value = data.lst;
                            that.obj.template_title = data.lst[0].template_title;
                        }else{
                            that.error = data.message;
                        }
                    });

            },
            save: function() {
                var that = this;
                var row = {};
                row.post_save_values = 1;
                row.payload = this.obj.value;
                axios
                    .post("/comp-block-values/" + this.id + "/", row)
                    .then(function(response){
                        var data = response.data;

                        if(data.code == 'OK'){
                            that.load_params();
                            $('form *, button').blur();
                            toastr.success(window.string_saved);
                            that.$emit("saved");
                        }
                    });
            },
            add_row: function(row) {
                if (!(row.value instanceof Array)) {
                    row.value = [];
                }
                row.value.push({type: 'ref', value: ''});
            }
        },
        computed: {
        },
        watch: {
            id: function(newval) {
                setTimeout(this.load_params, 100);
            }
        },
        mounted: function(){
            this.load_params();
        }
    }
);
