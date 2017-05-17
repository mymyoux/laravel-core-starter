<div>
<span v-if="item">
    {{item.id_event}} name
</span>
<span v-else>
    no item
</span>
<span v-if="column">
    {{column.title}} column
</span>
<span v-else>
    no column
</span>
</div>