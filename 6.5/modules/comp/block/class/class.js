EMPS.vue_component('block-class', '/mjs/comp-block-class/class.vue',
    {
        props: ['value', 'mode', 'placeholder'],
        mixins: [EMPS_common_mixin],
        data: function(){
            return {
                text: "",
            };
        },
        methods: {

        },
        computed: {
        },
        watch: {
            value: function(newval) {
                this.text = newval;
            },
            text: function(newval) {
                this.$emit("input", newval);
            }
        },
        created: function () {
        },
        destroyed: function () {

        },
        mounted: function(){
            //alert("mounted");
            this.text = this.value;
        }
    }
);

EMPS.load_css("/mjs/comp-block-class/class.css");