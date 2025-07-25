{{if $lang == "en"}}
{{$str_close = "Close"}}
{{else}}
{{$str_close = "Закрыть"}}
{{/if}}
<div :class="'modal ' + addClass" :id="id">
    <template v-if="plain">
        <div class="modal-background" @click="close_modal"></div>
        <slot></slot>
        <button v-if="!noCloseButton" class="modal-close is-large" aria-label="close" @click="close_modal"></button>
    </template>
    <template v-else>
        <div class="modal-background" @click="close_modal($event)"></div>
        <div :class="get_class()">


            <header class="modal-card-head">
                <p class="modal-card-title"><slot name="header"></slot></p>
                <button type="button" class="delete" aria-label="close" @click="close_modal($event)"></button>
            </header>
            <section class="modal-card-body">
                <form @submit.prevent="submit_form">
                    <slot></slot>
                </form>
            </section>
            <footer v-if="!noFooter" class="modal-card-foot flex-end">
                <form @submit.prevent="submit_form" v-if="!noActions" class="buttons">
                    <button type="button" :class="btn_class" @click="close_modal($event)">{{$str_close}}</button>
                    <slot name="actions">
                    </slot>
                </form>
            </footer>

        </div>
    </template>
</div>
