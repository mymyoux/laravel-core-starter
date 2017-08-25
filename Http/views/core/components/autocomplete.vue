<div class="input-autocomplete">

    <input spellcheck="false" @focus="focus" @keyup="typing($event)"  @keydown.tab="blur" @keyup.enter="enter" type="text" v-model="choice" autocomplete="off">
        <ul class="list-scroll">
            <li v-for="item, index in list" @click="click(item)" :class="{selected:index==selected}">
                {{item.name}}
            </li>
        </ul>

</div>