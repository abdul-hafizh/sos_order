<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    Search,
    ImagePlus,
    Package,
    Layers,
    UploadCloud,
    X,
} from 'lucide-vue-next';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    barangs: Object,
    filters: Object,
    image_keyword: String,
});

const previewImage = ref(null);

const hasResult = computed(() => props.barangs?.data?.length > 0);
const hasSearch = computed(() => params.value.search || props.image_keyword);

const resetSearch = () => {
    previewImage.value = null;
    imageForm.reset();
    params.value.search = '';

    router.get(route('dashboard'), {}, {
        preserveState: false,
        replace: true,
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

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Hasil Pencarian Barang</h3>
                    <p class="text-sm text-gray-400">
                        Menampilkan {{ barangs?.data?.length || 0 }} barang.
                    </p>
                </div>

                <Button
                    v-if="hasSearch"
                    type="button"
                    variant="outline"
                    class="rounded-2xl"
                    @click="resetSearch"
                >
                    Reset
                </Button>
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
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-3xl border border-red-100 shadow-sm p-12 text-center">
                <Package class="w-16 h-16 mx-auto text-red-300 mb-4" />
                <h3 class="font-bold text-red-700">Barang tidak ditemukan</h3>

                <p class="text-sm text-gray-500 mt-2">
                    Barang yang Anda cari tidak tersedia di database.
                </p>

                <p v-if="image_keyword" class="text-sm text-gray-400 mt-2">
                    Keyword dari gambar:
                    <span class="font-semibold">{{ image_keyword }}</span>
                </p>

                <Button
                    type="button"
                    class="mt-5 bg-blue-700 text-white rounded-2xl"
                    @click="resetSearch"
                >
                    Reset
                </Button>
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
    </AuthenticatedLayout>
</template>