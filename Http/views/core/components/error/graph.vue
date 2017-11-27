<div class="bars">
    <template v-if="errors_realtime.max"  v-for="item in errors_realtime.models">
        <div v-if="item"  :class="{selected:selected && selected.time === item.time}" :style="{height:Math.round(item.count/errors_realtime.max*100)+'px', 'background-color':item.color}" @click="select(item)"> 
            <p class="label">{{item.count}}</p>
            <p class="sublabel">{{item.time}}</p>
        </div>
    </template>
</div>