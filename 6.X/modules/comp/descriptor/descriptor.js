(function() {

    Vue.component('descriptor', {
        template: '#descriptor-template',
        props: ['value', 'type', 'plain'],
        data: function(){
            return {
                description: '',
            };
        },
        methods: {
            describe: function() {
                if (this.value === undefined || this.value === 0 || this.value === '0') {
                    this.description = '';
                    return;
                }
                let idx = this.type + "-" + this.value;
                if (this.plain) {
                    idx += "-p";
                }
                if (window.descriptor_cache[idx] !== undefined) {
                    if (window.descriptor_cache[idx] == "loading") {
                        setTimeout(this.describe, 500);
                        return;
                    }
                    this.description = window.descriptor_cache[idx];
                    return;
                }
                window.descriptor_cache[idx] = "loading";

                let that = this;
                let add = '';
                if (this.plain) {
                    add = "?plain=1";
                }
                axios
                    .get("/pick-ng-describe/" + this.type + "/" + this.value + "/" + add)
                    .then(function(response){
                        let data = response.data;
                        if (data.code == 'OK') {
                            that.description = data.display;
                            if (!that.description) {
                                that.description = "";
                            }
                            window.descriptor_cache[idx] = that.description;
                        }else{
                            window.descriptor_cache[idx] == undefined;
                            alert(data.message);
                        }
                    });
            },
        },
        mounted: function(){
            if (window.descriptor_cache === undefined) {
                window.descriptor_cache = {};
            }
            this.describe();
        },
        watch: {
            value: function(val) {
                this.describe();
            },
            type: function(val) {
                this.describe();
            }
        }
    });


})();
