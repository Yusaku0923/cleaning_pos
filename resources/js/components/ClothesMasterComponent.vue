<template>
    <div class="col-12">
        <div class="col-10 mx-auto">
            <div>
                <div class="card col-12 py-3 mb-4 h4 text-center">
                    カテゴリー
                </div>
                <div class="col-12 menu-columns justify-content-between">
                    <!-- 商品選択時のみ表示 -->
                    <button
                        class="card menu-card col-12 mx-auto text-dark text-decoration-none"
                        v-if="mode === 'clothes'"
                        @click="switchCategory()"
                    >
                        <span class="fw-light text-secondary return-icon text-center mx-auto"><i class="fa-solid fa-rotate-left"></i></span>
                        <span class="text-center mx-auto">もどる</span>
                    </button>

                    <!-- 追加ボタン（選択画面によって変える） -->
                    <button type="button" class="card menu-card col-12 mx-auto">
                        <span class="fw-light text-primary menu-icon text-center mx-auto">＋</span>
                        <span class="text-center mx-auto">カテゴリーを追加する</span>
                    </button>
                    
                    <!-- カテゴリ表示 -->
                    <template v-if="mode === 'category'">
                        <button
                            class="card menu-card col-12 mx-auto"
                            v-for="(card, index, _) in cards"
                            :key="index"
                            @click="switchClothes(index)"
                        >
                            <div class="category-label-field position-relative">
                                <p class="col-12 category-label text-center position-absolute top-50 start-50 translate-middle fs-22">{{ card.name }}</p>
                            </div>
                        </button>
                    </template>

                    <!-- 商品名 -->
                    <template v-if="mode === 'clothes'">
                        <button
                            class="card menu-card col-12 mx-auto"
                            v-for="(card, index, _) in cards"
                            :key="index"
                            @click="selectClothes(index)"
                        >
                            <div class="clothes-label-upper-field">
                                <span class="col-12 clothes-label mx-auto text-center fs-22">{{ card.name }}</span>
                            </div>
                            <div class="clothes-label-lower-field text-center">
                                <span class="price-label mx-auto text-center">{{ card.price.toLocaleString() }}円</span>
                            </div>
                        </button>
                    </template>

                </div>
            </div>
        </div>
        <!-- modal -->
        <clothes-edit-modal
            @close="close"
            @update="updateClothes"
            :mode="modalMode"
            :info="modalInfo"
            v-if="showModal"
        ></clothes-edit-modal>
        <!-- modal -->
    </div>
</template>

<script>

import ClothesEditModal from './Modals/ClothesEditModalComponent';

export default {
    props: {
        category_clothes: {
            require: true
        },
        token: {
            require: true
        }
    },
    data() {
        return {
            mode: 'category',
            cards: this.category_clothes,
            showModal: false,
            selectedCategory: '',
            selectedClothes: '',
            modalMode: '',
            modalInfo: ''
        }
    },
    components: {
        'clothes-edit-modal': ClothesEditModal,
    },
    methods: {
        close: function() {
            this.showModal = false;
        },
        switchClothes: function(index) {
            this.mode = 'clothes';
            this.cards = this.category_clothes[index]['clothes'];
            this.selectedCategory = this.category_clothes[index]['id'];
        },
        switchCategory: function() {
            this.mode = 'category';
            this.cards = this.category_clothes;
        },
        selectClothes: function(index) {
            this.modalMode = 'edit';
            this.modalInfo = this.cards[index];
            this.showModal = true;
            this.selectedClothes = this.cards[index]['id'];
        },
        updateClothes: function(name, name_kana, price, tag_count) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/clothes/update', {
                id: this.selectedClothes,
                category_id: this.selectedCategory,
                name: name,
                name_kana: name_kana,
                price: price,
                tag_count: tag_count,
            })
            .then(function (response) {
                // location.reload();
                console.log(response);
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        }
    }
}
</script>