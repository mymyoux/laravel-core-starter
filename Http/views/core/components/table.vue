<div @click="checkOutside($event)">

<div class="new-template-page scroll-list-users">

    <slot name="title" v-if="title">
        <h1>((.title))</h1>
        <span class="subtitle">((.subtitle))</span>
    </slot>

<div class="list-table vue">
    <slot name="header">

    </slot>
    <slot name="action">
        <div v-if="actions" class="menu-actions table-menu-actions">
            <div class="create" v-if="list.config.creatable">
                <div class="cta-blue-s" @click="create($event)">create</div>
            </div>
            <!--<div class="export">
                <div class="cta-blue-s">export</div>
            </div>-->
            <div v-on:click="openSearch()" class="searchbox btn" :class="{open:search_open || list.search}">
                <input class="search_global" v-model="list.search" type="search" :placeholder="((search))" value="" @keyup.enter="onSearchGlobal($event)" @blur="onSearchGlobal($event, true)"/>
                <i class="icon-search"></i>
            </div>

            <div v-for="choice, key in list.choices" class="custom-select custom-select-no-conflict">
                <select v-model="list.choosen[key]" @change="choiceChange(key, $event)">
                    <option v-for="item in choice" :value="item.value" >{{item.label}}</option>
                </select>
            </div>

            <!-- filters -->
            <div v-if="!list.multiFilters() && filter && list.displayFilters().length > 0" :class="'custom-select custom-select-no-conflict'">
                <select v-model="list.current_filter" @change="filterChange( list )">
                    <option value="">((all))</option>
                    <option v-for="value, key in list.displayFilters()" :value="value" :selected="(list.current_filter == value ? 'selected' : '')">(( '' + value))</option>
                </select>
            </div>
            <div v-else-if="list.multiFilters() && list.displayFilters().length > 0" class="item">
                <div class="custom-select tag scroll" >
                    <div data-field="position" data-type="reallist">
                        <ul class="click select" data-multiple data-static>
                            <p>
                                <span v-if="list.current_filters && list.current_filters.length == 0">All</span>
                                <span v-else v-for="value, key in list.current_filters">
                                    {{ value }}{{ key !== list.current_filters.length - 1 ? ', ' : ''}}
                                </span>
                            </p>
                            <ul class="list-scroll click" data-multiple data-static>
                                <li v-for="value, key in list.displayFilters()" @click="addFilterMultiSelect(list, value)" :value="value">{{ value }}<span :class="'checkbox-square' + (list.current_filters.indexOf(value) != -1 ? ' active' : '')"></span></li>
                            </ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </slot>
    <div class="table">

        <div class="table-tr table-header">

            <div  v-for="(column,i) in list.columns" class="table-th" v-bind:class="[
            {
                sortable:column.sortable,
                sorted:column.selected,
                filterable:column.filterable,
                resizable:column.resizable
            },column.headerClasses?column.headerClasses:'']" v-if="column.visible" @click="sorting(list, column)">
                <span>{{column.title}} <span v-if="column.sortable"><i :class="'icon-arrow ' + (true === column.selected? (column.order[0] === 1 ? 'up' : 'down') : '')"></i></span></span>
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

        <slot name="notification"></slot>

        <div v-if="list && list.models && list.models.length" v-for="(item, index) in list.models" class="table-tr table-item" :class="{'link-inside':list.config.link,deletable:list.config.deletable}" @click="liclick(item, $event)" :data-create="item._creating">
            <div v-for="(column,i) in list.columns" class="table-td" v-if="column.visible && (!item.getAction || (item.getAction(item) != item.getAction(list.models[index - 1])))" :class="{'link-inside':typeof column.link == 'string', 'editable':column.editable === true}" v-on:click="click(item,column, $event)">
                <div v-if="!column.type">
                    <div v-if="column.editable && edition && edition === item" class="edit-input">
                        <input type="text" v-model="item[column.prop]" @keyup="change(item, column, $event)" @keyup.enter="edited(item, $event)" @keyup.esc="cancel(item, $event)" :placeholder="column.title">
                        <span v-if="column.error" class="error">
                            {{column.error}}
                        </span>
                    </div>
                    <div v-else>
                        <span v-if="column.editable" class="edition icon-edit" @click="edit(item, column, $event)">
                            &nbsp;
                        </span>
                        <span @dblclick="edit(item, column, $event)">
                            {{item[column.prop]}}
                        </span>
                    </div>
                </div>
                <div v-else>
                 <component v-bind:is="column.type" :item="item" :column="column" :data="data" :alert="alert">
                </component>
                </div>
            </div>

            <div v-if="edition && edition === item" class="edit-save">
                <span @click="edited(item, $event)" class="icon-check">&nbsp;</span>
                <span @click="cancel(item, $event)" class="icon-cross2">&nbsp;</span>
            </div>
            <div v-if="list.config.deletable && item !== edition"  class="button-deletable">
                <div v-if="deleting" class="confirm-delete">
                    <span @click="remove(item, $event)" class="confirm">
                        confirm
                    </span>
                    <span @click="cancelRemove(item, $event)">
                        cancel
                    </span>
                </div>
                <span v-else @click="askRemove(item, $event)" class="icon-delete">
                    &nbsp;
                </span>
            </div>
        </div>
        <div class="table-tr table-item width-fix">
                <div v-for="(column,i) in list.columns" class="table-td">
                    <div>
                        <div>
                            <span>
                                &nbsp;
                            </span>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <div v-if="!loading && (!list || !list.models || !list.models.length)" class="no-result">
        <p>no result</p>
    </div>

    <div v-on:click="paginate" v-else class="btn">Load More</div>

</div>

</div>

</div>
