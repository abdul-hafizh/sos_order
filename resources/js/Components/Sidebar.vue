<script>
export default { inheritAttrs: false };
</script>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ChartPieIcon,
    ShoppingBagIcon,
    BuildingOffice2Icon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    Squares2X2Icon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Separator } from '@/Components/ui/separator'

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const closeMobile = () => emit('close');

const isCollapsed = ref(false);
const openGroups = ref([]);

const menuItems = [
    { name: 'Dashboard', route: 'dashboard', icon: ChartPieIcon, type: 'single' },

    { name: 'SPK', route: 'spk.index', icon: ShoppingBagIcon, type: 'single' },

    { name: 'Barang', route: 'barang.index', icon: ClipboardDocumentListIcon, type: 'single' },

    {
        name: 'Master Data',
        icon: Squares2X2Icon,
        type: 'group',
        children: [
            { name: 'Produk', route: 'master-produk.index' },
            { name: 'Type', route: 'master-tipe.index' },
            { name: 'Satuan', route: 'master-satuan.index' },
            { name: 'Berat', route: 'master-berat.index' },
            { name: 'Ukuran', route: 'master-ukuran.index' },
            { name: 'Warna', route: 'master-warna.index' },
            { name: 'Karakter', route: 'master-karakter.index' },
        ],
    },
];

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    if (isCollapsed.value) openGroups.value = [];
};

const toggleGroup = (groupName) => {
    if (isCollapsed.value) isCollapsed.value = false;
    const index = openGroups.value.indexOf(groupName);
    index > -1 ? openGroups.value.splice(index, 1) : openGroups.value.push(groupName);
};

const getRoute = (name) => (name ? route(name) : '#');
const isActive = (route_name) => route_name && route().current(route_name);

menuItems
    .filter((item) => item.type === 'group')
    .forEach((item) => {
        if (item.children.some((child) => isActive(child.route))) {
            openGroups.value.push(item.name);
        }
    });
</script>

<template>
    <aside
        class="hidden md:flex flex-col bg-white border-r border-slate-200 h-screen sticky top-0 transition-all duration-300 ease-in-out z-40"
        :class="[isCollapsed ? 'w-20' : 'w-60']">
        <div class="h-16 flex items-center px-6 border-b border-slate-100 shrink-0">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0 text-white font-bold">S
            </div>
            <span v-if="!isCollapsed" class="ml-3 font-semibold text-slate-900 tracking-tight">SOS ORDER</span>
        </div>

        <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
            <div v-for="item in menuItems" :key="item.name" class="space-y-1">

                <div v-if="item.type === 'single'">
                    <Link :href="getRoute(item.route)"
                        class="flex items-center p-2.5 rounded-md transition-colors duration-200"
                        :class="isActive(item.route) ? 'bg-slate-500 text-white font-medium' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'">
                        <component :is="item.icon" class="w-5 h-5 shrink-0" :class="isCollapsed ? 'mx-auto' : 'mr-3'" />
                        <span v-if="!isCollapsed" class="text-sm">{{ item.name }}</span>
                    </Link>
                </div>

                <div v-else-if="item.type === 'group'" class="space-y-1">
                    <button @click="toggleGroup(item.name)"
                        class="w-full flex items-center p-2.5 rounded-md transition-colors text-slate-600 hover:bg-slate-200/70 hover:text-slate-900">
                        <component :is="item.icon" class="w-5 h-5 shrink-0" :class="isCollapsed ? 'mx-auto' : 'mr-3'" />

                        <template v-if="!isCollapsed">
                            <span class="flex-grow text-left text-sm font-medium">{{ item.name }}</span>
                            <ChevronDownIcon class="w-4 h-4 transition-transform duration-300"
                                :class="{ 'rotate-180': openGroups.includes(item.name) }" />
                        </template>
                    </button>

                    <Transition name="slide">
                        <div v-if="openGroups.includes(item.name) && !isCollapsed" class="pl-9 space-y-0.5 mt-1">
                            <Link v-for="child in item.children" :key="child.name" :href="getRoute(child.route)"
                                class="block p-2 rounded-md text-sm transition-colors"
                                :class="isActive(child.route) ? 'bg-slate-500 text-white font-medium' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/70'">
                                {{ child.name }}
                            </Link>
                        </div>
                    </Transition>
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-slate-100">
            <button @click="toggleCollapse"
                class="w-full flex items-center justify-center p-2 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition-all">
                <component :is="isCollapsed ? ChevronRightIcon : ChevronLeftIcon" class="w-5 h-5" />
            </button>
        </div>
    </aside>

    <!-- Mobile drawer -->
    <Transition name="fade">
        <div v-if="show" class="md:hidden fixed inset-0 z-50 bg-black/50" @click="closeMobile"></div>
    </Transition>

    <Transition name="drawer">
        <aside v-if="show" class="md:hidden fixed inset-y-0 left-0 z-50 w-72 max-w-[80vw] bg-white flex flex-col shadow-2xl">
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0 text-white font-bold">P
                    </div>
                    <span class="ml-3 font-semibold text-slate-900 tracking-tight">Procsy</span>
                </div>
                <button @click="closeMobile" class="p-2 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-900">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
                <div v-for="item in menuItems" :key="item.name" class="space-y-1">

                    <div v-if="item.type === 'single'">
                        <Link :href="getRoute(item.route)" @click="closeMobile"
                            class="flex items-center p-2.5 rounded-md transition-colors duration-200"
                            :class="isActive(item.route) ? 'bg-slate-500 text-white font-medium' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'">
                            <component :is="item.icon" class="w-5 h-5 shrink-0 mr-3" />
                            <span class="text-sm">{{ item.name }}</span>
                        </Link>
                    </div>

                    <div v-else-if="item.type === 'group'" class="space-y-1">
                        <button @click="toggleGroup(item.name)"
                            class="w-full flex items-center p-2.5 rounded-md transition-colors text-slate-600 hover:bg-slate-200/70 hover:text-slate-900">
                            <component :is="item.icon" class="w-5 h-5 shrink-0 mr-3" />
                            <span class="flex-grow text-left text-sm font-medium">{{ item.name }}</span>
                            <ChevronDownIcon class="w-4 h-4 transition-transform duration-300"
                                :class="{ 'rotate-180': openGroups.includes(item.name) }" />
                        </button>

                        <Transition name="slide">
                            <div v-if="openGroups.includes(item.name)" class="pl-9 space-y-0.5 mt-1">
                                <Link v-for="child in item.children" :key="child.name" :href="getRoute(child.route)" @click="closeMobile"
                                    class="block p-2 rounded-md text-sm transition-colors"
                                    :class="isActive(child.route) ? 'bg-slate-500 text-white font-medium' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/70'">
                                    {{ child.name }}
                                </Link>
                            </div>
                        </Transition>
                    </div>
                </div>
            </nav>
        </aside>
    </Transition>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
}

.slide-enter-to,
.slide-leave-from {
    max-height: 500px;
    opacity: 1;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.drawer-enter-active,
.drawer-leave-active {
    transition: transform 0.25s ease;
}

.drawer-enter-from,
.drawer-leave-to {
    transform: translateX(-100%);
}
</style>