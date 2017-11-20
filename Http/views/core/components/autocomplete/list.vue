<div class="input-place" :class="[(!selection || !onrest) ? 'white' : '']" data-field="place" data-type="gmap_place" data-autocomplete="place_" data-additionals="place_id,address_components,formatted_address,geometry,types" data-types="all">
    <div class="item_selected"  @click="activate" v-if="onrest && selection && tag">
        <slot name="item_selected" :text="choice">
            <template v-if="choice">{{choice}}
            </template>
            <slot name="first" v-else>
            </slot>
        </slot>
        <slot name="remove"></slot>
    </div>
    <div class="empty" v-else>
        <input spellcheck="false" :placeholder="((premessage_placeholder))" :tabindex="tabindex"  @keyup="typing($event)"  @keydown.enter="enter($event)" @blur="blur($event)" @focus="focus()" type="text" data-focus value="" :class="[choice ? 'selection' : '']" v-model="choice" autocomplete="off">
        <slot name="remove"></slot>
    </div>
    <ul class="list-scroll" v-show="!hidden">
        <li v-for="item, index in list.models" @mousedown="click(item)" :class="{selected:index==selected}">
            <slot  name="item" :text="item">
                   {{item.name}}
            </slot>
        </li>
    </ul>
</div>