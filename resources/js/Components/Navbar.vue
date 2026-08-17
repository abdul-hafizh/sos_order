<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDownIcon,
    Bars3Icon,
    XMarkIcon,
    MagnifyingGlassIcon,
    PhotoIcon,
    ShoppingCartIcon,
    Squares2X2Icon,
    MapPinIcon,
} from '@heroicons/vue/24/outline';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    user: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    cartQty: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['toggleCart', 'openImageSearch', 'openLoginModal']);

const isHoUser = computed(() => props.user?.kode_cabang === 'GSOS');
const isAdmin = computed(() => !!props.user?.is_admin);
const currentCabangName = computed(() => props.user?.cabang?.cabang_nama || props.user?.kode_cabang);

const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category_code || '');

const categoryDropdownOpen = ref(false);
const mobileMenuOpen = ref(false);

const masterDataItems = [
    { name: 'Produk', route: 'master-produk.index' },
    { name: 'Produk Detail', route: 'master-produk-detail.index' },
    { name: 'Kategori', route: 'master-kategori.index' },
    { name: 'Type', route: 'master-tipe.index' },
    { name: 'Satuan', route: 'master-satuan.index' },
    { name: 'Berat', route: 'master-berat.index' },
    { name: 'Ukuran', route: 'master-ukuran.index' },
    { name: 'Warna', route: 'master-warna.index' },
    { name: 'Karakter', route: 'master-karakter.index' },
    { name: 'UOM', route: 'master-uom.index' },
    { name: 'PPN', route: 'master-ppn.index' },
];

const trendingSearches = ['Tumbler Vacuum', 'Kertas HVS A4', 'Pulpen Gel', 'Disinfektan', 'Mouse Wireless', 'Seragam Polo'];

const getRoute = (name) => (name ? route(name) : '#');
const isActive = (routeName) => routeName && route().current(routeName);

const executeSearch = () => {
    router.get(
        route('dashboard'),
        {
            search: searchQuery.value,
            category_code: selectedCategory.value,
        },
        { preserveState: true, replace: true }
    );
};

const selectTrending = (term) => {
    searchQuery.value = term;
    executeSearch();
};

const selectCategoryQuick = (code) => {
    selectedCategory.value = code;
    categoryDropdownOpen.value = false;
    router.get(
        route('dashboard'),
        {
            search: searchQuery.value,
            category_code: code,
        },
        { preserveState: true, replace: true }
    );
};
</script>

