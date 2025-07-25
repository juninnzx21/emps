(function() {

    Vue.component('imagesel', {
        mixins: [EMPS_common_mixin],
        template: '#image-component-template',
        props: ['value', 'context', 'mode', 'cl'],
        data: function(){
            return {
                lst: [],
                queue: [],
                current: null,
                selected: null,
                cancel_loading: false,
            };
        },
        methods: {
            select_image: function() {
                vuev.$emit("select_image", this);
            },
            selected_image: function(img) {
                this.selected = img;
                this.$emit("input", img.id);
            },
            clear: function() {
                this.selected = null;
                console.log("EMITTING ZERO");
                this.$emit("input", 0);
            },
            open_select_image: function(that) {
                this.current = that;
                this.load_images();
                //alert("ctx " + that.context);
                //that.selected_image(1);
            },
            load_images: function() {
                axios.get("/json-upload-photos/" + this.current.context + "/?list_uploaded_photos=1").then(response => {
                    if (this.cancel_loading) {
                        this.cancel_loading = false;
                        return;
                    }
                    let data = response.data;
                    if (data.code == "OK") {
                        this.lst = data.files;
                        if (this.lst.length == 0) {
                            this.clear();
                        } else {
                            let found = false;
                            for (let v of this.lst) {
                                if (v.id == this.value) {
                                    found = true;
                                    break;
                                }
                            }
                            if (!found) {
                                this.clear();
                            }
                        }
                        this.open_modal("modalImageCompSelector");
                    }
                });
            },
            click_select: function(img) {
                this.current.selected_image(img);
                this.close_modal("modalImageCompSelector");
            },
            click_clear: function(img) {
                this.current.clear();
                this.close_modal("modalImageCompSelector");
            },

            // from uploader
            add_files: function() {
                this.$refs.files.click();
            },
            handle_uploads: function(){
                var files = this.$refs.files.files;

                if (files.length == 0) {
                    return;
                }

                if (this.single) {
                    this.queue = [];
                }

                for (var i = 0; i < files.length; i++ ) {
                    files[i].image_url = URL.createObjectURL(files[i]);
                    files[i].started = false;
                    files[i].progress = 0;
                    this.queue.push(files[i]);
                    if (this.max > 0 && this.queue.length > this.max) {
                        this.queue.splice(i, this.queue.length - this.max);
                    }
                    this.start_uploading();
                }
            },
            start_uploading: function() {
                if (this.context === undefined) {
                    this.need_upload = true;
                    return;
                }

                this.need_upload = false;

                for (var i = 0; i < this.queue.length; i++ ) {
                    var file = this.queue[i];
                    if (!file.started) {
                        file.started = true;
                        var form_data = new FormData();
                        form_data.append('post_upload_photo', '1');
                        form_data.append('files[0]', file);
                        if (this.single) {
                            form_data.append("single_mode", '1');
                        }
                        var that = this;

                        console.log(this.target, form_data);
                        axios.post( this.target,
                            form_data,
                            {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: function(e) {
                                    if(e.lengthComputable){
                                        file.loaded = e.loaded;
                                        file.total = e.total;
                                        //console.log(file);
                                        that.$forceUpdate();
                                    }
                                },
                                cancelToken: new axios.CancelToken(function executor(c) {
                                    // An executor function receives a cancel function as a parameter
                                    file.cancel_executor = c;
                                })
                            }
                        ).then(function(response){
                            that.remove_upload(file);
                            var data = response.data;

                            if (data.code == 'OK') {
                                // remove from queue, add to files
                                that.lst = data.files;
                            }else{
                                toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                            }

                        })
                            .catch(function(){
                                if (!file.cancelled) {
                                    toastr.error(file.name, string_failed, {positionClass: "toast-bottom-full-width"});
                                }

                                that.remove_upload(file);

                            });
                    }
                }
            },
            remove_upload: function(file) {
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i] === file) {
                        this.queue.splice(i, 1);
                        break;
                    }
                }
            },
            load_selected: function(id) {
                if (id > 0) {
                    axios.get("/json-upload-photos/" + this.context + "/?list_one=1&id=" + id).then(response => {
                        if (this.cancel_loading) {
                            this.cancel_loading = false;
                            return;
                        }
                        let data = response.data;
                        if (data.code == "OK") {
                            if (data.files.length > 0) {
                                this.selected = data.files[0];
                            } else {
                                this.clear();
                            }
                        }
                    });
                }
            },
        },
        computed: {
            image_class: function() {
                return "image " + this.cl;
            },
            target: function() {
                return "/json-upload-photos/" + this.context + "/";
            },
        },
        watch: {
            value: function(newId) {
                if (!newId) {
                    this.selected = null;
                    this.cancel_loading = true;
                    return;
                }
                if (this.selected !== null && newId == this.selected.id) {
                    return;
                }
                this.load_selected(newId);
            }
        },
        mounted: function(){
            if (this.value > 0) {
                this.load_selected(this.value);
            }
        },
        created: function () {
            if (this.mode == 'common') {
                vuev.$on("select_image", this.open_select_image);
            } else {
                vuev.$on("selected_image", this.selected_image);
            }

        },
        destroyed: function () {
        },
    });

    emps_scripts.push(() => {
        EMPS.load_css("/mjs/comp-image/image.css");
    });
})();
