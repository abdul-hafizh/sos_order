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
    UserIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    ShieldCheckIcon,
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
    tipes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['toggleCart', 'openImageSearchModal', 'openLoginModal']);

const isHoUser = computed(() => !!props.user?.is_ho_user);
const currentCabangName = computed(() => props.user?.cabang?.cabang_nama || props.user?.kode_cabang);

const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category_code || '');

const categoryDropdownOpen = ref(false);
const mobileMenuOpen = ref(false);
const masterDropdownOpen = ref(false);

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

const selectCategoryQuick = (code) => {
    selectedCategory.value = code;
    categoryDropdownOpen.value = false;
    mobileMenuOpen.value = false;
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
        <div class="bg-slate-900 text-slate-300 text-[11px] sm:text-xs py-1 px-3 sm:px-4 border-b border-slate-800">
            <div class="max-w-[1800px] w-full mx-auto flex items-center justify-between font-medium gap-2">
                <div class="flex items-center space-x-2 truncate">
                    <span class="bg-indigo-600 text-white px-2 py-0.5 rounded text-[9px] sm:text-[10px] font-black uppercase tracking-wider shrink-0">
                        SOS ORDER
                    </span>
                    <span class="truncate text-slate-300">
                        Sistem Pengadaan Barang Cabang Internal GSOS
                    </span>
                </div>
                <div v-if="currentCabangName" class="flex items-center space-x-1 font-bold text-white shrink-0">
                    <MapPinIcon class="w-3.5 h-3.5 text-indigo-400" />
                    <span><span class="hidden sm:inline">Cabang: </span><span class="text-indigo-300 font-bold">{{ currentCabangName }}</span></span>
                </div>
            </div>
        </div>

        <!-- Main Navbar Header -->
        <div class="max-w-[1800px] w-full mx-auto px-3 sm:px-6 lg:px-8 py-2 md:py-2.5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-4">
                
                <!-- Top Row on Mobile: Brand Logo + Cart Button + Mobile Menu Toggle -->
                <div class="flex items-center justify-between w-full md:w-auto shrink-0">
                    <!-- Brand Logo -->
                    <Link :href="route('dashboard')" class="flex items-center space-x-2 group">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-lg sm:text-xl shadow-md group-hover:scale-105 transition duration-200">
                            S
                        </div>
                        <div class="flex flex-col">
                            <span class="font-black text-slate-900 tracking-tight text-xl sm:text-2xl leading-none">
                                SOS <span class="text-indigo-600">ORDER</span>
                            </span>
                            <span class="text-[8px] sm:text-[9px] text-indigo-600 font-extrabold tracking-widest uppercase mt-0.5">
                                Internal E-Procurement
                            </span>
                        </div>
                    </Link>

                    <!-- Mobile Top Action Buttons (Cart, Login/Profile, Hamburger) -->
                    <div class="flex items-center space-x-2 md:hidden">
                        <!-- Floating/Header Cart Trigger -->
                        <button
                            type="button"
                            @click="$emit('toggleCart')"
                            class="relative p-2 text-slate-700 hover:text-indigo-600 bg-slate-100 rounded-xl transition cursor-pointer flex items-center border border-slate-200"
                            title="Keranjang Pengadaan"
                        >
                            <ShoppingCartIcon class="w-5 h-5 text-indigo-600" />
                            <span
                                v-if="cartQty > 0"
                                class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[9px] font-black rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 border-2 border-white shadow-xs"
                            >
                                {{ cartQty }}
                            </span>
                        </button>

                        <!-- Mobile User Avatar / Login -->
                        <button
                            v-if="!user"
                            type="button"
                            @click="$emit('openLoginModal')"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs flex items-center shadow-2xs transition"
                        >
                            <span>Login</span>
                        </button>

                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200"
                        >
                            <Bars3Icon v-if="!mobileMenuOpen" class="w-5 h-5" />
                            <XMarkIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Central Search Bar with Prominent Photo AI Search Button -->
                <div class="flex-1 max-w-3xl min-w-0 w-full">
                    <form @submit.prevent="executeSearch" class="relative flex items-center bg-white border border-slate-300 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100 rounded-xl p-1 shadow-2xs transition-all gap-1">
                        <div class="relative flex-1 min-w-0 flex items-center">
                            <MagnifyingGlassIcon class="w-4 h-4 sm:w-5 sm:h-5 absolute left-2.5 sm:left-3 text-slate-400 pointer-events-none" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari varian, SKU, ATK..."
                                class="w-full pl-8 sm:pl-10 pr-2 h-9 text-xs sm:text-sm text-slate-900 placeholder-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent font-medium"
                            />
                        </div>

                        <!-- Prominent Standalone Photo Search UI Button -->
                        <button
                            type="button"
                            @click="$emit('openImageSearchModal')"
                            class="h-8 sm:h-9 px-2 sm:px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg transition-all cursor-pointer flex items-center space-x-1 sm:space-x-1.5 text-xs font-extrabold shrink-0 shadow-2xs"
                            title="Pencarian Foto AI (Cari Berdasarkan Gambar)"
                        >
                            <PhotoIcon class="w-4 h-4 text-indigo-600" />
                            <span class="text-[11px] sm:text-xs font-extrabold whitespace-nowrap">Cari Gambar</span>
                        </button>

                        <button
                            type="submit"
                            class="h-8 sm:h-9 px-3 sm:px-5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs flex items-center shrink-0 transition shadow-2xs cursor-pointer"
                        >
                            <span>Cari</span>
                        </button>
                    </form>
                </div>

                <!-- Right User Controls (Desktop Only) -->
                <div class="hidden md:flex items-center space-x-3 shrink-0">
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
                </div>
            </div>
        </div>

        <!-- Quick Category & Links Sub-Navbar Strip -->
        <div class="bg-slate-50 border-t border-slate-200/80 py-1.5 px-3 sm:px-6 lg:px-8">
            <div class="max-w-[1800px] w-full mx-auto flex items-center justify-between gap-4">
                
                <!-- Category Pills Scroll Strip -->
                <div class="flex items-center space-x-1.5 sm:space-x-2 overflow-x-auto no-scrollbar py-0.5 min-w-0">
                    <button
                        type="button"
                        @click="selectCategoryQuick('')"
                        class="px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold whitespace-nowrap transition shadow-2xs"
                        :class="!selectedCategory ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-200 border border-slate-200'"
                    >
                        🔥 Semua Kategori
                    </button>

                    <button
                        v-for="cat in categories"
                        :key="cat.code"
                        type="button"
                        @click="selectCategoryQuick(cat.code)"
                        class="px-3 py-1 rounded-full text-[11px] sm:text-xs font-semibold whitespace-nowrap transition"
                        :class="selectedCategory === cat.code ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'"
                    >
                        <span>{{ cat.name }}</span>
                    </button>
                </div>

                <!-- Admin Navigation Links (Desktop) -->
                <div class="hidden lg:flex items-center space-x-4 text-xs shrink-0 font-bold">
                    <Link
                        :href="route('dashboard')"
                        class="hover:text-indigo-600"
                        :class="isActive('dashboard') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        Katalog Barang
                    </Link>

                    <Link
                        v-if="user"
                        :href="route('spk.index')"
                        class="hover:text-indigo-600"
                        :class="isActive('spk.index') || route().current('spk.*') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        SPK Saya
                    </Link>

                    <Link
                        v-if="isHoUser"
                        :href="route('barang.index')"
                        class="hover:text-indigo-600"
                        :class="isActive('barang.index') || route().current('barang.*') ? 'text-indigo-600' : 'text-slate-600'"
                    >
                        Kelola Barang
                    </Link>

                    <Dropdown v-if="isHoUser" align="right" width="48">
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
                        Admin
                    </Link>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer Menu (When Hamburger is clicked) -->
        <div v-if="mobileMenuOpen" class="lg:hidden border-t border-slate-200 bg-white p-4 space-y-4 shadow-xl">
            <div v-if="user" class="p-3 bg-indigo-50 rounded-2xl flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-black text-sm">
                        {{ (user?.nama_user || 'U').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-xs sm:text-sm">{{ user?.nama_user }}</h4>
                        <span class="text-[10px] text-indigo-600 font-bold">Cabang: {{ currentCabangName }}</span>
                    </div>
                </div>

                <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold text-red-600 hover:underline">
                    Logout
                </Link>
            </div>

            <div v-else class="p-3 bg-slate-50 rounded-2xl flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Status: Pengunjung Guest</span>
                <button
                    type="button"
                    @click="$emit('openLoginModal'); mobileMenuOpen = false;"
                    class="px-3 py-1.5 bg-indigo-600 text-white font-bold text-xs rounded-xl"
                >
                    Masuk / Login
                </button>
            </div>

            <div class="space-y-1 text-xs font-bold text-slate-700">
                <Link
                    :href="route('dashboard')"
                    @click="mobileMenuOpen = false"
                    class="flex items-center space-x-2 p-2.5 rounded-xl hover:bg-slate-100"
                    :class="isActive('dashboard') ? 'bg-indigo-50 text-indigo-600 font-bold' : ''"
                >
                    <Squares2X2Icon class="w-4 h-4 text-indigo-600" />
                    <span>Katalog Pengadaan Barang</span>
                </Link>

                <Link
                    v-if="user"
                    :href="route('spk.index')"
                    @click="mobileMenuOpen = false"
                    class="flex items-center space-x-2 p-2.5 rounded-xl hover:bg-slate-100"
                    :class="isActive('spk.index') ? 'bg-indigo-50 text-indigo-600 font-bold' : ''"
                >
                    <ClipboardDocumentListIcon class="w-4 h-4 text-indigo-600" />
                    <span>SPK Saya</span>
                </Link>

                <Link
                    v-if="isHoUser"
                    :href="route('barang.index')"
                    @click="mobileMenuOpen = false"
                    class="flex items-center space-x-2 p-2.5 rounded-xl hover:bg-slate-100"
                >
                    <Cog6ToothIcon class="w-4 h-4 text-indigo-600" />
                    <span>Kelola Barang Admin</span>
                </Link>

                <Link
                    v-if="isHoUser"
                    :href="route('admin-ho.index')"
                    @click="mobileMenuOpen = false"
                    class="flex items-center space-x-2 p-2.5 rounded-xl hover:bg-slate-100"
                >
                    <ShieldCheckIcon class="w-4 h-4 text-indigo-600" />
                    <span>Admin</span>
                </Link>
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
