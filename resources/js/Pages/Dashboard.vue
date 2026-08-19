<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LoginModal from "@/Components/LoginModal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import {
    Search,
    ImageOff,
    Package,
    UploadCloud,
    X,
    ShoppingCart,
    Plus,
    Building2,
    Minus,
    Eye,
    Sparkles,
    Loader2,
    SlidersHorizontal,
    Tag,
    Grid,
    List,
    Layers,
    CheckCircle2,
    ShieldCheck,
    Truck,
    Flame,
    Star,
    ArrowRight,
    MapPin,
    BadgeCheck,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    variantList: Object,
    tipeList: {
        type: Array,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: Object,
    image_keyword: String,
    image_path: String,
    keranjang: Object,
});

// Login Modal State
const showLoginModal = ref(false);

// Catalog Section Anchor Reference for Point 1
const catalogSectionRef = ref(null);

// View Toggle Mode: 'grid' or 'list'
const viewMode = ref('grid');

const previewImage = ref(props.image_path ? `/storage/${props.image_path}` : null);

const imageForm = useForm({
    image: null,
});

// Guest Cart LocalStorage State
const guestCartItems = ref([]);

const loadGuestCart = () => {
    try {
        const saved = localStorage.getItem("guest_cart");
        if (saved) {
            guestCartItems.value = JSON.parse(saved);
        }
    } catch (e) {
        guestCartItems.value = [];
    }
};

const saveGuestCart = () => {
    try {
        localStorage.setItem("guest_cart", JSON.stringify(guestCartItems.value));
    } catch (e) {}
};

// Quick View Modal State
const quickViewVariant = ref(null);
const quickViewQty = ref(1);
const quickViewImageIndex = ref(0);

const openQuickView = (detail) => {
    quickViewVariant.value = detail;
    quickViewQty.value = 1;
    quickViewImageIndex.value = 0;
};

const closeQuickView = () => {
    quickViewVariant.value = null;
    quickViewQty.value = 1;
    quickViewImageIndex.value = 0;
};

const currentUser = computed(() => usePage().props.auth?.user);
const currentCabangName = computed(
    () => currentUser.value?.cabang?.cabang_nama || currentUser.value?.kode_cabang,
);

const addQuickViewToCart = () => {
    if (!quickViewVariant.value?.barang) return;

    if (!currentUser.value) {
        // Guest Cart Flow
        addGuestItem(quickViewVariant.value, quickViewQty.value);
        closeQuickView();
        showCart.value = true;
        return;
    }

    bulkAddForm.items = [{
        id_barang: quickViewVariant.value.barang.id_barang,
        qty: quickViewQty.value,
    }];

    bulkAddForm.post(route("keranjang.storeBarangBanyak"), {
        preserveScroll: true,
        onSuccess: () => {
            closeQuickView();
            showCart.value = true;
        },
    });
};

const uploadInput = ref(null);
const selectedItem = ref(null);
const currentUploadingItem = ref(null);
const cartItemFileInput = ref(null);

const triggerUploadItemGambar = (item) => {
    currentUploadingItem.value = item;
    cartItemFileInput.value?.click();
};

const handleItemMultipleImageUpload = (e) => {
    const files = e.target.files;
    if (!files || !files.length || !currentUploadingItem.value) return;

    if (!currentUser.value) {
        showLoginModal.value = true;
        return;
    }

    const formData = new FormData();
    for (const file of files) {
        formData.append("gambar[]", file);
    }

    router.post(
        route("keranjang.uploadGambar", currentUploadingItem.value.id_keranjang_detail),
        formData,
        {
            forceFormData: true,
            preserveScroll: true,
        },
    );
};

const variantQty = ref({});

watch(
    () => props.variantList,
    (list) => {
        variantQty.value = Object.fromEntries(
            (list?.data || []).map((d) => [d.id_produk_detail, 0]),
        );
    },
    { immediate: true },
);

const changeVariantQty = (detail, delta) => {
    const current = variantQty.value[detail.id_produk_detail] || 0;
    variantQty.value[detail.id_produk_detail] = Math.max(0, current + delta);
};

const variantLabel = (detail) => {
    const attrs = [
        detail.ukuran?.nama,
        detail.berat?.nama,
        detail.karakter?.nama,
        detail.satuan?.nama || detail.uom?.nama_uom,
    ]
        .filter(Boolean)
        .join(" - ");
    const produkNama = detail.produk?.nama_produk;

    if (produkNama && attrs) return `${produkNama} - ${attrs}`;

    return (
        produkNama ||
        attrs ||
        detail.barang?.nama_barang ||
        `Varian #${detail.id_produk_detail}`
    );
};

const totalSelectedQty = computed(() =>
    Object.values(variantQty.value).reduce((sum, q) => sum + (q || 0), 0),
);

const bulkAddForm = useForm({
    items: [],
});

// Guest Helper function to add item to guestCartItems
const addGuestItem = (detail, qtyToAdd) => {
    const existingIndex = guestCartItems.value.findIndex(
        (i) => i.id_barang === detail.barang.id_barang
    );

    if (existingIndex > -1) {
        guestCartItems.value[existingIndex].qty += qtyToAdd;
    } else {
        guestCartItems.value.push({
            id_keranjang_detail: `guest-${Date.now()}-${Math.random()}`,
            id_barang: detail.barang.id_barang,
            nama_barang: variantLabel(detail),
            qty: qtyToAdd,
            tipe_item: 'barang_terdaftar',
            barang: detail.barang,
            gambar_url: detail.gambars?.[0]?.path_file ? `/storage/${detail.gambars[0].path_file}` : null,
            gambar: [],
        });
    }

    saveGuestCart();
};

