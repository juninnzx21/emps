<div>
    <block-params prefix="el" v-model="obj"
                  :depth="0"
                  mode="compact"
                  nidx="1"
                  :lc="null"
                  :ctx="ctx"
                  :clipboard="null" @save="save"></block-params>
    <block-class mode="modal"/>
</div>