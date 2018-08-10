<div class="file-upload" v-if="type == 'picture'" :class="picture == 'cover'?'full-width':''" @click="open">
    <template v-if="picture == 'profile'">
        <img v-if="!image" :src="user.company.getPicture(150,150)" :class="type" />
        <img v-else :src="image" :class="type" />
        <div class="cta-white-s-dark icon-photos">
            <a href="#">(( edit_image ))</a>
        </div>
        <input type="file" class="picture"  @change="uploadPicture"/>
    </template>

    <template v-if="picture == 'cover'">
        <div v-if="image" class="profile-picture edit cover" :style="'background-image:url(\'' + image + '\')'"></div>
        <div class="profile-picture edit cover" :style="'background-image:url(\'' + (user.company.profile.cover ? user.company.profile.cover : '/css/img/default-avatar.svg')" v-else ></div>
        <div class="cta-white-s-dark icon-photos">
            <a href="#">(( edit_image ))</a>
        </div>
        <input type="file" class="picture" @change="uploadPicture" />
    </template>

    <template v-if="picture == 'picture'">
        <div v-if="image" class="profile-picture edit shadow has-image" :style="'background-size: 140px 248px;background-position: 0px -54px;background-image:url(\'' + image + '\')'">
            <span class="icon-photos"></span>
            <input type="file" class="picture" @change="uploadPicture"/>
        </div>
        <div class="profile-picture edit" :class="(user.company.profile.pictures[index].picture ? '' : 'no-picture')" :style="'background-image:url(\'' + (user.company.profile.pictures[index].picture ? user.company.profile.pictures[index].picture : '')" v-else >
            <span class="icon-photos" v-if="!user.company.profile.pictures[index].picture"></span>
            <input type="file" class="picture" @change="uploadPicture"/>
        </div> 
    </template>
</div>