const addSelectedVariantsToCart = () => {
    const selected = (props.variantList?.data || []).filter(
        (d) => d.barang && (variantQty.value[d.id_produk_detail] || 0) > 0
    );

    if (!selected.length) return;

    if (!currentUser.value) {
        // Guest Cart Flow: Add all selected variants into LocalStorage Cart
        for (const detail of selected) {
            addGuestItem(detail, variantQty.value[detail.id_produk_detail]);
        }
        showCart.value = true;
        return;
    }

    const items = selected.map((d) => ({
        id_barang: d.barang.id_barang,
        qty: variantQty.value[d.id_produk_detail],
    }));

    bulkAddForm.items = items;

    bulkAddForm.post(route("keranjang.storeBarangBanyak"), {
        preserveScroll: true,
        onSuccess: () => {
            showCart.value = true;
        },
    });
};

const addSingleVariantToCart = (detail) => {
    if (!detail.barang) return;

    const qty = variantQty.value[detail.id_produk_detail] > 0
        ? variantQty.value[detail.id_produk_detail]
        : 1;

    if (!currentUser.value) {
        // Guest Cart Flow: Add single variant into LocalStorage Cart
        addGuestItem(detail, qty);
        showCart.value = true;
        return;
    }

    bulkAddForm.items = [{
        id_barang: detail.barang.id_barang,
        qty: qty,
    }];

    bulkAddForm.post(route("keranjang.storeBarangBanyak"), {
        preserveScroll: true,
        onSuccess: () => {
            showCart.value = true;
        },
    });
};

const cartItems = computed(() => {
    if (currentUser.value) {
        return props.keranjang?.items || [];
    }
    return guestCartItems.value;
});

const totalCartQty = computed(() => {
    if (currentUser.value) {
        return props.keranjang?.total_baris || 0;
    }
    return guestCartItems.value.length;
});

const showCart = ref(false);

const groupedCartItems = computed(() => {
    const groups = new Map();

    for (const item of cartItems.value) {
        const parent = item.barang?.produk?.produk;
        const key = parent ? `produk-${parent.id_produk}` : `item-${item.id_keranjang_detail}`;
        const label = parent
            ? parent.nama_produk
            : item.tipe_item === "barang_baru"
              ? "Permintaan Barang Baru"
              : item.nama_barang;

        if (!groups.has(key)) {
            groups.set(key, { key, label, items: [] });
        }

        groups.get(key).items.push(item);
    }

    return Array.from(groups.values());
});

const barangBaruForm = useForm({
    nama_barang: "",
    qty: 1,
    satuan: "",
    catatan: "",
    gambar: null,
    image_path: "",
});

const addBarangBaruToCart = () => {
    if (!currentUser.value) {
        showLoginModal.value = true;
        return;
    }

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
    if (item.gambar_url) {
        return item.gambar_url;
    }

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

    if (!currentUser.value) {
        // Guest Cart Qty Update
        const target = guestCartItems.value.find(i => i.id_keranjang_detail === item.id_keranjang_detail);
        if (target) {
            target.qty = qty;
            saveGuestCart();
        }
        return;
    }

    router.put(
        route("keranjang.updateQty", item.id_keranjang_detail),
        { qty },
        { preserveScroll: true },
    );
};

const removeCartItem = (item) => {
    if (!currentUser.value) {
        // Guest Cart Remove
        guestCartItems.value = guestCartItems.value.filter(i => i.id_keranjang_detail !== item.id_keranjang_detail);
        saveGuestCart();
        return;
    }

    router.delete(route("keranjang.destroy", item.id_keranjang_detail), {
        preserveScroll: true,
    });
};

