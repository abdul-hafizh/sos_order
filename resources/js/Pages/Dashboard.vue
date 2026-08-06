<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm, Link, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import {
    Search,
    ImagePlus,
    Package,
    Layers,
    UploadCloud,
    X,
    ShoppingCart,
    Plus,
    Building2,
} from "lucide-vue-next";

const props = defineProps({
    barangs: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    filters: Object,
    image_keyword: String,
    image_path: String,
    keranjang: Object,
});

const previewImage = ref(props.image_path ? `/storage/${props.image_path}` : null);

const imageForm = useForm({
    image: null,
});

const previewModalData = ref({
    images: [],
    activeIndex: 0,
    item: null,
});

const openPreview = (item, index = 0) => {
    selectedItem.value = item;

    previewModalData.value = {
        images: item.gambar.map((g) => `/storage/${g.gambar}`),
        activeIndex: index,
        item,
    };
};

const uploadInput = ref(null);
const selectedItem = ref(null);

const detailModalData = ref(null);

const openDetail = (barang) => {
    detailModalData.value = barang;
};

const closeDetail = () => {
    detailModalData.value = null;
};

const getDetailImages = (barang) => {
    const images = barang?.produk?.gambars || [];
    return images.map((g) => `/storage/${g.path_file}`);
};

const openUpload = (item) => {
    selectedItem.value = item;
    uploadInput.value.click();
};

const cartItems = computed(() => props.keranjang?.items || []);
const totalCartQty = computed(() => props.keranjang?.total_baris || 0);
const showCart = ref(false);

const currentUser = computed(() => usePage().props.auth?.user);
const currentCabangName = computed(
    () => currentUser.value?.cabang?.cabang_nama || currentUser.value?.kode_cabang,
);

const addCartForm = useForm({
    id_barang: null,
    qty: 1,
});

const uploadGambar = (e) => {
    const files = e.target.files;

    const formData = new FormData();

    for (const file of files) {
        formData.append("gambar[]", file);
    }

    router.post(
        route("keranjang.uploadGambar", selectedItem.value.id_keranjang_detail),
        formData,
        {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: (page) => {
                const item = page.props.keranjang.items.find(
                    (x) =>
                        x.id_keranjang_detail ===
                        selectedItem.value.id_keranjang_detail,
                );

                if (item) {
                    previewModalData.value.item = item;
                    previewModalData.value.images = item.gambar.map(
                        (g) => `/storage/${g.gambar}`,
                    );
                }
            },
        },
    );
};

const updateNamaBarangBaru = (item, namaBarang) => {
    if (item.tipe_item !== "barang_baru") return;

    const nama = namaBarang.trim();

    if (!nama || nama === item.nama_barang) return;

    router.put(
        route("keranjang.updateNamaBarangBaru", item.id_keranjang_detail),
        {
            nama_barang: nama,
        },
        {
            preserveScroll: true,
        },
    );
};
const triggerUploadFromModal = () => {
    if (!previewModalData.value.item) {
        alert("Data item tidak ditemukan.");
        return;
    }

    selectedItem.value = previewModalData.value.item;

    uploadInput.value?.click();
};

const barangBaruForm = useForm({
    nama_barang: "",
    qty: 1,
    satuan: "",
    catatan: "",
    gambar: null,
    image_path: "",
});

const addToCart = (barang) => {
    addCartForm.id_barang = barang.id_barang;
    addCartForm.qty = 1;

    addCartForm.post(route("keranjang.storeBarang"), {
        preserveScroll: true,
    });
};

const addBarangBaruToCart = () => {
    const urlParams = new URLSearchParams(window.location.search);

    barangBaruForm.nama_barang =
        props.image_keyword ||
        urlParams.get("image_keyword") ||
        params.value.search ||
        "Barang baru";

    barangBaruForm.image_path =
        props.image_path || urlParams.get("image_path") || "";

    barangBaruForm.post(route("keranjang.storeBarangBaru"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCart.value = true;
        },
    });
};

const getCartImage = (item) => {
    if (item.tipe_item !== "barang_baru") {
        const masterImages = item.barang?.produk?.gambars || [];
        if (masterImages.length) {
            return `/storage/${masterImages[0].path_file}`;
        }
        return null;
    }

    if (item.gambar?.length) {
        return `/storage/${item.gambar[0].gambar}`;
    }

    return null;
};

