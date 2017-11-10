<div class="component error">


<div class="bars" v-if="errors_realtime.max">
    <template v-for="item in errors_realtime.models">
        <div v-if="item"   :style="{height:Math.round(item.count/errors_realtime.max*100)+'px'}"adi> 
            {{item.count}}
        </div>
    </template>
</div>
error
<component-table :list="errors">
</component-table>
</div>