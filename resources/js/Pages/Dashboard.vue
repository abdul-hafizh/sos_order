<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Search,
    ImagePlus,
    Package,
    Layers,
    UploadCloud,
    X,
    ShoppingCart,
    Plus,
} from 'lucide-vue-next';

const props = defineProps({
    barangs: Object,
    filters: Object,
    image_keyword: String,
    image_path: String,
    keranjang: Object,
});

const previewImage = ref(null);

const hasResult = computed(() => props.barangs?.data?.length > 0);
const hasSearch = computed(() => params.value.search || props.image_keyword);
const cartItems = computed(() => props.keranjang?.items || []);
const totalCartQty = computed(() => props.keranjang?.total_baris || 0);
const showCart = ref(false);

const addCartForm = useForm({
    id_barang: null,
    qty: 1,
});

const barangBaruForm = useForm({
    nama_barang: '',
    qty: 1,
    satuan: '',
    catatan: '',
    gambar: null,
    image_path: '',
});

const addToCart = (barang) => {
    addCartForm.id_barang = barang.id_barang;
    addCartForm.qty = 1;

    addCartForm.post(route('keranjang.storeBarang'), {
        preserveScroll: true,
    });
};

const addBarangBaruToCart = () => {
    barangBaruForm.nama_barang = props.image_keyword || params.value.search || 'Barang baru';
    barangBaruForm.image_path = props.image_path || '';

    barangBaruForm.post(route('keranjang.storeBarangBaru'), {
        preserveScroll: true,
        onSuccess: () => {
            showCart.value = true;
        },
    });
};

const getCartImage = (item) => {
    if (item.gambar_permintaan) {
        return item.gambar_permintaan.startsWith('http')
            ? item.gambar_permintaan
            : `/storage/${item.gambar_permintaan}`;
    }

    const images = item.barang?.details?.flatMap((detail) => detail.gambars || []) || [];

    if (images.length && images[0].path_file) {
        return `/storage/${images[0].path_file}`;
    }

    return null;
};

const resetSearch = () => {
    previewImage.value = null;
    imageForm.reset();
    params.value.search = '';

    router.get(route('dashboard'), {}, {
        preserveState: false,
        replace: true,
    });
};

const updateCartQty = (item, qty) => {
    if (qty < 1) return;

    router.put(route('keranjang.updateQty', item.id_keranjang_detail), {
        qty,
    }, {
        preserveScroll: true,
    });
};

const removeCartItem = (item) => {
    router.delete(route('keranjang.destroy', item.id_keranjang_detail), {
        preserveScroll: true,
    });
};

const searchByImage = () => {
    if (!imageForm.image) return;

    imageForm.post(route('dashboard.search-image'), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: false,
    });
};

const params = ref({
    search: props.filters?.search || '',
});