const searchByImage = () => {
    if (!imageForm.image) {
        alert("Pilih gambar dulu.");
        return;
    }

    imageForm.post(route("dashboard.search-image"), {
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
});

const selectedCategory = computed(() => params.value.category_code);

// Point 1: Scroll down to catalog results section when filter/search is applied
const searchData = () => {
    router.get(
        route("dashboard"),
        {
            search: params.value.search,
            category_code: params.value.category_code,
        },
        { preserveState: true, replace: true },
    );
    if (catalogSectionRef.value) {
        catalogSectionRef.value.scrollIntoView({ behavior: 'smooth' });
    }
};

const selectCategory = (categoryCode) => {
    params.value.category_code = categoryCode;
    router.get(
        route("dashboard"),
        {
            search: params.value.search,
            category_code: categoryCode,
        },
        { preserveState: true, replace: true },
    );
    if (catalogSectionRef.value) {
        catalogSectionRef.value.scrollIntoView({ behavior: 'smooth' });
    }
};

// Masuk ke daftar varian 1 tipe (dari kartu tipe di mode browse)
const selectTipeGroup = (tipe) => {
    router.get(
        route("dashboard"),
        {
            category_code: params.value.category_code,
            id_tipe: tipe.id_tipe,
        },
        { preserveState: false },
    );
    if (catalogSectionRef.value) {
        catalogSectionRef.value.scrollIntoView({ behavior: 'smooth' });
    }
};

// Kembali dari daftar varian 1 tipe ke daftar tipe (mode browse)
const backToTipeList = () => {
    router.get(
        route("dashboard"),
        {
            category_code: params.value.category_code,
        },
        { preserveState: false },
    );
};

// Handle login success from LoginModal
const handleLoginSuccess = () => {
    showLoginModal.value = false;

    // Load and sync guest cart items to backend database
    loadGuestCart();
    if (guestCartItems.value.length > 0) {
        const itemsToSync = guestCartItems.value.map(i => ({
            id_barang: i.id_barang,
            qty: i.qty,
        }));

        bulkAddForm.items = itemsToSync;
        bulkAddForm.post(route("keranjang.storeBarangBanyak"), {
            preserveScroll: true,
            onSuccess: () => {
                guestCartItems.value = [];
                localStorage.removeItem("guest_cart");
                showCart.value = true;
            },
        });
    } else {
        showCart.value = true;
    }
};

// Sync guest cart to server after guest logs in
onMounted(() => {
    loadGuestCart();

    if (currentUser.value && guestCartItems.value.length > 0) {
        const itemsToSync = guestCartItems.value.map(i => ({
            id_barang: i.id_barang,
            qty: i.qty,
        }));

        bulkAddForm.items = itemsToSync;
        bulkAddForm.post(route("keranjang.storeBarangBanyak"), {
            preserveScroll: true,
            onSuccess: () => {
                guestCartItems.value = [];
                localStorage.removeItem("guest_cart");
            },
        });
    }

    if ((props.filters?.search || props.filters?.category_code) && catalogSectionRef.value) {
        setTimeout(() => {
            catalogSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
        }, 200);
    }
});

const rupiah = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value || 0);
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

