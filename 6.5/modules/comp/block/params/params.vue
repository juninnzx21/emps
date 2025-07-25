<div>

    <div class="is-size-6 field has-text-weight-bold" :id="prefix">
      {{*<button type="button"
              v-if="depth > 0"
              @click.stop.prevent="emit_edit(value)"
              class="button is-primary is-light is-small"><i class="fa fa-pencil"></i></button>*}}
      <span :class="{'has-text-success': (prefix == last_clicked)}">
        {{ value.template_title }}</span> <span class="tag" v-if="nidx > 0">{{ nidx }} </span>
      <template v-if="depth == 0"><span class="is-pulled-right">
      <button type="button"
              @click.stop.prevent="copy_json(value.value[0].value)"
              class="button is-primary is-light is-small"><i class="fa fa-copy"></i></button>
    </span></template>
    </div>


    <template v-for="(row,idx) in value.value" v-if="mode !== 'compact'">
        <div class="mb-3">
            <label class="label">{{ row.title }}:</label>
            <template v-if="row.type == 'c'">
                <input type="text" v-model="row.value" @keydown.enter="save" class="input" :placeholder="row.default" />
            </template>
            <template v-if="row.type == 'h'">
                <editor v-model="row.value" :id="'param_html_' + prefix + '_' + idx" :init="emps_tinymce_settings"></editor>
            </template>
            <template v-if="row.type == 't'">
                <textarea class="textarea" v-model="row.value" rows="3"></textarea>
            </template>
            <template v-if="row.type.substr(0, 1) == 'a'">
                <div class="panel mb-3">
                    <div class="panel-block is-block">
                        <template v-for="(srow,si) in row.value">
                            <template v-if="srow.type == 'ref'">
                                <div class="columns is-mobile">
                                    <div class="column">
                                        <div class="control">
                                            <selector
                                                    v-model="srow.value"
                                                    title="Блок"
                                                    :has-extra="true"
                                                    :placeholder="row.template"
                                                    :search="true"
        :type="'e_blocks' + ((srow.template !== undefined && srow.template != '')?'|template=' + urlencode(srow.template):'')">
                                            </selector>
                                        </div>
                                    </div>

                                    <div class="column is-narrow">
                                        {{capture assign="clipbtns"}}
                                        <template v-if="clipboard === null">
                                            <button type="button"
                                                    @click="cut_to_clipboard(srow, row, si)"
                                                    class="button is-info is-light">
                                                <i class="fa fa-scissors"></i>
                                            </button>
                                            <button type="button"
                                                    @click="copy_to_clipboard(srow)"
                                                    class="button is-info is-light">
                                                <i class="fa fa-clone"></i>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button type="button"
                                                    @click="insert_from_clipboard(row, si)"
                                                    class="button is-info is-light">
                                                <i class="fa fa-clipboard"></i>
                                            </button>
                                        </template>
                                        {{/capture}}

                                        <button type="button"
                                                @click="convert_to_raw(srow)"
                                                class="button is-info is-light">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        {{$clipbtns}}
                                        <button type="button"
                                                @click="remove_item(si, row.value)"
                                                class="button is-danger is-light">
                                            <i class="fa fa-remove"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template v-if="srow.type == 'raw'">
                                <div class="columns is-mobile">
                                    <div class="column">
                                        <div class="field has-addons">
                                            <div class="control is-expanded">
                                                <input type="text" v-model="srow.template"
                                                       @keydown.enter="change_template(srow)"
                                                       class="input" />
                                            </div>
                                            <div class="control">
                                                <button
                                                        @click="change_template(srow)"
                                                        class="button is-info is-light" type="button">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="column is-narrow">
                                        <button type="button"
                                                @click="srow.expanded = !srow.expanded"
                                                class="button is-info is-light">
                                            <i v-if="!srow.expanded" class="fa fa-chevron-down"></i>
                                            <i v-else class="fa fa-chevron-up"></i>
                                        </button>
                                        <button type="button"
                                                @click="convert_to_ref(srow)"
                                                class="button is-info is-light">
                                            <i class="fa fa-link"></i>
                                        </button>
                                        {{$clipbtns}}
                                        <button type="button"
                                                @click="remove_item(si, row.value)"
                                                class="button is-danger is-light">
                                            <i class="fa fa-remove"></i>
                                        </button>
                                    </div>
                                </div>

                                <block-params v-if="srow.expanded" v-model="value.value[idx].value[si]"
                                              @clipboard="emit_clipboard"
                                              :clipboard="clipboard"
                                              :nidx="si + 1"
                                              :lc="last_clicked"
                                              :prefix="prefix + '_' + (si + 1)" @save="save"></block-params>
                            </template>
                        </template>
                        <button type="button" @click="emit_add(row)" class="button is-primary is-light">Добавить элемент</button>
                        <button type="button"
                                v-if="clipboard !== null"
                                @click="insert_from_clipboard(row, -1)"
                                class="button is-info is-light">
                            <i class="fa fa-clipboard"></i>
                        </button>

                    </div>
                </div>
            </template>
        </div>
    </template>
    <template v-for="(row,idx) in value.value" v-if="mode == 'compact'">
      <div class="mb-3">
        <template v-if="row.type.substr(0, 1) == 'a'">
          <div class="panel mb-3">
            <div class="panel-block is-block">
              <template v-for="(srow,si) in row.value">

                <span class="is-pulled-left pr-1" :id="prefix + '_' + (si + 1)">
                  <button type="button"
                          @click="toggle_expanded(srow)"
                          class="button is-info is-light is-small">
                  <i v-if="!srow.expanded" class="fa fa-folder-o"></i>
                  <i v-else class="fa fa-folder-open-o"></i>
                </button>
                  <i class="fa fa-arrow-right has-text-success" v-if="(prefix + '_' + (si + 1)) == last_clicked"></i>
                  {{* last_clicked *}}
                </span>
                <span class="is-pulled-right">
                    <button type="button"
                            @click.stop.prevent="emit_edit(srow)"
                            class="button is-primary is-light is-small"><i class="fa fa-pencil"></i></button>


                  <div class="dropdown is-right is-hoverable">
  <div class="dropdown-trigger">
                    <button type="button"
                            class="button is-link is-light is-small">
                    <i class="fa fa-ellipsis-v"></i>
                    </button>
  </div>
  <div class="dropdown-menu" id="dropdown-menu4" role="menu">
    <div class="dropdown-content">
                                              <template v-if="clipboard === null">
                                                <a href="javascript:;"
                                                   @click="cut_to_clipboard(srow, row, si)"
                                                   class="dropdown-item"> Вырезать </a>
                                                <a href="javascript:;"
                                                   @click="copy_to_clipboard(srow)"
                                                   class="dropdown-item"> Копировать </a>
                                              </template>
                                              <template v-else>
                                                <a href="javascript:;"
                                                   @click="insert_from_clipboard(row, si)"
                                                   class="dropdown-item"> Вставить до </a>
                                              </template>
                                                      <hr class="dropdown-divider" />
                                                    <a href="javascript:;"
                                                       @click="copy_json(srow)"
                                                       class="dropdown-item"> Копировать JSON</a>
      <hr class="dropdown-divider" />
      <a href="javascript:;" @click="remove_item(si, row.value)" class="dropdown-item"> Удалить </a>
    </div>
  </div>