watch(params, (newParams) => {
    router.get(route('dashboard'), newParams, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, { deep: true });

const imageForm = useForm({
    image: null,
});

const rupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const getFirstImage = (barang) => {
    const images = barang.details?.flatMap((detail) => detail.gambars || []) || [];
    return images[0]?.path_file ? `/storage/${images[0].path_file}` : null;
};

const handleImage = (event) => {
    const file = event.target.files?.[0];

    if (!file) return;

    imageForm.image = file;
    previewImage.value = URL.createObjectURL(file);
};

const clearImage = () => {
    imageForm.image = null;
    previewImage.value = null;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                <p class="text-sm text-gray-400">Cari barang berdasarkan teks atau gambar</p>
            </div>
        </template>

        <div class="py-7 px-6 w-full">
            <button
                type="button"
                class="fixed bottom-6 right-6 z-40 bg-blue-700 text-white rounded-full shadow-xl p-4 hover:bg-blue-800"
                @click="showCart = true"
            >
                <ShoppingCart class="w-6 h-6" />

                <span
                    v-if="totalCartQty > 0"
                    class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full min-w-6 h-6 flex items-center justify-center px-1"
                >
                    {{ totalCartQty }}
                </span>
            </button>

            <div class="rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900 text-white p-8 shadow-xl mb-7">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 bg-white/15 px-4 py-2 rounded-full text-sm mb-4">
                        <Package class="w-4 h-4" />
                        Smart Product Search
                    </div>

                    <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                        Temukan barang lebih cepat dengan teks atau gambar.
                    </h1>

                    <p class="text-blue-100 mt-3">
                        Ketik nama barang seperti biasa, atau upload foto barang agar sistem mencari produk yang mirip.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-blue-100 text-blue-700 p-3 rounded-2xl">
                            <Search class="w-6 h-6" />
                        </div>

                        <div>
                            <h3 class="font-bold text-gray-900">Cari berdasarkan teks</h3>
                            <p class="text-sm text-gray-400">Cari berdasarkan nama barang atau kode barang.</p>
                        </div>
                    </div>

                    <div class="relative">
                        <Search class="w-5 h-5 absolute left-4 top-3.5 text-gray-400" />

                        <Input
                            v-model="params.search"
                            placeholder="Contoh: botol, tumbler, paper bag..."
                            class="pl-11 h-12 rounded-2xl text-base"
                        />
                    </div>
                </div>

                <form
                    class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6"
                    @submit.prevent="searchByImage"
                >
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-indigo-100 text-indigo-700 p-3 rounded-2xl">
                            <ImagePlus class="w-6 h-6" />
                        </div>

                        <div>
                            <h3 class="font-bold text-gray-900">Cari berdasarkan gambar</h3>
                            <p class="text-sm text-gray-400">Upload foto barang.</p>
                        </div>
                    </div>

                    <label
                        v-if="!previewImage"
                        class="border-2 border-dashed border-gray-300 hover:border-indigo-500 hover:bg-indigo-50 transition rounded-3xl p-6 flex flex-col items-center justify-center cursor-pointer"
                    >
                        <UploadCloud class="w-9 h-9 text-indigo-700 mb-2" />
                        <span class="text-sm font-semibold text-gray-700">Klik untuk upload gambar</span>
                        <span class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP</span>

                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleImage"
                        />
                    </label>

                    <div v-else class="relative rounded-3xl overflow-hidden border">
                        <img :src="previewImage" class="w-full h-48 object-cover" />

                        <button
                            type="button"
                            class="absolute top-3 right-3 bg-black/60 text-white p-2 rounded-full"
                            @click="clearImage"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <Button
                        type="submit"
                        class="w-full mt-4 bg-indigo-700 text-white rounded-2xl h-11"
                        :disabled="!imageForm.image || imageForm.processing"
                    >
                        {{ imageForm.processing ? 'Mencari...' : 'Cari dengan Gambar' }}
                    </Button>

                    <p v-if="imageForm.errors.image" class="text-xs text-red-500 mt-2">
                        {{ imageForm.errors.image }}
                    </p>
                </form>
            </div>

            <div
                v-if="image_keyword"
                class="mb-5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-2xl px-5 py-3 text-sm"
            >
                Keyword dari gambar:
                <span class="font-semibold">{{ image_keyword }}</span>
            </div>

            <div
                v-if="image_keyword"
                class="mb-6 bg-white border border-indigo-100 rounded-3xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <h3 class="font-bold text-gray-900">
                        Barang yang dicari tidak ada?
                    </h3>

                    <p class="text-sm text-gray-500">
                        Anda tetap bisa memasukkan hasil foto ke keranjang sebagai
                        <b>permintaan barang baru</b>.
                    </p>
                </div>

                <Button
                    type="button"
                    class="bg-indigo-700 text-white rounded-2xl"
                    @click="addBarangBaruToCart"
                >
                    <Plus class="w-4 h-4 mr-2" />
                    Masukkan Keranjang Hasil Foto
                </Button>
            </div>

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Hasil Pencarian Barang</h3>
                    <p class="text-sm text-gray-400">
                        Menampilkan {{ barangs?.data?.length || 0 }} barang.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row gap-3 justify-center mt-5">
                    <Button
                        type="button"
                        variant="outline"
                        class="rounded-2xl"
                        @click="resetSearch"
                    >
                        Reset
                    </Button>
                </div>
            </div>

            <div
                v-if="barangs?.data?.length"
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6"
            >
                <div
                    v-for="barang in barangs.data"
                    :key="barang.id_barang"
                    class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition overflow-hidden group"
                >
                    <div class="h-52 bg-gray-100 relative overflow-hidden">
                        <img
                            v-if="getFirstImage(barang)"
                            :src="getFirstImage(barang)"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        />

                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <Package class="w-16 h-16" />
                        </div>

                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-semibold">
                            {{ barang.kode_barang }}
                        </div>
                    </div>

                    <div class="p-5">
                        <h4 class="font-bold text-gray-900 line-clamp-2 min-h-[48px]">
                            {{ barang.nama_barang }}
                        </h4>

                        <p class="text-blue-700 font-bold mt-2">
                            {{ rupiah(barang.harga_jual) }}
                        </p>

                        <div class="flex items-center justify-between text-sm text-gray-500 mt-4">
                            <span>Stok: {{ barang.stok ?? 0 }}</span>
                            <span>{{ barang.satuan }}</span>
                        </div>

                        <div class="flex items-center gap-2 mt-4 text-sm text-gray-500">
                            <Layers class="w-4 h-4" />
                            <span>{{ barang.details?.length || 0 }} varian</span>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3">
                            <span
                                v-for="variant in barang.details?.slice(0, 3)"
                                :key="variant.id_barang_detail"
                                class="text-xs bg-gray-100 px-3 py-1 rounded-full"
                            >
                                {{ variant.nama_variant }}
                            </span>

                            <span
                                v-if="barang.details?.length > 3"
                                class="text-xs bg-gray-100 px-3 py-1 rounded-full"
                            >
                                +{{ barang.details.length - 3 }}
                            </span>
                        </div>

                        <Button
                            type="button"
                            class="w-full mt-5 bg-blue-700 text-white rounded-2xl"
                            @click="addToCart(barang)"
                        >
                            <ShoppingCart class="w-4 h-4 mr-2" />
                            Masukkan Keranjang
                        </Button>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-3xl border border-red-100 shadow-sm p-12 text-center">
                <Package class="w-16 h-16 mx-auto text-red-300 mb-4" />

                <h3 class="font-bold text-red-700">
                    Barang tidak ditemukan
                </h3>

                <p class="text-sm text-gray-500 mt-2">
                    Barang yang Anda cari tidak tersedia di database.
                </p>

                <p v-if="image_keyword" class="text-sm text-gray-400 mt-2">
                    Keyword dari gambar:
                    <span class="font-semibold">{{ image_keyword }}</span>
                </p>

                <p class="text-sm text-blue-600 mt-3">
                    Anda tetap dapat membuat permintaan barang baru. Gambar yang diupload akan disimpan sebagai referensi untuk admin.
                </p>

                <div class="flex flex-col md:flex-row justify-center gap-3 mt-6">
                    <Button
                        type="button"
                        class="bg-blue-700 text-white rounded-2xl"
                        @click="addBarangBaruToCart"
                        :disabled="barangBaruForm.processing"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        {{ barangBaruForm.processing ? 'Memasukkan...' : 'Masukkan sebagai Barang Baru' }}
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        class="rounded-2xl"
                        @click="resetSearch"
                    >
                        Reset
                    </Button>
                </div>
            </div>

            <div v-if="barangs?.links?.length" class="mt-8 flex flex-wrap gap-2 justify-center">
                <Link
                    v-for="(link, index) in barangs.links"
                    :key="index"
                    :href="link.url ?? '#'"
                >
                    <Button
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </Link>
            </div>
        </div>

        <div
            v-if="showCart"
            class="fixed inset-0 z-50 bg-black/50 flex justify-end"
        >
            <div class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col">
                <div class="p-5 border-b flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Keranjang</h3>
                        <p class="text-sm text-gray-400">
                            {{ totalCartQty }} item kebutuhan
                        </p>
                    </div>

                    <button
                        type="button"
                        class="p-2 rounded-full hover:bg-gray-100"
                        @click="showCart = false"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <div
                        v-if="!cartItems.length"
                        class="text-center py-12 text-gray-400"
                    >
                        <ShoppingCart class="w-12 h-12 mx-auto mb-3" />
                        <p class="text-sm">Keranjang masih kosong.</p>
                    </div>

                    <div
                        v-for="item in cartItems"
                        :key="item.id_keranjang_detail"
                        class="border rounded-2xl p-4"
                    >
                        <div class="flex gap-3">
                            <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center">
                                <img
                                    v-if="getCartImage(item)"
                                    :src="getCartImage(item)"
                                    class="w-full h-full object-cover"
                                />

                                <Package v-else class="w-7 h-7 text-gray-400" />
                            </div>

                            <div class="flex-1">
                                <h4 class="font-semibold text-sm text-gray-900">
                                    {{ item.nama_barang }}
                                </h4>

                                <p class="text-xs mt-1"
                                :class="item.tipe_item === 'barang_baru' ? 'text-orange-600' : 'text-blue-600'">
                                    {{ item.tipe_item === 'barang_baru' ? 'Permintaan barang baru' : 'Barang tersedia' }}
                                </p>

                                <div class="flex items-center gap-2 mt-3">
                                    <button
                                        type="button"
                                        class="w-8 h-8 rounded-lg border"
                                        @click="updateCartQty(item, Number(item.qty) - 1)"
                                    >
                                        -
                                    </button>

                                    <Input
                                        :model-value="item.qty"
                                        type="number"
                                        min="1"
                                        class="w-16 h-8 text-center"
                                        @change="updateCartQty(item, Number($event.target.value))"
                                    />

                                    <button
                                        type="button"
                                        class="w-8 h-8 rounded-lg border"
                                        @click="updateCartQty(item, Number(item.qty) + 1)"
                                    >
                                        +
                                    </button>

                                    <span class="text-xs text-gray-400">
                                        {{ item.satuan }}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="text-red-500"
                                @click="removeCartItem(item)"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t">
                    <Button
                        type="button"
                        class="w-full bg-blue-700 text-white rounded-2xl h-12"
                        :disabled="!cartItems.length"
                    >
                        Pesan Sekarang
                    </Button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>