<div>

<div class="new-template-page scroll-list-users">
    <h1>lists</h1>
    <span class="subtitle">Subtitle</span>

<div class="list-users">

    <div class="menu-actions table-menu-actions">
        <div class="export">
            <div class="cta-blue-s" on-click="exportData()">((export))</div>
        </div>
        <div v-on:click="openSearch(list)" class="searchbox btn">
            <input data-field="search" type="search" placeholder="((search))" value="" on-keyup="onKeyUp(list)" on-blur="onBlur(list)"/>
            <i class="icon-search"></i>
        </div>

        <div class="custom-select custom-select-no-conflict">
            <select>
                <option value="" ></option>
            </select>
        </div>

        <!--
        <div class="item">
            <div class="custom-select tag scroll" >
                <div data-field="position" data-type="reallist">
                    <ul class="click select" data-multiple data-static>
                        <p>
                            All
                        </p>
                        <ul class="list-scroll click" data-multiple data-static>

                            <li on-click="" value="{{this}}">Name<span class="checkbox-square"></span></li>

                        </ul>
                    </ul>
                </div>
            </div>
        </div>-->

    </div>
    <div class="table">

        <div class="table-tr table-header">

            <div class="table-th" on-click="">
                <span>Id</span>
            </div>
            <div class="table-th" on-click="">
                <span>Type</span>
            </div>
            <div class="table-th" on-click="">
                <span>State</span>
            </div>

        </div>

        <!--<div class="timer small red close" data-id="">
            <p class="cancel">((deleting))</p>
            <div class="cta-white-l cancel" on-click="">
                <a href="#">((cancel))</a>
            </div>
        </div>-->

        <!--<li v-for="list in lists.models">
            {{ list.state }}
        </li>-->

        <div v-if="list" v-for="item in list.models" class="table-tr">
            <div class="table-td">
                <span>
                     {{item.id_event}}
                </span>
            </div>
            <div class="table-td">
                <span>
                    {{ item.type }}
                </span>
            </div>
            <div class="table-td">
                <span>
                    {{ item.state }}
                </span>
            </div>
        </div>
        <div class="table-tr link-inside" on-click="loadMore(list)">
            <div class="table-td"></div>
        </div>
    </div>

    <div class="search-filter is_animated cabinet">
        <div class="search_loader" style="width: 100%">
            <div class="loader">
                <span class="load"></span>
            </div>
        </div>
    </div>

    <div v-on:click="paginate" class="btn">Load More</div>

</div>

</div>

</div>