const resetSearch = () => {
    previewImage.value = null;
    imageForm.reset();
    params.value.search = "";
    appliedSearch.value = params.value.search;
    params.value.category_code = "";

    router.get(
        route("dashboard"),
        {},
        {
            preserveState: false,
            replace: true,
        },
    );
};

const updateCartQty = (item, qty) => {
    if (qty < 1) return;

    router.put(
        route("keranjang.updateQty", item.id_keranjang_detail),
        {
            qty,
        },
        {
            preserveScroll: true,
        },
    );
};

const removeCartItem = (item) => {
    router.delete(route("keranjang.destroy", item.id_keranjang_detail), {
        preserveScroll: true,
    });
};

const searchByImage = () => {
    if (!imageForm.image) {
        alert("Pilih gambar dulu.");
        return;
    }

    const formData = new FormData();
    formData.append("image", imageForm.image);

    router.post(route("dashboard.search-image"), formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: false,
        onError: (errors) => {
            alert(errors.image || "Gagal upload gambar");
        },
    });
};

const params = ref({
    search: props.filters?.search || "",
    category_code: props.filters?.category_code || "",
    image_keyword: props.image_keyword || "",
    image_path: props.image_path || "",
});

const selectedCategory = computed(() => {
    return params.value.category_code;
});

