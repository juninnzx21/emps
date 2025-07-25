EMPS.vue_component('modal', '/mjs/comp-modal/modal.vue', {
    template: '#modal-component-template',
    props: ['id', 'form', 'submit', 'size', 'buttonClass', 'noFooter', 'noActions', 'plain', 'noCloseButton', 'addClass'],
    data: function(){
        return {
            btn_class: {'button': true}
        };
    },
    methods: {
        close_modal: function(e){
            var element = document.getElementById(this.id);
            element.classList.remove("is-active");
            this.$emit("closed");
        },
        on_open: function(data){
            var element = document.getElementById(this.id);
            element.classList.add("is-active");
            this.$emit("open");
        },
        on_close: function(data) {
            this.close_modal();
        },
        submit_form: function(){
            if(this.submit !== undefined){
                this.submit.call();
            }
        },
        get_class: function(){
            var c = "modal-card";
            switch(this.size){
                case "lg":
                    c += " modal-lg";
                    break;
                case "sm":
                    c += " modal-sm";
                    break;
                case "container":
                    c += " modal-container";
                case "full":
                    c += " modal-full";
                    break;
            }
            return c;
        }
    },
    watch: {
        id: function(new_val, old_val) {
            vuev.$off("modal:open:" + old_val);
            vuev.$on("modal:open:" + new_val, this.on_open);
        }
    },
    mounted: function(){
        this.btn_class = Object.assign(this.btn_class, this.buttonClass);
        this.$forceUpdate();
        vuev.$on("modal:open:" + this.id, this.on_open);
        vuev.$on("modal:close:" + this.id, this.on_close);
    }
});
