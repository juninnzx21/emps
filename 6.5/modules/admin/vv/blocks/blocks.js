var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#admin-blocks-app',
        mounted: function(){
            $("#admin-blocks-app").show();
            $(".app-loading").hide();
        },
        methods: {
        }
    });
});

var EMPS_V_blocks = {
    data: function() {
        return {
            expanded: false,
            mobile: false,
        };
    },
    methods: {
        save_and_preview: function() {
            this.$refs.values.save();
            setTimeout(this.$refs.preview.load_preview, 500);
        },
        preview: function() {
            setTimeout(this.$refs.preview.load_preview, 0);
        },
        toggle: function(v) {
            this[v] = !this[v];
        }
    },
    computed: {
    },
    watch: {
    },
    mounted: function() {
    }
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_blocks);

emps_scripts.push(()=>{
    EMPS.load_css("/mjs/admin-vv-blocks/blocks.css");
});