const appliedSearch = ref(props.filters.search || "");
const searchData = () => {
    appliedSearch.value = params.value.search;
    router.get(
        route("dashboard"),
        {
            search: params.value.search,
            per_page: params.value.per_page,
            category_code: params.value.category_code,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const selectCategory = (categoryCode) => {
    params.value.category_code = categoryCode;

    router.get(
        route("dashboard"),
        {
            search: params.value.search,
            category_code: categoryCode,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const rupiah = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const getFirstImage = (barang) => {
    const images = barang.produk?.gambars || [];
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

const pesanSekarang = () => {
    if (!confirm("Apakah kamu yakin ingin menyimpan pesanan ini ke SPK?")) {
        return;
    }

    router.post(
        route("keranjang.pesanSekarang"),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showCart.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
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

            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-8"
            >
                <!-- Header Utama -->
                <div class="flex items-center justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-50 text-blue-600 p-3 rounded-2xl">
                            <Search class="w-6 h-6" />
                        </div>
                        <div>
                            <h3
                                class="font-bold text-gray-900 text-lg md:text-xl"
                            >
                                Dashboard
                            </h3>
                            <p class="text-xs md:text-sm text-gray-400">
                                Cari barang berdasarkan teks atau gambar
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="currentCabangName"
                        class="hidden sm:flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-100 px-4 py-2 rounded-2xl text-sm font-semibold shrink-0"
                    >
                        <Building2 class="w-4 h-4" />
                        {{ currentCabangName }}
                    </div>
                </div>

                <div
                    v-if="image_keyword"
                    class="my-5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-2xl px-5 py-3 text-sm"
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
                            Anda tetap bisa memasukkan hasil foto ke keranjang
                            sebagai
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
                <div class="my-5">
                    <!-- Header & Tombol Reset -->
                    <!-- Layout Utama: Sidebar & Area Konten (Horizontal Scroll) -->
                    <div class="flex flex-col lg:flex-row gap-6 items-start">
                        <!-- Sidebar: Filter (Teks, Gambar) & Kategori -->
                        <aside
                            class="w-full lg:w-72 bg-gray-50 rounded-3xl border border-gray-100 p-4 h-fit lg:sticky lg:top-6 flex-shrink-0 shadow-sm space-y-5"
                        >
                            <!-- Filter: Berdasarkan Nama / Kode -->
                            <div class="px-2">
                                <label
                                    class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2"
                                >
                                    Cari Nama / Kode
                                </label>
                                <div class="relative">
                                    <Search
                                        class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                    />
                                    <Input
                                        v-model="params.search"
                                        @keyup.enter="searchData"
                                        placeholder="Contoh: botol, tumbler..."
                                        class="pl-11 pr-10 h-12 rounded-xl text-sm border-gray-200 focus:border-blue-600 focus:ring-blue-600/20 w-full bg-white"
                                    />
                                    <button
                                        v-if="params.search"
                                        type="button"
                                        @click="resetSearch"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition duration-200 p-1"
                                        title="Bersihkan pencarian"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div
                                    v-if="appliedSearch"
                                    class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-200/60 text-xs text-gray-500"
                                >
                                    <div
                                        class="flex items-center gap-1.5 flex-wrap"
                                    >
                                        <span>Keyword:</span>
                                        <span
                                            class="bg-blue-50 border border-blue-100 text-blue-700 font-semibold px-2.5 py-0.5 rounded-full"
                                        >
                                            "{{ appliedSearch }}"
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="resetSearch"
                                        class="text-red-600 hover:underline font-medium"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Filter: Berdasarkan Foto Produk -->
                            <form
                                @submit.prevent="searchByImage"
                                class="px-2 pt-1 border-t border-gray-200/60"
                            >
                                <label
                                    class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2 mt-4"
                                >
                                    Cari dengan Foto
                                </label>

                                <label
                                    v-if="!previewImage"
                                    class="border-2 border-dashed border-gray-300 hover:border-indigo-500 hover:bg-indigo-50/50 transition rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer bg-white aspect-square"
                                >
                                    <UploadCloud
                                        class="w-8 h-8 text-indigo-600 mb-2"
                                    />
                                    <span
                                        class="text-xs font-semibold text-gray-700"
                                        >Upload Gambar</span
                                    >
                                    <span class="text-[11px] text-gray-400 mt-1"
                                        >Klik untuk memilih foto</span
                                    >

                                    <input
                                        name="image"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleImage"
                                    />
                                </label>

                                <div
                                    v-else
                                    class="relative rounded-2xl overflow-hidden border bg-white flex items-center justify-center aspect-square p-2"
                                >
                                    <img
                                        :src="previewImage"
                                        class="max-w-full max-h-full object-contain"
                                    />

                                    <button
                                        type="button"
                                        class="absolute top-2 right-2 bg-black/60 hover:bg-black text-white p-1.5 rounded-full transition"
                                        @click="clearImage"
                                        title="Hapus gambar"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <Button
                                    type="submit"
                                    class="w-full mt-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl h-10 text-xs md:text-sm font-medium shadow-sm"
                                    :disabled="
                                        !imageForm.image || imageForm.processing
                                    "
                                >
                                    {{
                                        imageForm.processing
                                            ? "Mencari..."
                                            : "Cari dengan Gambar"
                                    }}
                                </Button>
                                <p
                                    v-if="imageForm.errors.image"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ imageForm.errors.image }}
                                </p>
                            </form>

                            <div class="px-2 pt-1 border-t border-gray-200/60">
                                <div
                                    class="flex items-center gap-2 mb-3 mt-4"
                                >
                                    <Layers class="w-5 h-5 text-blue-600" />
                                    <h4 class="font-bold text-gray-900">
                                        Kategori
                                    </h4>
                                </div>

                                <div class="space-y-1">
                                    <!-- Semua Kategori -->
                                    <button
                                        type="button"
                                        @click="selectCategory('')"
                                        class="w-full text-left px-4 py-2.5 rounded-2xl text-sm transition font-medium"
                                        :class="
                                            !selectedCategory
                                                ? 'bg-blue-600 text-white font-semibold shadow-sm'
                                                : 'text-gray-600 hover:bg-white hover:text-blue-600'
                                        "
                                    >
                                        Semua Kategori
                                    </button>

                                    <!-- List Kategori -->
                                    <button
                                        v-for="category in categories"
                                        :key="category.code"
                                        type="button"
                                        @click="selectCategory(category.code)"
                                        class="w-full text-left px-4 py-2.5 rounded-2xl text-sm transition font-medium"
                                        :class="
                                            selectedCategory === category.code
                                                ? 'bg-blue-600 text-white font-semibold shadow-sm'
                                                : 'text-gray-600 hover:bg-white hover:text-blue-600'
                                        "
                                    >
                                        {{ category.name }}
                                    </button>
                                </div>
                            </div>
                        </aside>

                        <!-- Area Card Barang / Empty State -->
                        <div class="flex-grow w-full">
                            <!-- Kondisi: Ada Barang (Grid ala Marketplace) -->
                            <div
                                v-if="barangs?.data?.length"
                                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4"
                            >
                                <div
                                    v-for="barang in barangs.data"
                                    :key="barang.id_barang"
                                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group flex flex-col"
                                >
                                    <!-- Gambar & Badge Kode Barang -->
                                    <div
                                        class="aspect-square bg-gray-100 relative overflow-hidden"
                                    >
                                        <img
                                            v-if="getFirstImage(barang)"
                                            :src="getFirstImage(barang)"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                        />

                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center text-gray-400"
                                        >
                                            <Package class="w-10 h-10" />
                                        </div>

                                        <div
                                            class="absolute top-2 left-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded-full text-[10px] font-semibold shadow-sm"
                                        >
                                            {{ barang.kode_barang }}
                                        </div>
                                    </div>

                                    <!-- Informasi Detail Card -->
                                    <div class="p-3 flex flex-col flex-grow">
                                        <h4
                                            class="font-semibold text-gray-900 text-sm line-clamp-2 min-h-[40px]"
                                        >
                                            {{ barang.nama_barang }}
                                        </h4>

                                        <div
                                            class="text-blue-600 font-bold text-base mt-1.5"
                                        >
                                            {{ rupiah(barang.harga_jual) }}
                                        </div>

                                        <!-- Stok & Satuan -->
                                        <div
                                            class="flex items-center justify-between text-xs text-gray-500 mt-auto pt-1.5"
                                        >
                                            <span
                                                >Stok:
                                                {{ barang.stok ?? 0 }}</span
                                            >
                                            <span
                                                class="font-medium text-gray-700"
                                                >{{ barang.satuan }}</span
                                            >
                                        </div>

                                        <!-- Tombol Aksi -->
                                        <div class="flex gap-2 mt-3">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                class="flex-1 rounded-xl text-xs h-9 shadow-sm"
                                                @click="openDetail(barang)"
                                            >
                                                Detail
                                            </Button>

                                            <Button
                                                type="button"
                                                class="flex-1 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs h-9 shadow-sm"
                                                @click="addToCart(barang)"
                                            >
                                                <ShoppingCart
                                                    class="w-3.5 h-3.5 mr-1.5"
                                                />
                                                Tambah
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kondisi: Barang Tidak Ditemukan (Empty State) -->
                            <div
                                v-else
                                class="bg-white rounded-3xl border border-red-100 shadow-sm p-8 md:p-12 text-center"
                            >
                                <Package
                                    class="w-16 h-16 mx-auto text-red-300 mb-4"
                                />

                                <h3 class="font-bold text-red-700 text-lg">
                                    Barang tidak ditemukan
                                </h3>

                                <p
                                    class="text-sm text-gray-500 mt-2 max-w-md mx-auto"
                                >
                                    Barang yang Anda cari tidak tersedia di
                                    database.
                                </p>

                                <p
                                    v-if="image_keyword"
                                    class="text-sm text-gray-400 mt-2"
                                >
                                    Keyword dari gambar:
                                    <span class="font-semibold text-gray-700">{{
                                        image_keyword
                                    }}</span>
                                </p>

                                <p
                                    class="text-sm text-blue-600 mt-3 max-w-md mx-auto bg-blue-50 p-3 rounded-2xl border border-blue-100"
                                >
                                    Anda tetap dapat membuat permintaan barang
                                    baru. Gambar yang diupload akan disimpan
                                    sebagai referensi untuk admin.
                                </p>

                                <div
                                    class="flex flex-col md:flex-row justify-center gap-3 mt-6"
                                >
                                    <Button
                                        type="button"
                                        class="bg-blue-700 hover:bg-blue-800 text-white rounded-2xl shadow-sm"
                                        @click="addBarangBaruToCart"
                                        :disabled="barangBaruForm.processing"
                                    >
                                        <Plus class="w-4 h-4 mr-2" />
                                        {{
                                            barangBaruForm.processing
                                                ? "Memasukkan..."
                                                : "Masukkan sebagai Barang Baru"
                                        }}
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
                        </div>
                    </div>

                    <!-- Pagination Links -->
                    <div
                        v-if="barangs?.links?.length"
                        class="mt-8 flex flex-wrap gap-2 justify-center"
                    >
                        <Link
                            v-for="(link, index) in barangs.links"
                            :key="index"
                            :href="link.url ?? '#'"
                        >
                            <Button
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                class="rounded-xl"
                                v-html="link.label"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showCart"
            class="fixed inset-0 z-50 bg-black/50 flex justify-end"
        >
            <div
                class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col"
            >
                <div class="p-5 border-b flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">
                            Keranjang
                        </h3>
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
                            <div>
                                <div
                                    v-if="item.tipe_item === 'barang_baru'"
                                    class="relative w-16 h-16 cursor-pointer"
                                    @click="openUpload(item)"
                                >
                                    <template v-if="item.gambar?.length">
                                        <img
                                            v-for="(
                                                img, index
                                            ) in item.gambar.slice(0, 3)"
                                            :key="img.id_gambar"
                                            :src="`/storage/${img.gambar}`"
                                            class="absolute w-14 h-14 object-contain rounded-xl border-2 border-white shadow-lg transition-all duration-200 hover:z-50"
                                            :style="{
                                                left: `${index * 6}px`,
                                                top: `${index * 4}px`,
                                                zIndex: index + 1,
                                                transform: `rotate(${(index - 1) * 4}deg)`,
                                            }"
                                            @click.stop="
                                                openPreview(item, index)
                                            "
                                        />

                                        <div
                                            v-if="item.gambar.length > 3"
                                            class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center border-2 border-white shadow"
                                        >
                                            +{{ item.gambar.length - 3 }}
                                        </div>
                                    </template>

                                    <div
                                        v-else
                                        class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center border"
                                    >
                                        <ImagePlus
                                            class="w-6 h-6 text-gray-400"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="w-16 h-16 rounded-xl overflow-hidden border bg-gray-50 flex items-center justify-center pointer-events-none"
                                >
                                    <img
                                        v-if="getCartImage(item)"
                                        :src="getCartImage(item)"
                                        class="w-full h-full object-cover"
                                    />
                                    <Package
                                        v-else
                                        class="w-6 h-6 text-gray-400"
                                    />
                                </div>
                            </div>
                            <div class="flex-1">
                                <Input
                                    v-if="item.tipe_item === 'barang_baru'"
                                    :model-value="item.nama_barang"
                                    class="h-9 text-sm font-semibold"
                                    @change="
                                        updateNamaBarangBaru(
                                            item,
                                            $event.target.value,
                                        )
                                    "
                                />

                                <h4
                                    v-else
                                    class="font-semibold text-sm text-gray-900"
                                >
                                    {{ item.nama_barang }}
                                </h4>

                                <p
                                    class="text-xs mt-1"
                                    :class="
                                        item.tipe_item === 'barang_baru'
                                            ? 'text-orange-600'
                                            : 'text-blue-600'
                                    "
                                >
                                    {{
                                        item.tipe_item === "barang_baru"
                                            ? "Permintaan barang baru"
                                            : "Barang tersedia"
                                    }}
                                </p>

                                <div class="flex items-center gap-2 mt-3">
                                    <button
                                        type="button"
                                        class="w-8 h-8 rounded-lg border"
                                        @click="
                                            updateCartQty(
                                                item,
                                                Number(item.qty) - 1,
                                            )
                                        "
                                    >
                                        -
                                    </button>

                                    <Input
                                        :model-value="item.qty"
                                        type="number"
                                        min="1"
                                        class="w-16 h-8 text-center"
                                        @change="
                                            updateCartQty(
                                                item,
                                                Number($event.target.value),
                                            )
                                        "
                                    />

                                    <button
                                        type="button"
                                        class="w-8 h-8 rounded-lg border"
                                        @click="
                                            updateCartQty(
                                                item,
                                                Number(item.qty) + 1,
                                            )
                                        "
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
                                class="text-red-500 self-start mt-1"
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
                        @click="pesanSekarang"
                    >
                        Pesan Sekarang
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <div
        v-if="detailModalData"
        class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-6"
        @click="closeDetail"
    >
        <div
            class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop
        >
            <div
                class="flex items-center justify-between px-6 py-5 border-b bg-gray-50"
            >
                <div>
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ detailModalData.nama_barang }}
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Kode Barang:
                        <span class="font-semibold">{{
                            detailModalData.kode_barang
                        }}</span>
                    </p>
                </div>

                <button
                    class="w-10 h-10 rounded-xl hover:bg-gray-200 transition flex items-center justify-center"
                    @click="closeDetail"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Galeri Foto -->
                <div
                    v-if="getDetailImages(detailModalData).length"
                    class="grid grid-cols-3 md:grid-cols-4 gap-3"
                >
                    <div
                        v-for="(img, index) in getDetailImages(
                            detailModalData,
                        )"
                        :key="index"
                        class="rounded-xl overflow-hidden border border-gray-200 bg-gray-50 aspect-square"
                    >
                        <img
                            :src="img"
                            class="w-full h-full object-contain"
                        />
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-8 flex flex-col items-center text-gray-400"
                >
                    <Package class="w-8 h-8 mb-2" />
                    <span class="text-xs">Belum ada foto produk</span>
                </div>

                <!-- Detail Produk -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Nama Produk
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{
                                detailModalData.produk?.produk?.nama_produk ??
                                "-"
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Tipe
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.produk?.tipe?.nama ?? "-" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Satuan
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.produk?.satuan?.nama ?? "-" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Berat
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.produk?.berat?.nama ?? "-" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Ukuran
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.produk?.ukuran?.nama ?? "-" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Warna
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.produk?.warna?.nama ?? "-" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Karakter
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{
                                detailModalData.produk?.karakter?.nama ?? "-"
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            UOM
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{
                                detailModalData.produk?.uom?.nama_uom ?? "-"
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Harga Jual
                        </p>
                        <p class="font-semibold text-blue-600">
                            {{ rupiah(detailModalData.harga_jual) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
                            Stok
                        </p>
                        <p class="font-semibold text-gray-900">
                            {{ detailModalData.stok ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="border-t bg-white px-6 py-5 flex justify-end gap-3"
            >
                <Button variant="outline" class="rounded-2xl" @click="closeDetail">
                    Tutup
                </Button>

                <Button
                    class="bg-blue-700 hover:bg-blue-800 text-white rounded-2xl"
                    @click="
                        addToCart(detailModalData);
                        closeDetail();
                    "
                >
                    <ShoppingCart class="w-4 h-4 mr-2" />
                    Tambah ke Keranjang
                </Button>
            </div>
        </div>
    </div>

    <div
        v-if="previewModalData.item"
        class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-6"
        @click="previewModalData.item = null"
    >
        <div
            class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop
        >
            <div
                class="flex items-center justify-between px-6 py-5 border-b bg-gray-50"
            >
                <div>
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ previewModalData.item.nama_barang }}
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        {{ previewModalData.images.length }} Foto
                    </p>
                </div>

                <button
                    class="w-10 h-10 rounded-xl hover:bg-gray-200 transition flex items-center justify-center"
                    @click="previewModalData.item = null"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <div
                    class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5"
                >
                    <div
                        v-for="(img, index) in previewModalData.images"
                        :key="index"
                        class="group rounded-2xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow-xl transition"
                    >
                        <img
                            :src="img"
                            class="w-full aspect-square object-contain transition duration-300 group-hover:scale-105"
                        />

                        <div
                            class="px-3 py-2 text-xs text-gray-500 border-t bg-gray-50"
                        >
                            Foto {{ index + 1 }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="border-t bg-white px-6 py-5 flex justify-between items-center"
            >
                <div class="text-sm text-gray-500">
                    Total Foto:
                    <span class="font-semibold text-gray-900">
                        {{ previewModalData.images.length }}
                    </span>
                </div>

                <div class="flex gap-3">
                    <Button
                        variant="outline"
                        class="rounded-2xl"
                        @click="previewModalData.item = null"
                    >
                        Tutup
                    </Button>

                    <Button
                        class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl"
                        @click="triggerUploadFromModal"
                    >
                        <ImagePlus class="w-4 h-4 mr-2" />
                        Tambah Foto
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <input
        ref="uploadInput"
        type="file"
        multiple
        class="hidden"
        @change="
            (e) => {
                console.log('CHANGE');
                console.log(e.target.files);
                uploadGambar(e);
            }
        "
    />
</template>
