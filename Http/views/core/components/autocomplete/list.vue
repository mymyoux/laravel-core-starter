<div class="input-place" data-field="place" data-type="gmap_place" data-autocomplete="place_" data-additionals="place_id,address_components,formatted_address,geometry,types" data-types="all">
    <div class="selected"  @click="activate" v-if="onrest && selected_item">
        <slot name="item_selected" :text="selected_item">
            <template v-if="choice">{{choice}}
            </template>
            <slot name="first" v-else>
            </slot>
        </slot>
    </div>
    <input v-else spellcheck="false" :placeholder="((premessage_placeholder))" @keyup="typing($event)"  @keyup.enter="enter" @blur="blur($event)" @focus="focus()" type="text" data-focus value="" v-model="choice" autocomplete="off">
        <ul class="list-scroll" v-show="!hidden">
            <li v-for="item, index in list.models" @mousedown="click(item)" :class="{selected:index==selected}">
                <slot  name="item" :text="item">
                        {{item.name}}
                </slot>
            </li>
        </ul>

</div>