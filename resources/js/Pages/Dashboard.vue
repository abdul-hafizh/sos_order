<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm, Link } from "@inertiajs/vue3";
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
    ArrowUp,
    ArrowDown,
    Minus,
} from "lucide-vue-next";

const props = defineProps({
    barangs: Object,
    filters: Object,
    image_keyword: String,
    image_path: String,
    keranjang: Object,
});

const previewImage = ref(null);

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

const openUpload = (item) => {
    selectedItem.value = item;
    uploadInput.value.click();
};

const hasResult = computed(() => props.barangs?.data?.length > 0);
const hasSearch = computed(() => params.value.search || props.image_keyword);
const cartItems = computed(() => props.keranjang?.items || []);
const totalCartQty = computed(() => props.keranjang?.total_baris || 0);
const showCart = ref(false);

const getTrend = (current, before) => {
    if (current > before) return { icon: ArrowUp, color: "text-green-600" };
    if (current < before) return { icon: ArrowDown, color: "text-red-600" };
    return { icon: Minus, color: "text-gray-400" };
};

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
    image_keyword: props.image_keyword || "",
    image_path: props.image_path || "",
});

const appliedSearch = ref(props.filters.search || "");
const searchData = () => {
    appliedSearch.value = params.value.search;
    router.get(
        route("dashboard"),
        {
            search: params.value.search,
            per_page: params.value.per_page,
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
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                <p class="text-sm text-gray-400">
                    Cari barang berdasarkan teks atau gambar
                </p>
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

            <div
                class="rounded-3xl bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900 text-white p-8 shadow-xl mb-7"
            >
                <div class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 bg-white/15 px-4 py-2 rounded-full text-sm mb-4"
                    >
                        <Package class="w-4 h-4" />
                        Smart Product Search
                    </div>

                    <h1 class="text-3xl md:text-4xl font-bold leading-tight">
                        Temukan barang lebih cepat dengan teks atau gambar.
                    </h1>

                    <p class="text-blue-100 mt-3">
                        Ketik nama barang seperti biasa, atau upload foto barang
                        agar sistem mencari produk yang mirip.
                    </p>
                </div>
            </div>

            <div
                class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-8"
            >
                <!-- Header Utama -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="bg-blue-50 text-blue-600 p-3 rounded-2xl">
                        <Search class="w-6 h-6" />
                    </div>
                    <div>
                        <h3
                            class="font-bold text-gray-900 text-base md:text-lg"
                        >
                            Pencarian Produk
                        </h3>
                        <p class="text-xs md:text-sm text-gray-400">
                            Cari produk lebih cepat menggunakan teks atau upload
                            foto.
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch"
                >
                    <div
                        class="lg:col-span-2 flex flex-col justify-between bg-gray-50/50 p-5 rounded-2xl border border-gray-100"
                    >
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2"
                            >
                                Berdasarkan Nama / Kode
                            </label>
                            <div class="relative">
                                <Search
                                    class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                />
                                <Input
                                    v-model="params.search"
                                    @keyup.enter="searchData"
                                    placeholder="Contoh: botol, tumbler, paper bag..."
                                    class="pl-11 pr-10 h-12 rounded-xl text-base border-gray-200 focus:border-blue-600 focus:ring-blue-600/20 w-full bg-white"
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
                        </div>

                        <div
                            v-if="appliedSearch"
                            class="flex flex-wrap items-center justify-between gap-2 mt-4 pt-3 border-t border-gray-200/60 text-xs text-gray-500"
                        >
                            <div class="flex items-center gap-1.5 flex-wrap">
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

                    <form
                        @submit.prevent="searchByImage"
                        class="lg:col-span-1 flex flex-col justify-between bg-gray-50/50 p-5 rounded-2xl border border-gray-100"
                    >
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2"
                            >
                                Berdasarkan Foto Produk
                            </label>

                            <label
                                v-if="!previewImage"
                                class="border-2 border-dashed border-gray-300 hover:border-indigo-500 hover:bg-indigo-50/50 transition rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer bg-white h-[48px]"
                            >
                                <div
                                    class="flex items-center gap-2 text-gray-500"
                                >
                                    <UploadCloud
                                        class="w-5 h-5 text-indigo-600"
                                    />
                                    <span
                                        class="text-xs font-semibold text-gray-700"
                                        >Upload Gambar</span
                                    >
                                </div>

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
                                class="relative rounded-2xl overflow-hidden border bg-white flex items-center justify-center h-[48px] px-3"
                            >
                                <img
                                    :src="previewImage"
                                    class="h-full object-contain py-1"
                                />

                                <button
                                    type="button"
                                    class="absolute right-2 bg-black/60 hover:bg-black text-white p-1 rounded-full transition"
                                    @click="clearImage"
                                    title="Hapus gambar"
                                >
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <Button
                                type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl h-10 text-xs md:text-sm font-medium shadow-sm"
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
                        </div>
                    </form>
                </div>
                <div
                    v-if="image_keyword"
                    class="my-5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-2xl px-5 py-3 text-sm"
                >
                    Keyword dari gambar:
                    <span class="font-semibold">{{ image_keyword }}</span>
                    <div class=" "></div>
                </div>

                <div v-if="image_path" class="flex justify-center my-6">
                    <img
                        :src="`/storage/${image_path}`"
                        class="w-[350px] h-[350px] object-contain rounded-2xl border shadow-md"
                    />
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
                <div class="flex items-center justify-between my-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">
                        Hasil Pencarian Barang
                    </h3>
                    <p class="text-sm text-gray-400">
                        Menampilkan {{ barangs?.data?.length || 0 }} barang.
                    </p>
                </div>

                <div
                    class="flex flex-col md:flex-row gap-3 justify-center mt-5"
                >
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
                class="flex overflow-x-auto gap-3 md:gap-6 pb-4 pt-1 snap-x snap-mandatory scrollbar-thin scrollbar-thumb-gray-200 scrollbar-track-transparent"
            >
                <div
                    v-for="barang in barangs.data"
                    :key="barang.id_barang"
                    class="flex-shrink-0 w-[260px] md:w-[320px] snap-start bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition overflow-hidden group flex flex-col"
                >
                    <div
                        class="h-32 md:h-52 bg-gray-100 relative overflow-hidden"
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
                            <Package class="w-10 h-10 md:w-16 md:h-16" />
                        </div>

                        <div
                            class="absolute top-2 left-2 md:top-3 md:left-3 bg-white/90 backdrop-blur px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-semibold"
                        >
                            {{ barang.kode_barang }}
                        </div>
                    </div>

                    <div class="p-3 md:p-5 flex flex-col flex-grow">
                        <h4
                            class="font-bold text-gray-900 text-sm md:text-base line-clamp-2 min-h-[36px] md:min-h-[48px]"
                        >
                            {{ barang.nama_barang }}
                        </h4>

                        <div
                            class="space-y-1.5 bg-gray-50/70 p-3 rounded-xl border border-gray-100/80 mb-3 mt-2"
                        >
                            <div
                                class="flex items-center justify-between text-xs md:text-sm"
                            >
                                <span class="text-gray-500 font-medium"
                                    >Harga Beli</span
                                >
                                <div
                                    class="flex items-center gap-1.5 font-semibold text-gray-700"
                                >
                                    <span>{{ rupiah(barang.harga_beli) }}</span>
                                    <component
                                        :is="
                                            getTrend(
                                                barang.harga_beli,
                                                barang.harga_beli_before,
                                            ).icon
                                        "
                                        :class="
                                            getTrend(
                                                barang.harga_beli,
                                                barang.harga_beli_before,
                                            ).color
                                        "
                                        class="w-3.5 h-3.5"
                                    />
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between text-xs md:text-sm"
                            >
                                <span class="text-gray-500 font-medium"
                                    >Harga Jual</span
                                >
                                <div
                                    class="flex items-center gap-1.5 font-semibold text-blue-600"
                                >
                                    <span>{{ rupiah(barang.harga_jual) }}</span>
                                    <component
                                        :is="
                                            getTrend(
                                                barang.harga_jual,
                                                barang.harga_jual_before,
                                            ).icon
                                        "
                                        :class="
                                            getTrend(
                                                barang.harga_jual,
                                                barang.harga_jual_before,
                                            ).color
                                        "
                                        class="w-3.5 h-3.5"
                                    />
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between text-xs md:text-sm"
                            >
                                <span class="text-gray-500 font-medium"
                                    >Harga Jumbo</span
                                >
                                <div
                                    class="flex items-center gap-1.5 font-semibold text-indigo-600"
                                >
                                    <span>{{
                                        rupiah(barang.harga_jual_jumbo)
                                    }}</span>
                                    <component
                                        :is="
                                            getTrend(
                                                barang.harga_jual_jumbo,
                                                barang.harga_jual_jumbo_before,
                                            ).icon
                                        "
                                        :class="
                                            getTrend(
                                                barang.harga_jual_jumbo,
                                                barang.harga_jual_jumbo_before,
                                            ).color
                                        "
                                        class="w-3.5 h-3.5"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between text-xs md:text-sm text-gray-500 mt-auto"
                        >
                            <span>Stok: {{ barang.stok ?? 0 }}</span>
                            <span>{{ barang.satuan }}</span>
                        </div>

                        <div
                            class="hidden md:flex items-center gap-2 mt-3 text-sm text-gray-500"
                        >
                            <Layers class="w-4 h-4" />
                            <span
                                >{{ barang.details?.length || 0 }} varian</span
                            >
                        </div>

                        <div class="hidden md:flex flex-wrap gap-2 mt-2">
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
                            class="w-full mt-3 md:mt-4 bg-blue-700 text-white rounded-2xl text-xs md:text-sm h-9 md:h-10"
                            @click="addToCart(barang)"
                        >
                            <ShoppingCart class="w-4 h-4 mr-1 md:mr-2" />
                            <span class="hidden sm:inline"
                                >Masukkan Keranjang</span
                            >
                            <span class="sm:hidden">Tambah</span>
                        </Button>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="bg-white rounded-3xl border border-red-100 shadow-sm p-12 text-center"
            >
                <Package class="w-16 h-16 mx-auto text-red-300 mb-4" />

                <h3 class="font-bold text-red-700">Barang tidak ditemukan</h3>

                <p class="text-sm text-gray-500 mt-2">
                    Barang yang Anda cari tidak tersedia di database.
                </p>

                <p v-if="image_keyword" class="text-sm text-gray-400 mt-2">
                    Keyword dari gambar:
                    <span class="font-semibold">{{ image_keyword }}</span>
                </p>

                <p class="text-sm text-blue-600 mt-3">
                    Anda tetap dapat membuat permintaan barang baru. Gambar yang
                    diupload akan disimpan sebagai referensi untuk admin.
                </p>

                <div
                    class="flex flex-col md:flex-row justify-center gap-3 mt-6"
                >
                    <Button
                        type="button"
                        class="bg-blue-700 text-white rounded-2xl"
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
                        v-html="link.label"
                    />
                </Link>
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
