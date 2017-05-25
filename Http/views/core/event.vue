<div>
Page event
<ul>
<li v-for="item in events.models">
    (({{item.type}})) {{trad('translate.index.title')}}
</li>    

    <component-table :list="events" :actions=true></component-table>
</div>
