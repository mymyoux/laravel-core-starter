<div   @click="checkOutside($event)">

<div class="new-template-page scroll-list-users">

    <h1>lists</h1>
    <span class="subtitle">Subtitle</span>

<div class="list-table vue">
    <slot name="header">

    </slot>
    <slot name="action">
        <div v-if="actions" class="menu-actions table-menu-actions">
            <div class="create" v-if="list.config.creatable">
                <div class="cta-blue-s" @click="create($event)">create</div>
            </div>
            <div class="export">
                <div class="cta-blue-s">export</div>
            </div>
            <div v-on:click="openSearch(list)" class="searchbox btn">
                <input data-field="search" type="search" placeholder="search" value="" on-keyup="onKeyUp(list)" on-blur="onBlur(list)"/>
                <i class="icon-search"></i>
            </div>

            <div class="custom-select custom-select-no-conflict">
                <select>
                    <option value="" ></option>
                </select>
            </div>
        </div>
    </slot>
    <div class="table">

        <div class="table-tr table-header">

            <div  v-for="(column,i) in list.columns" class="table-th" v-bind:class="[
            {
                sortable:column.sortable,
                filterable:column.filterable,
                resizable:column.resizable
            },column.headerClasses?column.headerClasses:'']" v-if="column.visible">
                <span>{{column.title}}</span>
            </div>
        </div>
        <div class="table-tr table-search" v-if="list.config.searchable">
            <div  v-for="(column,i) in list.columns" class="table-th">
                <div v-if="column.searchable">
                    <input type="search" v-model="list.filter[column.prop]" :placeholder="column.title" @keyup="onSearch(column, $event)">
                </div>
                <span v-else>&nbsp;</span>
            </div>
        </div>
        <div v-if="loading" class="search-filter is_animated">
            <div class="search_loader" style="width: 100%">
                <div class="loader">
                    <span class="load"></span>
                </div>
            </div>
        </div>
        <div v-if="list" v-for="item in list.models" class="table-tr table-item" :class="{'link-inside':list.config.link,deletable:list.config.deletable}" @click="liclick(item, $event)" :data-create="item._creating">
            <div v-for="(column,i) in list.columns" class="table-td" v-if="column.visible" :class="{'link-inside':typeof column.link == 'string', 'editable':column.editable === true}" v-on:click="click(item,column, $event)">
                <div v-if="!column.type">
                    <div v-if="column.editable && edition && edition === item" class="edit-input">
                        <input type="text" v-model="item[column.prop]" @keyup="change(item, column, $event)" @keyup.enter="edited(item, $event)" @keyup.esc="cancel(item, $event)" :placeholder="column.title">
                        <span v-if="column.error" class="error">
                            {{column.error}}
                        </span>
                    </div>
                    <div v-else>
                        <span v-if="column.editable" class="edition" @click="edit(item, column, $event)">
                            edit
                        </span>
                        <span @dblclick="edit(item, column, $event)">
                            {{item[column.prop]}}
                        </span>
                    </div>
                </div>
                 <component v-else v-bind:is="column.type" :item="item" :column="column" :data="data" :alert="alert">

                </component>
            </div>
            <div v-if="edition && edition === item">
                <span @click="edited(item, $event)">ok</span>
                <span @click="cancel(item, $event)">cancel</span>
            </div>
            <div v-if="list.config.deletable && item !== edition"  class="button-deletable">
                <div v-if="deleting">
                    <span @click="remove(item, $event)">
                        confirm
                    </span>
                    <span @click="cancelRemove(item, $event)">
                        cancel
                    </span>
                </div>
                <span v-else @click="askRemove(item, $event)">
                    delete
                </span>
            </div>
        </div>
    </div>

    <div v-on:click="paginate" class="btn">Load More</div>

</div>

</div>

</div>
