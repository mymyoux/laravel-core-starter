<div>
Page event
    <ul>
    <span>{{events.models.length}}</span>
    <li v-for="item in events.models" @click="showEvent(item)">
        ((*item.shortType, {'event':item}))
    </li>    
    </ul>
    <!-- <component-table :list="events" :actions=true></component-table>-->
    <div v-if="event">
        <h1>
            ((*event.shortType,{'event':event}))
        </h1>
         <h2 v-if="event.step">
            ((*event.shortType+'-step-'+event.step,{'event':event}))
        </h2>
         <h2 v-if="getComponent">
            <component :is="getComponent"></component>
        </h2>
        <h2 v-else v-if="event.data">
            {{event.data}}
        </h2>
        <h2 v-if="event.result">
            {{event.result}}
        </h2>
    </div>
</div>
