var vuev, app;

emps_scripts.push(function(){
    vuev = new Vue();
    app = new Vue({
        el: '#e_websites_app',
        mounted: function(){
            $("#e_websites_app").show();
            $(".app-loading").hide();
        },
        methods: {
        }
    });
});

var EMPS_V_websites = {
    data: function() {
        return {
        };
    },
    methods: {
    },
    computed: {
    },
    watch: {
    },
    mounted: function() {
        var that = this;
        EMPS.vue_load_enums(that, "websitestatus,publish");
    }
};

if (window.EMPS_vted_mixins === undefined) {
    window.EMPS_vted_mixins = [];
}

window.EMPS_vted_mixins.push(EMPS_V_websites);