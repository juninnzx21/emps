emps_scripts.push(function() {
    Vue.component('uploads-photos', {
        template: '#uploads-photos-component-template',
        props: {
            context: {
                required: true,
            },
            cols: {
                type: Number,
                default: 4
            },
            max: {
                type: Number,
                default: 0
            },
            single: {
                type: Boolean,
                default: false
            },
            centered: {
                type: Boolean,
                default: false
            },
            label: {
                type: String,
                default: null,
            },
            defaultimg: {
                type: String,
                default: null,
            },
            noImage: {
                type: Boolean,
                default: false,
            },
            noName: {
                type: Boolean,
                default: false,
            },
            fullLink: {
                type: Boolean,
                default: false,
            },
            readOnly: {
                type: Boolean,
                default: false,
            }
        },
        data: function(){
            return {
                selected_file: '',
                queue: [],
                files: [],
                need_upload: false,
            };
        },
        methods: {
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
                                that.files = data.files;
                                if (that.max > 0) {
                                    that.enforce_max();
                                }
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
            enforce_max: function() {
                if (this.files.length > this.max) {
                    var i, l;
                    l = this.files.length - this.max;
                    var lst = [];
                    for (i = 0; i < l; i++) {
                        lst.push(this.files[i].id);
                    }
                    this.do_delete_file(lst.join(","));
                }
            },
            do_delete_file: function(lst) {
                var that = this;
                axios
                    .get(this.target + "?delete_uploaded_photo=" + lst)
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                            $("button").blur();
                        }else{
                            alert(data.message);
                        }
                    });
            },
            delete_file: function(file) {
                if (!confirm("Delete this photo?")) {
                    return;
                }
                this.do_delete_file(file.id);
            },
            delete_queue: function(idx) {
                this.queue.splice(idx, 1);
            },
            load_files: function() {
                if (!this.context) {
                    this.files = [];
                    return;
                }
                var that = this;
                this.files = [];
                axios
                    .get(this.target + "?list_uploaded_photos=1")
                    .then(function(response){
                        var data = response.data;
                        if (data.code == 'OK') {
                            that.files = data.files;
                        }else{
                            alert(data.message);
                        }
                    });

            },
            is_uploading: function() {
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i].started) {
                        this.$emit("is_uploading", true);
                        return true;
                    }
                }
                this.$emit("is_uploading", false);
                return false;
            },
            get_total_progress: function() {
                var loaded = 0, total = 0;
                for (var i = 0; i < this.queue.length; i++ ) {
                    if (this.queue[i].started) {
//                        console.log(this.queue[i]);
                        if (!isNaN(this.queue[i].loaded)) {
                            loaded += this.queue[i].loaded;
                        }
                        if (!isNaN(this.queue[i].total)) {
                            total += this.queue[i].total;
                        }
                    }
                }

                if (total === 0) {
                    return 0;
                }

                var rv = Math.round((loaded / total) * 100, 2);
                this.$emit("progress", rv);
                return rv;
            },
        },
        computed: {
            target: function() {
                return "/json-upload-photos/" + this.context + "/";
            }
        },
        watch: {
            context: function(new_val, old_val) {
                if (this.need_upload) {
                    this.start_uploading();
                }
            },
            files: function(new_val, old_val) {
                this.$emit("update", new_val);
            },
            need_upload: function(new_val, old_val) {
                this.$emit("need_upload", new_val);
            }
        },
        mounted: function(){
            this.$watch('context', this.load_files);
            this.load_files();
        }
    });


});