// SPK Submission Handler: OPEN LOGIN MODAL IF GUEST
const pesanSekarang = () => {
    if (!currentUser.value) {
        // OPEN POPUP LOGIN MODAL FOR GUEST
        showLoginModal.value = true;
        return;
    }

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

const getCategoryName = (code) => {
    const found = props.categories.find(c => String(c.code) === String(code));
    return found ? found.name : 'Kategori';
};

// Point 2: Kategori Pilihan Pengadaan - diambil langsung dari data Category
// (menu master-kategori), dicocokkan ke produk lewat MasterProdukDetail.category_id.
const quickCategoryIcons = computed(() => [
    { title: 'Semua Kategori', gambar_url: null, catCode: '' },
    ...props.categories.map((cat) => ({
        title: cat.name,
        gambar_url: cat.gambar_url,
        catCode: cat.code,
    })),
]);
</script>

<template>
    <Head title="Katalog Pengadaan Barang - SOS ORDER" />

    <AuthenticatedLayout
        @open-login-modal="showLoginModal = true"
        @toggle-cart="showCart = !showCart"
    >
        <!-- Pop-Up Login Modal Component -->
        <LoginModal
            :show="showLoginModal"
            @close="showLoginModal = false"
            @success="handleLoginSuccess"
        />

        <!-- Floating Cart Trigger Button -->
        <button
            type="button"
            class="fixed bottom-6 right-6 z-50 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-2xl p-4 hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer border-2 border-white/20"
            @click="showCart = true"
        >
            <ShoppingCart class="w-6 h-6" />
            <span
                v-if="totalCartQty > 0"
                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-black rounded-full min-w-6 h-6 flex items-center justify-center px-1.5 border-2 border-white shadow-md animate-bounce"
            >
                {{ totalCartQty }}
            </span>
        </button>

        <div class="space-y-6 pb-20 font-sans">
            
            <!-- 1. Corporate Hero Banner (Original Indigo Theme) -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 md:p-8 shadow-xl border border-white/10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="absolute -right-12 -bottom-12 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="space-y-3 relative z-10 max-w-2xl">
                    <div class="inline-flex items-center space-x-2 bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 px-3.5 py-1 rounded-full text-xs font-bold">
                        <Sparkles class="w-3.5 h-3.5 text-yellow-300" />
                        <span>E-PROCUREMENT CATALOG 2026</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-black tracking-tight leading-tight">
                        Katalog Pengadaan Barang Internal SOS ORDER
                    </h1>
                    <p class="text-slate-300 text-xs sm:text-sm leading-relaxed font-medium">
                        Semua barang operasional terdaftar lengkap. Pilih varian barang, tentukan jumlah pesanan Qty, dan langsung terbit Dokumen SPK Gudang Pusat.
                    </p>
                    
                    <div class="pt-2 flex flex-wrap items-center gap-3 text-xs font-bold">
                        <span class="bg-white/10 px-3 py-1 rounded-lg text-white flex items-center space-x-1">
                            <BadgeCheck class="w-4 h-4 text-indigo-300" />
                            <span>100% Terverifikasi</span>
                        </span>
                        <span class="bg-white/10 px-3 py-1 rounded-lg text-white flex items-center space-x-1">
                            <Truck class="w-4 h-4 text-indigo-300" />
                            <span>Gudang GSOS</span>
                        </span>
                    </div>
                </div>

                <!-- Branch Status Badge -->
                <div v-if="currentCabangName" class="relative z-10 shrink-0 bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-3xl flex items-center space-x-4 shadow-lg">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center font-bold text-white shrink-0 shadow-2xs">
                        <Building2 class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-300 uppercase tracking-widest font-bold">Cabang Pemesan</span>
                        <h4 class="font-black text-white text-base">{{ currentCabangName }}</h4>
                        <span class="text-[11px] text-indigo-300 font-bold">● Terhubung Dokumen SPK</span>
                    </div>
                </div>
            </div>

            <!-- 2. Point 2: Category Section Evenly Aligned Grid ("kategori pada dashboard diratakan") -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-slate-900 font-black text-base">
                        <Grid class="w-5 h-5 text-indigo-600" />
                        <span>Kategori Pilihan Pengadaan</span>
                    </div>
                </div>

                <!-- Point 2: 10 Evenly Aligned Category Icon Cards Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-5 md:grid-cols-10 gap-3 justify-items-stretch">
                    <button
                        v-for="(item, idx) in quickCategoryIcons"
                        :key="idx"
                        type="button"
                        @click="selectCategory(item.catCode)"
                        class="w-full h-full flex flex-col items-center justify-center p-3 rounded-2xl border transition duration-200 group cursor-pointer hover:-translate-y-1 shadow-2xs text-center min-h-[90px]"
                        :class="selectedCategory === item.catCode && item.catCode ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200/80 hover:border-indigo-400 hover:bg-slate-50'"
                    >
                        <div class="w-11 h-11 rounded-2xl bg-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition flex items-center justify-center overflow-hidden shadow-2xs mb-1">
                            <img v-if="item.gambar_url" :src="item.gambar_url" class="w-full h-full object-cover" />
                            <Package v-else class="w-5 h-5" />
                        </div>
                        <span class="text-[11px] font-bold text-slate-800 group-hover:text-indigo-600 line-clamp-1 w-full text-center">
                            {{ item.title }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Keyword Banner if Image Search was performed -->
            <div v-if="image_keyword" class="bg-indigo-50 border border-indigo-200 text-indigo-950 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                        <Sparkles class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-xs text-indigo-600 font-bold">Hasil AI Photo Search:</span>
                        <h4 class="font-bold text-slate-900 text-sm">"{{ image_keyword }}"</h4>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <Button type="button" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl" @click="addBarangBaruToCart">
                        <Plus class="w-4 h-4 mr-1" />
                        Ajukan Sebagai Barang Baru
                    </Button>
                    <Button type="button" variant="outline" size="sm" class="text-xs rounded-xl" @click="resetSearch">
                        Reset
                    </Button>
                </div>
            </div>

            <!-- 4. Point 1: Catalog Results Section Anchor Reference ("saat difilter maka yang muncul pada hasil pencarian adalah isinya") -->
            <div ref="catalogSectionRef" class="scroll-mt-28 flex flex-col lg:flex-row gap-6 items-start">
                
                <!-- Left Sidebar Facet Filter Panel (Point 5: Filter Warna Dihilangkan) -->
                <aside class="w-full lg:w-72 bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs shrink-0 space-y-6 lg:sticky lg:top-24">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center space-x-2 text-slate-900 font-black text-sm">
                            <SlidersHorizontal class="w-4 h-4 text-indigo-600" />
                            <span>Filter Katalog</span>
                        </div>
                        <button v-if="params.search || params.category_code || previewImage" type="button" @click="resetSearch" class="text-xs font-bold text-red-600 hover:underline">
                            Reset Semua
                        </button>
                    </div>

                    <!-- Search Filter Input -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Cari Varian / SKU</label>
                        <div class="relative">
                            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                            <Input
                                v-model="params.search"
                                @keyup.enter="searchData"
                                placeholder="Nama, varian, atau SKU..."
                                class="pl-10 pr-8 h-10 text-xs rounded-xl border-slate-200 focus:border-indigo-600 focus:ring-indigo-100 bg-slate-50 w-full font-medium"
                            />
                            <button v-if="params.search" type="button" @click="resetSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Search by Image Box -->
                    <form @submit.prevent="searchByImage" class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pencarian Foto AI</label>
                        
                        <label v-if="!previewImage" class="border-2 border-dashed border-slate-300 hover:border-indigo-500 hover:bg-indigo-50/50 transition rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer bg-slate-50 aspect-video">
                            <UploadCloud class="w-6 h-6 text-indigo-600 mb-1" />
                            <span class="text-xs font-bold text-slate-700">Upload Foto Sampel</span>
                            <input name="image" type="file" accept="image/*" class="hidden" @change="handleImage" />
                        </label>

                        <div v-else class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center aspect-video p-2">
                            <img :src="previewImage" class="max-w-full max-h-full object-contain" />
                            <button type="button" class="absolute top-2 right-2 bg-black/70 hover:bg-black text-white p-1 rounded-full transition" @click="clearImage">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <Button type="submit" size="sm" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs h-9 font-bold flex items-center justify-center" :disabled="!imageForm.image || imageForm.processing">
                            <Loader2 v-if="imageForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                            {{ imageForm.processing ? "Mencari..." : "Cari Gambar" }}
                        </Button>
                    </form>

                    <!-- Category List -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Kategori</label>
                        
                        <div class="space-y-1">
                            <button
                                type="button"
                                @click="selectCategory('')"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition"
                                :class="!selectedCategory ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:bg-slate-100'"
                            >
                                <span>Semua Kategori</span>
                                <span class="text-[10px] opacity-80">({{ categories.length }})</span>
                            </button>

                            <button
                                v-for="cat in categories"
                                :key="cat.code"
                                type="button"
                                @click="selectCategory(cat.code)"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition"
                                :class="selectedCategory === cat.code ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 hover:bg-slate-100'"
                            >
                                <span class="truncate">{{ cat.name }}</span>
                            </button>
                        </div>
                    </div>
                </aside>

                <!-- Right Main Variant Product Catalog Grid -->
                <main class="flex-1 w-full space-y-5">

                    <!-- BROWSE MODE: Kelompok per Tipe Produk (default, sebelum search/pilih tipe) -->
                    <template v-if="tipeList">
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 flex items-center gap-3 shadow-xs">
                            <h3 class="font-extrabold text-slate-900 text-base">
                                {{ selectedCategory ? `Tipe Produk ${getCategoryName(selectedCategory)}` : 'Tipe Produk' }}
                            </h3>
                            <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-indigo-100">
                                {{ tipeList.length }} Tipe Ditemukan
                            </span>
                        </div>

                        <div v-if="tipeList.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-4 gap-4 md:gap-5">
                            <button
                                type="button"
                                v-for="tipe in tipeList"
                                :key="tipe.id_tipe"
                                @click="selectTipeGroup(tipe)"
                                class="group text-left bg-white rounded-3xl border border-slate-200/80 hover:border-indigo-400 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col cursor-pointer"
                            >
                                <div class="aspect-square bg-slate-50 relative overflow-hidden">
                                    <img
                                        v-if="tipe.gambar_url"
                                        :src="tipe.gambar_url"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                    />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-300 gap-1">
                                        <ImageOff class="w-8 h-8" />
                                        <span class="text-[10px] text-slate-400">Belum ada foto</span>
                                    </div>

                                    <div v-if="tipe.category_name" class="absolute top-3 left-3 bg-white/95 backdrop-blur-md px-2.5 py-0.5 rounded-full text-[10px] font-bold text-indigo-700 border border-slate-200/60 shadow-xs">
                                        {{ tipe.category_name }}
                                    </div>

                                    <div class="absolute top-3 right-3 bg-slate-900/80 text-white px-2 py-0.5 rounded-full text-[10px] font-bold">
                                        {{ tipe.jumlah_varian }} Varian
                                    </div>
                                </div>

                                <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                                    <h4 class="font-bold text-slate-900 text-sm line-clamp-2 group-hover:text-indigo-600 transition">
                                        {{ tipe.nama }}
                                    </h4>

                                    <div class="space-y-0.5">
                                        <div class="text-[10px] text-slate-400 font-semibold">Mulai dari</div>
                                        <div class="text-indigo-700 font-black text-base">
                                            {{ rupiah(tipe.harga_terendah) }}
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div v-else class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
                            <Package class="w-16 h-16 mx-auto text-slate-300" />
                            <h3 class="font-bold text-slate-900 text-base">Belum Ada Tipe Produk</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">Tidak ada tipe produk yang sesuai dengan kategori ini.</p>
                        </div>
                    </template>

                    <!-- FLAT MODE: Hasil pencarian teks/gambar, atau varian di dalam 1 tipe -->
                    <template v-else>

                    <!-- Breadcrumb kembali ke daftar tipe (hanya tampil saat sedang di dalam 1 tipe) -->
                    <button
                        v-if="filters?.id_tipe"
                        type="button"
                        @click="backToTipeList"
                        class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1"
                    >
                        ← Kembali ke Semua Tipe
                    </button>

                    <!-- Sorting Bar & View Switcher -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-extrabold text-slate-900 text-base">
                                {{ selectedCategory ? `Hasil Varian ${getCategoryName(selectedCategory)}` : 'Hasil Varian Barang' }}
                            </h3>
                            <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-indigo-100">
                                {{ variantList?.total || variantList?.data?.length || 0 }} Varian Ditemukan
                            </span>
                        </div>

                        <!-- Grid / List Mode Toggle Buttons -->
                        <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                            <button
                                type="button"
                                @click="viewMode = 'grid'"
                                class="p-1.5 rounded-lg transition font-bold text-xs flex items-center space-x-1 cursor-pointer"
                                :class="viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-500 hover:text-slate-900'"
                                title="Tampilan Grid Matrix"
                            >
                                <Grid class="w-4 h-4" />
                                <span class="hidden sm:inline">Grid</span>
                            </button>

                            <button
                                type="button"
                                @click="viewMode = 'list'"
                                class="p-1.5 rounded-lg transition font-bold text-xs flex items-center space-x-1 cursor-pointer"
                                :class="viewMode === 'list' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-500 hover:text-slate-900'"
                                title="Tampilan List Tabel"
                            >
                                <List class="w-4 h-4" />
                                <span class="hidden sm:inline">Tabel List</span>
                            </button>
                        </div>
                    </div>

                    <!-- 1. GRID VIEW MODE (Product Variant Cards) -->
                    <div v-if="variantList?.data?.length && viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-4 gap-4 md:gap-5">
                        <div
                            v-for="detail in variantList.data"
                            :key="detail.id_produk_detail"
                            class="group bg-white rounded-3xl border shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col justify-between"
                            :class="(variantQty[detail.id_produk_detail] || 0) > 0 ? 'border-indigo-600 ring-2 ring-indigo-600/20' : 'border-slate-200/80 hover:border-indigo-400'"
                        >
                            <div>
                                <!-- Image Container -->
                                <div class="aspect-square bg-slate-50 relative overflow-hidden cursor-pointer" @click="openQuickView(detail)">
                                    <img
                                        v-if="detail.gambars?.[0]"
                                        :src="`/storage/${detail.gambars[0].path_file}`"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                    />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-300 gap-1">
                                        <ImageOff class="w-8 h-8" />
                                        <span class="text-[10px] text-slate-400">Belum ada foto</span>
                                    </div>

                                    <!-- Category Pill Badge -->
                                    <div v-if="detail.category?.categoryname" class="absolute top-3 left-3 bg-white/95 backdrop-blur-md px-2.5 py-0.5 rounded-full text-[10px] font-bold text-indigo-700 border border-slate-200/60 shadow-xs">
                                        {{ detail.category.categoryname }}
                                    </div>

                                    <!-- Quick View Overlay Button -->
                                    <button
                                        type="button"
                                        @click.stop="openQuickView(detail)"
                                        class="absolute bottom-3 right-3 bg-slate-900/80 hover:bg-slate-900 text-white p-2 rounded-xl backdrop-blur transition-all opacity-0 group-hover:opacity-100 shadow-md"
                                        title="Quick View Detail Varian"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Card Details Info -->
                                <div class="p-4 space-y-2">
                                    <h4 class="font-bold text-slate-900 text-sm line-clamp-2 min-h-[40px] group-hover:text-indigo-600 transition cursor-pointer" @click="openQuickView(detail)">
                                        {{ variantLabel(detail) }}
                                    </h4>

                                    <!-- Rating & Stok -->
                                    <div class="flex items-center space-x-1.5 text-[11px] text-slate-500 font-medium">
                                        <span class="flex items-center text-amber-500 font-bold">
                                            <Star class="w-3.5 h-3.5 fill-amber-400 mr-0.5" />
                                            4.9
                                        </span>
                                        <span>•</span>
                                        <span>Stok: <b class="text-slate-800">{{ detail.barang?.stok ?? 0 }}</b></span>
                                    </div>

                                    <!-- Price & Location -->
                                    <div class="space-y-0.5 pt-1">
                                        <div class="text-indigo-700 font-black text-base">
                                            {{ rupiah(detail.barang?.harga_jual) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-semibold flex items-center">
                                            <span>📍 Gudang GSOS Pusat</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Qty Selector & Action Buttons -->
                            <div class="p-4 pt-0 space-y-2">
                                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl p-1.5">
                                    <button
                                        type="button"
                                        class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 transition shadow-2xs font-bold"
                                        @click="changeVariantQty(detail, -1)"
                                    >
                                        <Minus class="w-3.5 h-3.5" />
                                    </button>
                                    <span class="text-xs font-extrabold text-slate-900">
                                        {{ variantQty[detail.id_produk_detail] || 0 }}
                                    </span>
                                    <button
                                        type="button"
                                        class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 transition shadow-2xs font-bold"
                                        @click="changeVariantQty(detail, 1)"
                                    >
                                        <Plus class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <Button
                                    type="button"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs py-2 font-bold shadow-2xs flex items-center justify-center"
                                    @click="addSingleVariantToCart(detail)"
                                >
                                    <ShoppingCart class="w-3.5 h-3.5 mr-1.5" />
                                    + Keranjang
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. LIST VIEW MODE (Compact Table List) -->
                    <div v-else-if="variantList?.data?.length && viewMode === 'list'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 font-bold text-slate-700 uppercase tracking-wider">
                                    <tr>
                                        <th class="p-3.5">Varian Barang</th>
                                        <th class="p-3.5">SKU & Kategori</th>
                                        <th class="p-3.5">Spesifikasi</th>
                                        <th class="p-3.5">Harga Varian</th>
                                        <th class="p-3.5 text-center">Stok</th>
                                        <th class="p-3.5 text-center">Jumlah Qty</th>
                                        <th class="p-3.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="detail in variantList.data" :key="detail.id_produk_detail" class="hover:bg-slate-50/80 transition">
                                        <td class="p-3.5">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                                    <img v-if="detail.gambars?.[0]" :src="`/storage/${detail.gambars[0].path_file}`" class="w-full h-full object-cover" />
                                                    <ImageOff v-else class="w-5 h-5 text-slate-300 m-auto mt-2.5" />
                                                </div>
                                                <div>
                                                    <h5 class="font-bold text-slate-900 text-xs">{{ variantLabel(detail) }}</h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3.5">
                                            <div class="font-mono text-slate-700 font-bold text-[11px]">{{ detail.barang?.kode_barang || detail.kode_barang }}</div>
                                            <span v-if="detail.category?.categoryname" class="text-[10px] font-bold text-indigo-600">
                                                {{ detail.category.categoryname }}
                                            </span>
                                        </td>
                                        <td class="p-3.5">
                                            <div class="flex flex-wrap gap-1 text-[10px]">
                                                <span v-if="detail.ukuran?.nama" class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-medium">
                                                    Ukuran: {{ detail.ukuran.nama }}
                                                </span>
                                                <span v-if="detail.satuan?.nama || detail.uom?.nama_uom" class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-medium">
                                                    Satuan: {{ detail.satuan?.nama || detail.uom?.nama_uom }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 font-bold text-indigo-700 text-sm whitespace-nowrap">
                                            {{ rupiah(detail.barang?.harga_jual) }}
                                        </td>
                                        <td class="p-3.5 text-center whitespace-nowrap">
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md" :class="(detail.barang?.stok ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-500'">
                                                {{ detail.barang?.stok ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="p-3.5">
                                            <div class="flex items-center justify-center space-x-1.5">
                                                <button type="button" class="w-6 h-6 rounded bg-slate-100 border border-slate-200 text-slate-700 font-bold hover:bg-slate-200" @click="changeVariantQty(detail, -1)">-</button>
                                                <span class="w-8 text-center font-bold text-xs text-slate-900">{{ variantQty[detail.id_produk_detail] || 0 }}</span>
                                                <button type="button" class="w-6 h-6 rounded bg-slate-100 border border-slate-200 text-slate-700 font-bold hover:bg-slate-200" @click="changeVariantQty(detail, 1)">+</button>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-right whitespace-nowrap">
                                            <Button type="button" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold" @click="addSingleVariantToCart(detail)">
                                                + Keranjang
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
                        <Package class="w-16 h-16 mx-auto text-slate-300" />
                        <h3 class="font-bold text-slate-900 text-base">Varian Barang Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">Tidak ada varian produk yang sesuai dengan filter Anda.</p>
                        <Button type="button" class="bg-indigo-600 text-white rounded-xl text-xs font-bold" @click="resetSearch">
                            Reset Pencarian
                        </Button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="variantList?.links?.length" class="pt-4 flex justify-center gap-1.5 flex-wrap">
                        <Link
                            v-for="(link, idx) in variantList.links"
                            :key="idx"
                            :href="link.url ?? '#'"
                        >
                            <Button
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                class="rounded-xl text-xs font-bold"
                                v-html="link.label"
                            />
                        </Link>
                    </div>

                    </template>
                </main>
            </div>
        </div>

        <!-- Sticky Bottom Cart Action Bar -->
        <div v-if="totalSelectedQty > 0" class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-2xl px-6 py-4 flex items-center justify-between max-w-[1800px] mx-auto">
            <div class="text-xs sm:text-sm text-slate-600">
                Total Varian Dipilih: <span class="font-bold text-slate-900 text-base">{{ totalSelectedQty }}</span> pcs
            </div>

            <Button
                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 py-2.5 font-bold shadow-md"
                :disabled="bulkAddForm.processing"
                @click="addSelectedVariantsToCart"
            >
                <ShoppingCart class="w-4 h-4 mr-2" />
                {{ bulkAddForm.processing ? "Memproses..." : "Tambahkan ke Keranjang" }}
            </Button>
        </div>

        <!-- Quick View Modal for Variant -->
        <div v-if="quickViewVariant" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click="closeQuickView">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col border border-slate-200" @click.stop>
                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <span class="bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">
                        Detail Varian Produk
                    </span>
                    <button type="button" @click="closeQuickView" class="text-slate-400 hover:text-slate-900 p-1">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="aspect-square w-48 h-48 mx-auto bg-slate-50 rounded-2xl overflow-hidden border border-slate-200 flex items-center justify-center">
                        <img
                            v-if="quickViewVariant.gambars?.[quickViewImageIndex]"
                            :src="`/storage/${quickViewVariant.gambars[quickViewImageIndex].path_file}`"
                            class="w-full h-full object-cover"
                        />
                        <ImageOff v-else class="w-12 h-12 text-slate-300" />
                    </div>

                    <!-- Thumbnail Strip (hanya tampil kalau foto lebih dari 1) -->
                    <div v-if="quickViewVariant.gambars?.length > 1" class="flex items-center justify-center gap-2 flex-wrap">
                        <button
                            type="button"
                            v-for="(gambar, idx) in quickViewVariant.gambars"
                            :key="gambar.id_produk_gambar"
                            @click="quickViewImageIndex = idx"
                            class="w-12 h-12 rounded-lg overflow-hidden border-2 transition shrink-0"
                            :class="quickViewImageIndex === idx ? 'border-indigo-600' : 'border-slate-200 hover:border-indigo-300'"
                        >
                            <img :src="`/storage/${gambar.path_file}`" class="w-full h-full object-cover" />
                        </button>
                    </div>

                    <div class="space-y-2 text-center">
                        <h3 class="font-extrabold text-slate-900 text-base">
                            {{ variantLabel(quickViewVariant) }}
                        </h3>
                        <div class="text-indigo-700 font-black text-xl">
                            {{ rupiah(quickViewVariant.barang?.harga_jual) }}
                        </div>
                        <p class="text-xs text-slate-500">
                            Kode SKU: <span class="font-mono font-bold text-slate-800">{{ quickViewVariant.barang?.kode_barang || quickViewVariant.kode_barang }}</span>
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-700">Jumlah Pesanan Qty:</span>
                        <div class="flex items-center space-x-2 border border-slate-200 rounded-xl p-1 bg-slate-50">
                            <button type="button" @click="quickViewQty = Math.max(1, quickViewQty - 1)" class="w-8 h-8 bg-white rounded-lg border border-slate-200 flex items-center justify-center font-bold text-slate-700 font-bold">
                                -
                            </button>
                            <span class="text-sm font-extrabold px-3">{{ quickViewQty }}</span>
                            <button type="button" @click="quickViewQty++" class="w-8 h-8 bg-white rounded-lg border border-slate-200 flex items-center justify-center font-bold text-slate-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>

                    <Button type="button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-3 font-bold shadow-md" @click="addQuickViewToCart">
                        <ShoppingCart class="w-4 h-4 mr-2" />
                        + Masukkan Keranjang
                    </Button>
                </div>
            </div>
        </div>

        <!-- Slide-over Cart Drawer -->
        <div v-if="showCart" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex justify-end" @click="showCart = false">
            <div class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col" @click.stop>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="font-extrabold text-lg text-slate-900 flex items-center space-x-2">
                            <ShoppingCart class="w-5 h-5 text-indigo-600" />
                            <span>Keranjang Pengadaan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ totalCartQty }} item siap dibuat pesanan</p>
                    </div>
                    <button type="button" class="p-2 rounded-full hover:bg-slate-200 text-slate-500" @click="showCart = false">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-6">
                    
                    <!-- Helper Notice Box for Uploading Multiple Photos in Cart Drawer -->
                    <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 space-y-1.5 shadow-2xs">
                        <div class="flex items-center space-x-2 text-amber-900 font-extrabold text-xs">
                            <Info class="w-4 h-4 text-indigo-600 shrink-0" />
                            <span>💡 PETUNJUK FOTO SAMPEL BARANG:</span>
                        </div>
                        <p class="text-[11px] text-slate-700 leading-relaxed font-medium">
                            Khusus barang permintaan baru. Anda dapat mengunggah <b>satu atau beberapa foto sampel</b> untuk setiap item di keranjang ini. Klik tombol <b>"+ Upload Multiple Foto Sampel"</b> pada masing-masing barang di bawah ini.
                        </p>
                    </div>

                    <input
                        ref="cartItemFileInput"
                        type="file"
                        multiple
                        accept="image/*"
                        class="hidden"
                        @change="handleItemMultipleImageUpload"
                    />

                    <div v-if="!cartItems.length" class="text-center py-16 text-slate-400 space-y-2">
                        <ShoppingCart class="w-12 h-12 mx-auto text-slate-300" />
                        <p class="text-sm font-semibold">Keranjang pengadaan masih kosong.</p>
                    </div>

                    <div v-for="group in groupedCartItems" :key="group.key" class="space-y-3">
                        <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider flex items-center space-x-1.5 border-b border-slate-100 pb-1">
                            <Package class="w-3.5 h-3.5 text-indigo-600" />
                            <span>{{ group.label }}</span>
                        </h4>

                        <div v-for="item in group.items" :key="item.id_keranjang_detail" class="border border-slate-200 rounded-2xl p-3.5 space-y-2 bg-slate-50/50">
                            <div class="flex gap-3 items-start">
                                <div class="w-14 h-14 rounded-xl overflow-hidden border border-slate-200 bg-white flex items-center justify-center shrink-0">
                                    <img v-if="getCartImage(item)" :src="getCartImage(item)" class="w-full h-full object-cover" />
                                    <Package v-else class="w-6 h-6 text-slate-300" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h5 class="font-bold text-xs text-slate-900 truncate">{{ item.nama_barang }}</h5>
                                    <span class="text-[10px] font-semibold" :class="item.tipe_item === 'barang_baru' ? 'text-orange-600' : 'text-indigo-600'">
                                        {{ item.tipe_item === 'barang_baru' ? 'Permintaan Barang Baru' : 'Barang Terdaftar' }}
                                    </span>

                                    <div class="flex items-center space-x-2 mt-2">
                                        <button type="button" class="w-6 h-6 rounded-md bg-white border border-slate-300 flex items-center justify-center text-xs font-bold" @click="updateCartQty(item, Number(item.qty) - 1)">
                                            -
                                        </button>
                                        <span class="text-xs font-extrabold text-slate-900">{{ item.qty }}</span>
                                        <button type="button" class="w-6 h-6 rounded-md bg-white border border-slate-300 flex items-center justify-center text-xs font-bold" @click="updateCartQty(item, Number(item.qty) + 1)">
                                            +
                                        </button>
                                    </div>
                                </div>

                                <button type="button" class="text-slate-400 hover:text-red-600 p-1" @click="removeCartItem(item)">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Multiple Sample Photos Section for Cart Item -->
                            <div class="pt-2 border-t border-slate-200/60 mt-2">
                                <div v-if="item.gambar?.length" class="flex flex-wrap gap-1.5 mb-2">
                                    <div
                                        v-for="(img, idx) in item.gambar"
                                        :key="idx"
                                        class="w-10 h-10 rounded-lg overflow-hidden border border-slate-200 bg-white shadow-2xs relative"
                                    >
                                        <img :src="`/storage/${img.gambar}`" class="w-full h-full object-cover" />
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="triggerUploadItemGambar(item)"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 flex items-center space-x-1 hover:underline cursor-pointer"
                                >
                                    <UploadCloud class="w-3.5 h-3.5" />
                                    <span>+ Upload Multiple Foto Sampel ({{ item.gambar?.length || 0 }} foto)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-slate-100 bg-white">
                    <Button type="button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl h-12 font-bold shadow-md" :disabled="!cartItems.length" @click="pesanSekarang">
                        Buat Pesanan
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