<template>
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs font-sans">
        <!-- Top Corporate Info Bar -->
        <div class="bg-slate-900 text-slate-300 text-xs py-1.5 px-4 border-b border-slate-800">
            <div class="max-w-[1800px] w-full mx-auto flex items-center justify-between font-medium">
                <div class="flex items-center space-x-3">
                    <span class="bg-indigo-600 text-white px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                        SOS ORDER
                    </span>
                    <span class="hidden sm:inline text-slate-300">
                        Sistem Pengadaan Barang Cabang — Terhubung Langsung Dokumen SPK Gudang Pusat GSOS
                    </span>
                </div>
                <div v-if="currentCabangName" class="flex items-center space-x-1.5 font-bold text-white shrink-0">
                    <MapPinIcon class="w-3.5 h-3.5 text-indigo-400" />
                    <span>Cabang: <span class="text-indigo-300 font-bold">{{ currentCabangName }}</span></span>
                </div>
            </div>
        </div>

        <!-- Main Navbar Header -->
        <div class="max-w-[1800px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
            <div class="flex items-center justify-between gap-4">
                
                <!-- 1. Brand Logo (SOS ORDER) -->
                <Link :href="route('dashboard')" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="w-10 h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md group-hover:scale-105 transition duration-200">
                        S
                    </div>
                    <div class="flex flex-col">
                        <span class="font-black text-slate-900 tracking-tight text-2xl leading-none">
                            SOS <span class="text-indigo-600">ORDER</span>
                        </span>
                        <span class="text-[9px] text-indigo-600 font-extrabold tracking-widest uppercase mt-0.5">
                            Internal E-Procurement
                        </span>
                    </div>
                </Link>

                <!-- 2. Kategori Mega Menu Dropdown -->
                <div class="relative hidden lg:block shrink-0">
                    <button
                        type="button"
                        @click="categoryDropdownOpen = !categoryDropdownOpen"
                        class="flex items-center space-x-1.5 px-3 py-2 rounded-xl text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 text-xs font-bold transition bg-slate-50 border border-slate-200 cursor-pointer shadow-2xs"
                    >
                        <Squares2X2Icon class="w-4 h-4 text-indigo-600" />
                        <span>Kategori Barang</span>
                        <ChevronDownIcon class="w-3.5 h-3.5 opacity-70" :class="{ 'rotate-180': categoryDropdownOpen }" />
                    </button>

                    <!-- Mega Dropdown Panel -->
                    <div v-if="categoryDropdownOpen" class="absolute left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 animate-in fade-in slide-in-from-top-2">
                        <div class="px-3 py-2 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            Pilih Kategori Barang
                        </div>
                        <button
                            type="button"
                            @click="selectCategoryQuick('')"
                            class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between hover:bg-indigo-50 hover:text-indigo-600 transition"
                            :class="!selectedCategory ? 'bg-indigo-50 text-indigo-600' : 'text-slate-700'"
                        >
                            <span>🔥 Semua Kategori</span>
                            <span class="text-[10px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-full">All</span>
                        </button>
                        <hr class="my-1 border-slate-100" />
                        <button
                            v-for="cat in categories"
                            :key="cat.code"
                            type="button"
                            @click="selectCategoryQuick(cat.code)"
                            class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold flex items-center justify-between hover:bg-indigo-50 hover:text-indigo-600 transition"
                            :class="selectedCategory === cat.code ? 'bg-indigo-50 text-indigo-600 font-bold' : 'text-slate-600'"
                        >
                            <span class="truncate">{{ cat.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Central Search Bar -->
                <div class="flex-1 max-w-3xl min-w-0 flex flex-col justify-center">
                    <form @submit.prevent="executeSearch" class="relative flex items-center bg-white border border-slate-300 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 rounded-xl p-0.5 shadow-2xs transition-all">
                        <div class="relative w-full flex items-center">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 text-slate-400 pointer-events-none" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari varian barang, SKU, perabotan, ATK..."
                                class="w-full pl-10 pr-4 h-9 text-sm text-slate-900 placeholder-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent font-medium"
                            />
                        </div>
                        <button
                            type="submit"
                            class="ml-1 h-9 px-5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs flex items-center shrink-0 transition shadow-2xs"
                        >
                            <span>Cari</span>
                        </button>
                    </form>

                    <!-- Trending Searches Strip -->
                    <div class="hidden sm:flex items-center space-x-3 text-[11px] text-slate-500 mt-1 pl-1 overflow-x-auto no-scrollbar">
                        <span
                            v-for="(word, idx) in trendingSearches"
                            :key="idx"
                            @click="selectTrending(word)"
                            class="hover:text-indigo-600 cursor-pointer whitespace-nowrap font-medium"
                        >
                            {{ word }}
                        </span>
                    </div>
                </div>

                <!-- 4. Right User Controls -->
                <div class="flex items-center space-x-3 shrink-0">
                    <!-- Cart Button with Counter Badge -->
                    <button
                        type="button"
                        @click="$emit('toggleCart')"
                        class="relative p-2.5 text-slate-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition cursor-pointer flex items-center shadow-2xs border border-slate-200"
                        title="Keranjang Pengadaan"
                    >
                        <ShoppingCartIcon class="w-6 h-6 text-indigo-600" />
                        <span
                            v-if="cartQty > 0"
                            class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 border-2 border-white shadow-xs"
                        >
                            {{ cartQty }}
                        </span>
                    </button>

                    <!-- User Profile Dropdown / Login Button for Guest -->
                    <div class="relative border-l border-slate-200 pl-3">
                        <Dropdown v-if="user" align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center space-x-2 text-xs font-bold text-slate-700 hover:text-indigo-600 transition px-2 py-1 rounded-xl hover:bg-slate-100 cursor-pointer">
                                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-2xs">
                                        {{ (user?.nama_user || 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="hidden md:inline font-bold">{{ user?.nama_user }}</span>
                                    <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400" />
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile Saya</DropdownLink>
                                <DropdownLink :href="route('spk.index')">SPK Saya</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600">Log Out</DropdownLink>
                            </template>
                        </Dropdown>

                        <button
                            v-else
                            type="button"
                            @click="$emit('openLoginModal')"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs flex items-center space-x-1 shadow-2xs transition cursor-pointer"
                        >
                            <span>Masuk / Login</span>
                        </button>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                    >
                        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
                        <XMarkIcon v-else class="w-6 h-6" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Category Sub-Navbar Strip -->
        <div class="bg-slate-50 border-t border-slate-200/80 py-1.5 px-4 sm:px-6 lg:px-8">
            <div class="max-w-[1800px] w-full mx-auto flex items-center justify-between gap-4">
                
                <!-- Category Pills Scroll Strip -->
                <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar py-0.5 min-w-0">
                    <button
                        type="button"
                        @click="selectCategoryQuick('')"
                        class="px-3.5 py-1 rounded-full text-xs font-bold whitespace-nowrap transition shadow-2xs"
                        :class="!selectedCategory ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-200 border border-slate-200'"
                    >
                        🔥 Semua Kategori
                    </button>

                    <button
                        v-for="cat in categories"
                        :key="cat.code"
                        type="button"
                        @click="selectCategoryQuick(cat.code)"
                        class="px-3.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap transition"
                        :class="selectedCategory === cat.code ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'"
                    >
                        <span>{{ cat.name }}</span>
                    </button>
                </div>

                <!-- Admin Navigation Links -->
                <div class="hidden lg:flex items-center space-x-4 text-xs shrink-0 font-bold">
                    <Link
                        :href="route('dashboard')"
                        class="hover:text-indigo-600"
                        :class="isActive('dashboard') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        Katalog Barang
                    </Link>

                    <Link
                        :href="route('spk.index')"
                        class="hover:text-indigo-600"
                        :class="isActive('spk.index') || route().current('spk.*') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        SPK Saya
                    </Link>

                    <Link
                        v-if="isAdmin"
                        :href="route('barang.index')"
                        class="hover:text-indigo-600"
                        :class="isActive('barang.index') || route().current('barang.*') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        Kelola Barang
                    </Link>

                    <Dropdown v-if="isAdmin" align="right" width="48">
                        <template #trigger>
                            <button class="hover:text-indigo-600 flex items-center space-x-1 text-slate-600 cursor-pointer">
                                <span>Master Data</span>
                                <ChevronDownIcon class="w-3 h-3" />
                            </button>
                        </template>
                        <template #content>
                            <div class="py-1">
                                <DropdownLink
                                    v-for="child in masterDataItems"
                                    :key="child.name"
                                    :href="getRoute(child.route)"
                                    :class="isActive(child.route) ? 'bg-indigo-50 font-bold text-indigo-600' : ''"
                                >
                                    {{ child.name }}
                                </DropdownLink>
                            </div>
                        </template>
                    </Dropdown>

                    <Link
                        v-if="isHoUser"
                        :href="route('admin-ho.index')"
                        class="hover:text-indigo-600 text-slate-600"
                    >
                        Admin GSOS
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
