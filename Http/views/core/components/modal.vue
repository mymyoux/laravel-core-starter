<div>
    <div class="modal hide" id="#myModal">
        <div class="modal-dialog">
            <div class="modal-content big">
                <div class="modal-header">
                    <button @click="closeModal()" type="button" class="close"><span aria-hidden="true">x</span></button>
                    <h1>Alert</h1>
                </div>

                <div class="modal-body">

                    <section class="description">
                        {{ data.description }}
                    </section>

                    <div class="item">
                        <p>Company :</p>
                        <div class="custom-select tag scroll" >
                            <div class="company">
                                <component-autocomplete :selection="data.company_name"></component-autocomplete>
                            </div>
                        </div>
                    </div>


                    <section class="search main-search">

                        <div class="item">
                            <p>Jobs :</p>
                            <div class="custom-select tag scroll" >
                                <div data-field="position" data-type="reallist">
                                    <ul class="click select">
                                        <p class="ellipsis">
                                            <span class="icon-position"></span>
                                            <span>
                                            {{ getTags(data.position) }}
                                        </span>
                                        </p>
                                        <ul class="list-scroll click">
                                            <li v-on:click="eventSelect(position)" v-for="position in data.position" :value="position.value">
                                                <span class="checkbox-square" v-bind:class="[position.selected ? 'active' : '']"></span>
                                                {{ position.type }}
                                            </li>
                                        </ul>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="item gmap-search">
                            <p>Places:</p>
                            <div class="input-container">
                                <span class="icon-location"></span>
                            <component-autocomplete :selection="data.location"></component-autocomplete>
                            </div>
                        </div>
                       
                        <div class="item">
                            <p>With the following skills:</p>
                            <div class="input-container">
                                <span class="icon-skills"></span>
                                <input id="skills-select" data-field="search" type="search" value="" on-keyup="onKeyUp(event)" placeholder="Boolean searching" on-focus="onFocus(event)">
                            </div>
                        </div>

                    </section>


                    <section class="search main-search">

                         <div class="item">
                            <p>Experience :</p>
                            <div class="custom-select tag scroll" >
                                <div data-field="experience" data-type="reallist">
                                    <ul class="click select">
                                        <p class="ellipsis">
                                            <span class="icon-position"></span>
                                            <span v-if="data.modalInfo.extract && data.modalInfo.extract.json.experiences">
                                             {{ getTags(data.experience) }}
                                            </span>
                                        </p>
                                        <ul class="list-scroll click">
                                            <li v-on:click="eventSelect(experience)" v-for="experience in data.experience" :value="experience.value">
                                                <span class="checkbox-square" v-bind:class="[experience.selected ? 'active' : '']"></span>
                                                {{ experience.type }}
                                            </li>
                                        </ul>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="item">
                            <p>Spoken languages:</p>
                            <div class="custom-select tag scroll" >
                                <div id="language" data-field="language" data-type="reallist">
                                    <ul class="click select">
                                        <p class="ellipsis">
                                            <span class="icon-position"></span>
                                            <span v-if="data.modalInfo.extract && data.modalInfo.extract.json.languages">
                                            {{ getTags(data.language) }}
                                        </span>
                                        </p>
                                        <ul class="list-scroll click" data-multiple data-static>
                                            <li v-on:click="eventSelect(language)" v-for="language in data.language" :value="language.value">
                                                <span class="checkbox-square" v-bind:class="[language.selected ? 'active' : '']"></span>
                                                {{ language.type }}
                                            </li>
                                        </ul>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </section>

                    <section class="footer">
                        <a v-on:click="alert(data)" title="" class="btn btn-color btn-alert">Create Alert</a>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

