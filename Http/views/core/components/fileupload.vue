<div class="file-upload" v-if="type == 'picture'" @click="open">
    <img v-if="!image" :src="user.getPicture(150,150)" />
    <img v-else :src="image" data-preview />
    <div class="cta-white-s-dark icon-photos">
        <a href="#">(( edit_image ))</a>
    </div>
    <input type="file" class="picture"  @change="uploadPicture"/>
</div>