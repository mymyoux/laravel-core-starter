<div>

<div class="new-template-page scroll-list-users">

    <h1>lists</h1>
    <span class="subtitle">Subtitle</span>

<div class="list-table">
    <slot name="header">

    </slot>
    <slot name="action">
        <div v-if="actions" class="menu-actions table-menu-actions">
            <div class="export">
                <div class="cta-blue-s" on-click="exportData()">export</div>
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

        <div v-if="list" v-for="item in list.models" class="table-tr" :class="{'link-inside':list.config.link}">
            <div v-for="(column,i) in list.columns" class="table-td" v-if="column.visible" :class="{'link-inside':typeof column.link == 'string', 'editable':column.editable === true}" v-on:click="click(item,column, $event)">
                <div v-if="!column.type">
                    <span v-if="column.editable">
                        edit
                    </span>
                    <span >
                        {{item[column.prop]}}
                    </span>
                </div>
                 <component v-else v-bind:is="column.type" :item="item" :column="column" :data="data" :alert="alert">

                </component>
            </div>
        </div>
    </div>

    <div v-on:click="paginate" class="btn">Load More</div>

</div>

</div>

</div>
