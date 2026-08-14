<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ChartPieIcon,
    ShoppingBagIcon,
    ClipboardDocumentListIcon,
    Squares2X2Icon,
    ShieldCheckIcon,
    ChevronDownIcon,
    Bars3Icon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    user: Object,
});

const isHoUser = computed(() => props.user?.kode_cabang === 'GSOS');
const isAdmin = computed(() => !!props.user?.is_admin);

const mobileMenuOpen = ref(false);
const masterDataMobileOpen = ref(false);

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
const isMasterDataActive = () => masterDataItems.some(item => route().current(item.route));
</script>

<template>
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-[1800px] w-full mx-auto px-3 sm:px-4 lg:px-6">
            <div class="flex justify-between h-14">
                
                <!-- Left Section: Logo & Desktop Navigation Links -->
                <div class="flex items-center space-x-8">
                    <!-- Logo -->
                    <Link :href="route('dashboard')" class="flex items-center space-x-3 shrink-0">
                        <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            S
                        </div>
                        <span class="font-bold text-slate-900 tracking-tight text-lg">SOS ORDER</span>
                    </Link>

                    <!-- Desktop Navigation Links -->
                    <div class="hidden md:flex items-center space-x-1">
                        <!-- Dashboard -->
                        <Link
                            :href="route('dashboard')"
                            class="px-3.5 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1.5"
                            :class="isActive('dashboard') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        >
                            <ChartPieIcon class="w-4 h-4" />
                            <span>Dashboard</span>
                        </Link>

                        <!-- SPK -->
                        <Link
                            :href="route('spk.index')"
                            class="px-3.5 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1.5"
                            :class="isActive('spk.index') || route().current('spk.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        >
                            <ShoppingBagIcon class="w-4 h-4" />
                            <span>SPK</span>
                        </Link>

                        <!-- Barang -->
                        <Link
                            v-if="isAdmin"
                            :href="route('barang.index')"
                            class="px-3.5 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1.5"
                            :class="isActive('barang.index') || route().current('barang.*') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        >
                            <ClipboardDocumentListIcon class="w-4 h-4" />
                            <span>Barang</span>
                        </Link>

                        <!-- Master Data Dropdown -->
                        <Dropdown v-if="isAdmin" align="left" width="48">
                            <template #trigger>
                                <button
                                    class="px-3.5 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1.5 cursor-pointer"
                                    :class="isMasterDataActive() ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                                >
                                    <Squares2X2Icon class="w-4 h-4" />
                                    <span>Master Data</span>
                                    <ChevronDownIcon class="w-3.5 h-3.5" />
                                </button>
                            </template>

                            <template #content>
                                <div class="py-1">
                                    <DropdownLink
                                        v-for="child in masterDataItems"
                                        :key="child.name"
                                        :href="getRoute(child.route)"
                                        :class="isActive(child.route) ? 'bg-slate-100 font-semibold text-blue-600' : ''"
                                    >
                                        {{ child.name }}
                                    </DropdownLink>
                                </div>
                            </template>
                        </Dropdown>

                        <!-- Admin GSOS -->
                        <Link
                            v-if="isHoUser"
                            :href="route('admin-ho.index')"
                            class="px-3.5 py-2 rounded-md text-sm font-medium transition-colors flex items-center space-x-1.5"
                            :class="isActive('admin-ho.index') ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        >
                            <ShieldCheckIcon class="w-4 h-4" />
                            <span>Admin GSOS</span>
                        </Link>
                    </div>
                </div>

                <!-- Right Section: User Profile & Mobile Menu Toggle -->
                <div class="flex items-center space-x-3">
                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center space-x-2.5 text-sm font-semibold text-slate-700 hover:text-blue-600 transition px-3 py-1.5 rounded-md hover:bg-slate-100 cursor-pointer">
                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                        {{ (user?.nama_user || 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="hidden sm:inline">{{ user?.nama_user }}</span>
                                    <ChevronDownIcon class="w-4 h-4 text-slate-400" />
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none"
                    >
                        <Bars3Icon v-if="!mobileMenuOpen" class="w-6 h-6" />
                        <XMarkIcon v-else class="w-6 h-6" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <Transition name="fade">
            <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
                <Link
                    :href="route('dashboard')"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-md text-base font-medium"
                    :class="isActive('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-700 hover:bg-slate-100'"
                >
                    Dashboard
                </Link>

                <Link
                    :href="route('spk.index')"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-md text-base font-medium"
                    :class="isActive('spk.index') || route().current('spk.*') ? 'bg-slate-800 text-white' : 'text-slate-700 hover:bg-slate-100'"
                >
                    SPK
                </Link>

                <Link
                    v-if="isAdmin"
                    :href="route('barang.index')"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-md text-base font-medium"
                    :class="isActive('barang.index') || route().current('barang.*') ? 'bg-slate-800 text-white' : 'text-slate-700 hover:bg-slate-100'"
                >
                    Barang
                </Link>

                <!-- Mobile Master Data Group -->
                <div v-if="isAdmin">
                    <button
                        @click="masterDataMobileOpen = !masterDataMobileOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-slate-100"
                    >
                        <span>Master Data</span>
                        <ChevronDownIcon class="w-4 h-4 transition-transform" :class="{ 'rotate-180': masterDataMobileOpen }" />
                    </button>

                    <div v-if="masterDataMobileOpen" class="pl-4 space-y-1 mt-1 border-l-2 border-slate-200 ml-2">
                        <Link
                            v-for="child in masterDataItems"
                            :key="child.name"
                            :href="getRoute(child.route)"
                            @click="mobileMenuOpen = false"
                            class="block px-3 py-1.5 rounded-md text-sm font-medium"
                            :class="isActive(child.route) ? 'bg-slate-500 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        >
                            {{ child.name }}
                        </Link>
                    </div>
                </div>

                <Link
                    v-if="isHoUser"
                    :href="route('admin-ho.index')"
                    @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-md text-base font-medium"
                    :class="isActive('admin-ho.index') ? 'bg-slate-800 text-white' : 'text-slate-700 hover:bg-slate-100'"
                >
                    Admin GSOS
                </Link>
            </div>
        </Transition>
    </nav>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
