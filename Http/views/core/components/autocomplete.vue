<div>
        <input spellcheck="false" @keyup="typing($event)"  @keyup.enter="enter" @blur="blur()" type="text"  data-focus value="" v-model="choice" autocomplete="off">
        <ul class="list-scroll">
            <li v-for="item, index in list" @click="click(item)" :class="{selected:index==selected}">
                {{item.name}}
            </li>
        </ul>
</div>