<div class="input-place" data-field="place" data-type="gmap_place" data-autocomplete="place_" data-additionals="place_id,address_components,formatted_address,geometry,types" data-types="all">

    <input spellcheck="false" @keyup="typing($event)"  @keyup.enter="enter" @blur="blur()" type="text" data-focus value="" v-model="choice" autocomplete="off">
        <ul class="list-scroll">
            <li v-for="item, index in list" @click="click(item)" :class="{selected:index==selected}">
                {{item.name}}
            </li>
        </ul>

</div>