</div>

{{*                    <button type="button"
                            @click="remove_item(si, row.value)"
                            class="button is-danger is-light is-small">
                    <i class="fa fa-remove"></i>
                    </button>*}}
                </span>
                <template v-if="srow.type == 'ref'">
                  <div class="is-size-6 field has-text-weight-bold">
                    <button type="button"
                            @click="toggle_expanded(srow)"
                            class="button is-info is-light is-small">
                      <i v-if="!srow.expanded" class="fa fa-chevron-down"></i>
                      <i v-else class="fa fa-chevron-up"></i>
                    </button>
                    {{*<button type="button"
                            @click.stop.prevent="emit_edit(srow)"
                            class="button is-primary is-light is-small"><i class="fa fa-pencil"></i></button>*}}
                    Пустой блок <span class="tag">{{ si + 1 }}</span></div>

                </template>
                <template v-if="srow.type == 'raw'">


                  <block-params v-if="srow.expanded" v-model="value.value[idx].value[si]"
                                @clipboard="emit_clipboard"
                                @edit="emit_edit"
                                @add="emit_add"
                                :clipboard="clipboard"
                                mode="compact"
                                :lc="last_clicked"
                                :nidx="si + 1"
                                :depth="depth + 1"
                                :prefix="prefix + '_' + (si + 1)" @save="save"></block-params>
                  <template v-else>
                    <div class="is-size-6 field has-text-weight-bold">
                      {{*<button type="button"
                              @click.stop.prevent="emit_edit(srow)"
                              class="button is-primary is-light is-small"><i class="fa fa-pencil"></i></button>*}}
                      <span :class="{'has-text-success': (prefix + '_' + (si + 1)) == last_clicked}">
                        {{ srow.template_title }}
                      </span>
                       <span class="tag">{{ si + 1 }}</span>
                      {{* prefix + '_' + (si + 1) *}}
                    </div>
                  </template>

                </template>
              </template>
              <button type="button" @click="emit_add(row)" class="button is-primary is-light is-small"><i class="fa fa-plus"></i></button>
              /{{ value.template_title }}
              <span class="is-pulled-right">
                <button type="button"
                        v-if="clipboard !== null"
                        @click="insert_from_clipboard(row, -1)"
                        class="button is-info is-light is-small">
                            <i class="fa fa-clipboard"></i>
                        </button>
              </span>
            </div>
          </div>
        </template>
      </div>
    </template>
  <template v-if="depth == 0">
    <modal id="editParamModal" :submit="submit_block_form" size="container">
      <template slot="header">Редактирование блока</template>

      <template v-if="erow.type == 'ref'">
        <div class="control">
          <selector
              v-model="erow.value"
              title="Блок"
              :has-extra="true"
              :search="true"
              :type="'e_blocks' + ((erow.template !== undefined && erow.template != '')?'|template=' + urlencode(erow.template):'')">
          </selector>
        </div>
      </template>
      <template v-if="erow.type == 'raw'">
        <div class="field has-text-weight-bold">{{ erow.template_title }}</div>

        <div class="field has-addons">
          <div class="control is-expanded">
            <input type="text" v-model="erow.template"
                   @keydown.enter="change_template(erow)"
                   class="input" />
          </div>
          <div class="control">
            <button
                @click="change_template(erow)"
                class="button is-info is-light" type="button">
              <i class="fa fa-check"></i>
            </button>
          </div>
        </div>

        <template v-for="(row,idx) in erow.value" v-if="row.type.substr(0, 1) != 'a'">
          <div class="mb-3">
            <label class="label">{{ row.title }}:</label>
            <template v-if="row.type == 'c'">
              <template v-if="row.name == 'class'">
                <block-class mode="value" v-model="row.value" :placeholder="row.default" />
              </template>
              <template v-else>
                <input type="text" v-model="row.value" @keydown.enter="save" class="input" :placeholder="row.default" />
              </template>

            </template>
            <template v-if="row.type == 'h'">
              <editor v-model="row.value" :id="'param_html_' + prefix + '_' + idx" :init="emps_tinymce_settings"></editor>
            </template>
            <template v-if="row.type == 't'">
              <textarea class="textarea" v-model="row.value" rows="3"></textarea>
            </template>
            <template v-if="row.type == 'photo'">
              <div style="max-width: 15rem">
              <imagesel v-model="row.value"
                        cl="is-4by3"
                        @input="save_later"
                        :context="ctx"></imagesel>
              </div>
            </template>
          </div>
        </template>
      </template>

      <template slot="actions">
        <button type="submit" class="button is-success">Сохранить изменения</button>
      </template>
    </modal>

    <modal id="addBlockModal" :submit="submit_new_block" size="container">
      <template slot="header">Добавление блока</template>

      <div class="tabs" style="margin-bottom: 1rem">
        <ul>
          <li :class="{'is-active': addmode == 'ref'}">
            <a
                @click.stop.prevent="addmode = 'ref'">Под-блок</a>
          </li>
          <li :class="{'is-active': addmode == 'raw'}">
            <a
                @click.stop.prevent="addmode = 'raw'">Элемент</a>
          </li>
          <li :class="{'is-active': addmode == 'group'}">
            <a
                @click.stop.prevent="addmode = 'group'">Составной элемент</a>
          </li>
          <li :class="{'is-active': addmode == 'json'}">
            <a
                @click.stop.prevent="addmode = 'json'">JSON-код</a>
          </li>
        </ul>
      </div>

      <template v-if="addmode == 'ref'">
        <div class="control">
          <selector
              v-model="arow.value"
              title="Блок"
              :has-extra="true"
              :search="true"
              :type="'e_blocks' + ((arow.template !== undefined && arow.template != '')?'|template=' + urlencode(arow.template):'')">
          </selector>
        </div>
      </template>
      <template v-if="addmode == 'raw'">
        <div class="field has-text-weight-bold">{{ arow.template_title }}</div>

        <div class="field has-addons">
          <div class="control">
            <button
                @click="open_collection"
                class="button is-info is-light" type="button">
              Выбрать...
            </button>
          </div>
          <div class="control is-expanded">
            <input type="text" v-model="arow.template"
                   @keydown.enter="change_template(arow)"
                   class="input" />
          </div>
          <div class="control">
            <button
                @click="change_template(arow)"
                class="button is-info is-light" type="button">
              <i class="fa fa-check"></i>
            </button>
          </div>
        </div>

        <template v-for="(row,idx) in arow.value" v-if="row.type.substr(0, 1) != 'a'">
          <div class="mb-3">
            <label class="label">{{ row.title }}:</label>
            <template v-if="row.type == 'c'">
              <template v-if="row.name == 'class'">
                Class editor
              </template>
              <template v-else>
                <input type="text" v-model="row.value" class="input" :placeholder="row.default" />
              </template>
            </template>
            <template v-if="row.type == 'h'">
              <editor v-model="row.value" :id="'param_html_' + prefix + '_' + idx" :init="emps_tinymce_settings"></editor>
            </template>
            <template v-if="row.type == 't'">
              <textarea class="textarea" v-model="row.value" rows="3"></textarea>
            </template>
            <template v-if="row.type == 'photo'">
              <div style="max-width: 15rem">
                <imagesel v-model="row.value"
                          cl="is-4by3"
                          :context="ctx"></imagesel>
              </div>
            </template>
          </div>
        </template>

      </template>

      <template v-if="addmode == 'json'">
        <div class="field">
          <textarea class="textarea" rows="15" v-model="json_export"></textarea>
        </div>
      </template>

      <template slot="actions">
        <button type="submit" class="button is-success">Добавить блок</button>
      </template>
    </modal>

    <modal id="selectElementModal" size="container">
      <template slot="header">Коллекция элементов</template>

      {{include file="db:blocks/_index"}}

      <template slot="actions">
      </template>
    </modal>

    <modal id="modalExport" size="container">
      <template slot="header">Экспорт блоков в JSON</template>

      <textarea class="textarea" rows="15" :value="json_export"></textarea>

      <template slot="actions">
      </template>
    </modal>

  </template>
</div>