(function() {

    Vue.component('multiselect', {
        template: '#multiselect-component-template',
        props: {
            value: [Number, String],
            e: {
                type: Array,
                default: []
            },
            placeholder: String,
            title: String,
            size: String,
            readOnly: {
                type: Boolean,
                default: false
            },
            noField: {
                type: Boolean,
                default: false
            },
            noClear: {
                type: Boolean,
                default: false
            },
        },
        data: function(){
            return {
                guid: EMPS.guid(),
                description: '',
                value: [],
            };
        },
        methods: {
            select: function() {
                vuev.$emit("modal:open:" + this.modal_name());
            },
            close_modal: function() {
                $("#" + this.modal_name()).removeClass("is-active");
            },
            modal_name: function() {
                return "multiselectModal" + this.guid;
            },
            clear: function() {
                this.description = '';
                this.value = [];
                this.$emit('input', this.value);
            },
            toggle: function(v) {
                if (this.value === undefined || this.value === null) {
                    this.value = [];
                    this.value.push(v);
                    return;
                }
                if (this.value.includes(v)) {
                    var index = this.value.indexOf(v);
                    if (index !== -1) {
                        this.value.splice(index, 1);
                    }
                } else {
                    this.value.push(v);
                }
            },
            has_value: function(v) {
                if (this.value === undefined || this.value === null) {
                    return false;
                }
                if (this.value.includes(v)) {
                    return true;
                }
                return false;
            },
            set_description: function() {
                var l = this.e.length;
                var parts = [];
                for (var i = 0; i < l; i++) {
                    if (this.has_value(this.e[i].code)) {
                        parts.push(this.e[i].value);
                    }
                }
                this.description = parts.join(", ");
            },
            set_value: function() {
                this.set_description();
                this.$emit('input', this.value);
                this.close_modal();
            }
        },
        computed: {
        },
        watch: {
            enum: function(newVal) {
                //alert(JSON.stringify(newVal));
            },
        },
        mounted: function(){
        }
    });

})();
