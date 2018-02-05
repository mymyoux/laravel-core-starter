<div class="component error">

<input type="checkbox" v-model="is_api" @change="setIsApi(is_api)"> API <br/>
<input type="checkbox" v-model="front" @change="setFront(front)"> FRONT<br/>

<component-error-graph></component-error-graph> 

<component-table :list="errors">
</component-table>
</div>