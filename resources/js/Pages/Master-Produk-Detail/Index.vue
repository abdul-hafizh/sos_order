<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';
import SearchSelect from '@/Components/SearchSelect.vue';
import {
    Boxes,
    Pencil,
    Trash,
    Search,
    ImagePlus,
    Upload,
    X,
    Loader2,
} from 'lucide-vue-next';

const props = defineProps({
    produkDetail: Object,
    filters: Object,
    list_produk: Array,
    list_kategori: Array,
    list_tipe: Array,
    list_satuan: Array,
    list_berat: Array,
    list_ukuran: Array,
    list_warna: Array,
    list_karakter: Array,
    list_uom: Array,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingProdukDetail = ref(null);
const produkDetailToDelete = ref(null);
const isSubmitting = ref(false);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

watch(params, () => {
    router.get(route('master-produk-detail.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const form = useForm({
    id_produk: null,
    category_id: null,
    kode_barang: null,
    id_tipe: null,
    id_satuan: null,
    id_berat: null,
    id_ukuran: null,
    id_warna: null,
    id_karakter: null,
    id_uom: null,
    foto: [],
    deleted_gambar_ids: [],
});

const previews = ref([]);

const handleFoto = (event) => {
    const files = Array.from(event.target.files || []);

    files.forEach((file) => {
        form.foto.push(file);
        previews.value.push({ type: 'new', file, url: URL.createObjectURL(file) });
    });

    event.target.value = '';
};

const removePreview = (index) => {
    const preview = previews.value[index];

    if (preview.type === 'old') {
        form.deleted_gambar_ids.push(preview.id_produk_gambar);
    }

    if (preview.type === 'new') {
        const fileIndex = form.foto.findIndex((file) => file === preview.file);

        if (fileIndex !== -1) {
            form.foto.splice(fileIndex, 1);
        }

        URL.revokeObjectURL(preview.url);
    }

    previews.value.splice(index, 1);
};

// --- Kode barang combobox (async search) ---
const barangOptions = ref([]);
const barangKeyword = ref('');
const showBarangDropdown = ref(false);
const selectedBarang = ref(null);
const barangWrapper = ref(null);
let barangSearchTimer = null;

const searchBarangOptions = async () => {
    const res = await axios.get(route('master-produk-detail.barang-options'), {
        params: {
            search: barangKeyword.value,
            current: editingProdukDetail.value?.kode_barang || null,
        },
    });

    barangOptions.value = res.data;
};

watch(barangKeyword, () => {
    clearTimeout(barangSearchTimer);
    barangSearchTimer = setTimeout(searchBarangOptions, 300);
});

const openBarangDropdown = () => {
    showBarangDropdown.value = true;
    searchBarangOptions();
};

const chooseBarang = (item) => {
    form.kode_barang = item.kode_barang;
    selectedBarang.value = item;
    barangKeyword.value = '';
    showBarangDropdown.value = false;
};

const clearBarang = () => {
    form.kode_barang = null;
    selectedBarang.value = null;
};

const closeBarangDropdownOutside = (e) => {
    if (!barangWrapper.value?.contains(e.target)) {
        showBarangDropdown.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeBarangDropdownOutside));
onBeforeUnmount(() => document.removeEventListener('click', closeBarangDropdownOutside));

const openModal = (item = null) => {
    editingProdukDetail.value = item;
    form.clearErrors();

    if (item) {
        form.id_produk = item.id_produk;
        form.category_id = item.category_id;
        form.kode_barang = item.kode_barang;
        form.id_tipe = item.id_tipe;
        form.id_satuan = item.id_satuan;
        form.id_berat = item.id_berat;
        form.id_ukuran = item.id_ukuran;
        form.id_warna = item.id_warna;
        form.id_karakter = item.id_karakter;
        form.id_uom = item.id_uom;
        form.foto = [];
        form.deleted_gambar_ids = [];

        previews.value = (item.gambars || []).map((g) => ({
            type: 'old',
            id_produk_gambar: g.id_produk_gambar,
            url: `/storage/${g.path_file}`,
        }));

        selectedBarang.value = item.barang
            ? { kode_barang: item.barang.kode_barang, nama_barang: item.barang.nama_barang }
            : (item.kode_barang ? { kode_barang: item.kode_barang, nama_barang: '' } : null);
    } else {
        form.reset();
        previews.value = [];
        selectedBarang.value = null;
    }

    barangKeyword.value = '';
    showBarangDropdown.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingProdukDetail.value = null;
    previews.value.forEach((p) => p.type === 'new' && URL.revokeObjectURL(p.url));
    previews.value = [];
    form.reset();
};

const submit = () => {
    isSubmitting.value = true;

    if (editingProdukDetail.value) {
        router.post(
            route('master-produk-detail.update', editingProdukDetail.value.id_produk_detail),
            { ...form.data(), _method: 'put' },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeModal(),
                onFinish: () => { isSubmitting.value = false; },
            }
        );
    } else {
        form.post(route('master-produk-detail.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => { isSubmitting.value = false; },
        });
    }
};

const confirmDelete = (item) => {
    produkDetailToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyProdukDetail = () => {
    router.delete(route('master-produk-detail.destroy', produkDetailToDelete.value.id_produk_detail), {
        onSuccess: () => {
            showDeleteModal.value = false;
            produkDetailToDelete.value = null;
        },
    });
};

// --- Sinkron ke m_item: tombol hanya aktif kalau atribut produk detail
// (kode barang, type, satuan, UOM) dan seluruh harga di t_barang sudah terisi.
const isReadyForMItem = (item) => {
    const hasDetail = !!(item.kode_barang && item.id_tipe && item.id_satuan && item.id_uom);

    const barang = item.barang;
    const hargaFields = [
        barang?.harga_beli_before,
        barang?.harga_beli,
        barang?.harga_jual_before,
        barang?.harga_jual,
        barang?.harga_jual_jumbo,
        barang?.harga_jual_jumbo_before,
    ];
    const hasHarga = !!barang && hargaFields.every((v) => v !== null && v !== undefined && Number(v) > 0);

    return hasDetail && hasHarga;
};

const syncMItem = (item) => {
    if (!isReadyForMItem(item)) return;

    if (!confirm(`Sinkronkan "${item.kode_barang}" ke tabel m_item sekarang?`)) {
        return;
    }

    router.post(route('master-produk-detail.sync-m-item', item.id_produk_detail), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Produk Detail" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">Produk Detail</label>
                <p class="text-sm text-gray-400">Master Data | Produk Detail</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Boxes class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data Produk Detail</ItemTitle>
                        <ItemDescription class="text-sm">Kelola detail produk (atribut, UOM, foto) yang terhubung ke master produk dan kode barang.</ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" @click="openModal()">+ Tambah Produk Detail</Button>
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <div class="relative w-full max-w-xs">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
                    <Input v-model="params.search" placeholder="Cari produk / kode barang..." class="pl-9 border-gray-300 rounded-md" />
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block rounded-md border bg-white overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="p-3 w-20">Foto</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3">Barang Terhubung</th>
                            <th class="p-3">Atribut</th>
                            <th class="p-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in produkDetail.data" :key="item.id_produk_detail" class="border-t align-top">
                            <td class="p-3">
                                <div class="relative w-14 h-14 rounded-md overflow-hidden bg-slate-100 border">
                                    <img v-if="item.gambars?.length" :src="`/storage/${item.gambars[0].path_file}`" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                        <Boxes class="w-6 h-6" />
                                    </div>
                                    <span v-if="item.gambars?.length > 1" class="absolute bottom-0 right-0 bg-black/60 text-white text-[10px] px-1 rounded-tl">
                                        +{{ item.gambars.length - 1 }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="font-medium">{{ item.produk?.nama_produk || '-' }}</div>
                                <div class="text-xs text-gray-500 mt-1 line-clamp-2">{{ item.produk?.deskripsi || '-' }}</div>
                            </td>
                            <td class="p-3">
                                <div v-if="item.kode_barang" class="text-xs">
                                    <div class="font-semibold">{{ item.kode_barang }}</div>
                                    <div class="text-gray-500">{{ item.barang?.nama_barang || '-' }}</div>
                                </div>
                                <span v-else class="text-xs text-gray-400">Belum terhubung</span>
                            </td>
                            <td class="p-3">
                                <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600">
                                    <div><span class="text-gray-400">Kategori:</span> {{ item.category?.categoryname || '-' }}</div>
                                    <div><span class="text-gray-400">Type:</span> {{ item.tipe?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">Satuan:</span> {{ item.satuan?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">Berat:</span> {{ item.berat?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">Ukuran:</span> {{ item.ukuran?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">Warna:</span> {{ item.warna?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">Karakter:</span> {{ item.karakter?.nama || '-' }}</div>
                                    <div><span class="text-gray-400">UOM:</span> {{ item.uom?.nama_uom || '-' }}</div>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2 justify-center">
                                    <Button
                                        variant="ghost"
                                        size="xs"
                                        class="bg-green-500 text-white rounded-md disabled:opacity-40 disabled:cursor-not-allowed"
                                        :disabled="!isReadyForMItem(item)"
                                        :title="isReadyForMItem(item) ? 'Sinkron ke m_item' : 'Lengkapi kode barang, type, satuan, UOM, dan seluruh harga terlebih dahulu'"
                                        @click="syncMItem(item)"
                                    ><Upload class="w-4 h-4" /></Button>
                                    <Button variant="ghost" size="xs" class="bg-blue-500 text-white rounded-md" @click="openModal(item)"><Pencil class="w-4 h-4" /></Button>
                                    <Button variant="ghost" size="xs" class="bg-red-500 text-white rounded-md" @click="confirmDelete(item)"><Trash class="w-4 h-4" /></Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="produkDetail.data.length === 0">
                            <td colspan="5" class="p-8 text-center text-gray-400">Belum ada produk detail.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden space-y-3">
                <div v-for="item in produkDetail.data" :key="item.id_produk_detail" class="border rounded-lg p-3 bg-white shadow-sm">
                    <div class="flex gap-3">
                        <div class="relative w-16 h-16 rounded-md overflow-hidden bg-slate-100 border shrink-0">
                            <img v-if="item.gambars?.length" :src="`/storage/${item.gambars[0].path_file}`" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <Boxes class="w-6 h-6" />
                            </div>
                            <span v-if="item.gambars?.length > 1" class="absolute bottom-0 right-0 bg-black/60 text-white text-[10px] px-1 rounded-tl">
                                +{{ item.gambars.length - 1 }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ item.produk?.nama_produk || '-' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <span v-if="item.kode_barang">{{ item.kode_barang }} - {{ item.barang?.nama_barang || '-' }}</span>
                                <span v-else class="text-gray-400">Belum terhubung</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 line-clamp-2">{{ item.produk?.deskripsi || '-' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600 mt-3 border-t pt-2">
                        <div><span class="text-gray-400">Kategori:</span> {{ item.category?.categoryname || '-' }}</div>
                        <div><span class="text-gray-400">Type:</span> {{ item.tipe?.nama || '-' }}</div>
                        <div><span class="text-gray-400">Satuan:</span> {{ item.satuan?.nama || '-' }}</div>
                        <div><span class="text-gray-400">Berat:</span> {{ item.berat?.nama || '-' }}</div>
                        <div><span class="text-gray-400">Ukuran:</span> {{ item.ukuran?.nama || '-' }}</div>
                        <div><span class="text-gray-400">Warna:</span> {{ item.warna?.nama || '-' }}</div>
                        <div><span class="text-gray-400">Karakter:</span> {{ item.karakter?.nama || '-' }}</div>
                        <div><span class="text-gray-400">UOM:</span> {{ item.uom?.nama_uom || '-' }}</div>
                    </div>

                    <div class="flex gap-2 justify-end mt-3">
                        <Button
                            variant="ghost"
                            size="xs"
                            class="bg-green-500 text-white rounded-md disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!isReadyForMItem(item)"
                            :title="isReadyForMItem(item) ? 'Sinkron ke m_item' : 'Lengkapi kode barang, type, satuan, UOM, dan seluruh harga terlebih dahulu'"
                            @click="syncMItem(item)"
                        ><Upload class="w-4 h-4" /></Button>
                        <Button variant="ghost" size="xs" class="bg-blue-500 text-white rounded-md" @click="openModal(item)"><Pencil class="w-4 h-4" /></Button>
                        <Button variant="ghost" size="xs" class="bg-red-500 text-white rounded-md" @click="confirmDelete(item)"><Trash class="w-4 h-4" /></Button>
                    </div>
                </div>

                <div v-if="produkDetail.data.length === 0" class="p-8 text-center text-gray-400 border rounded-lg bg-white">
                    Belum ada produk detail.
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center">
                <Link v-for="(link, index) in produkDetail.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <!-- Form modal -->
            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-2xl shadow-xl max-h-[90vh] overflow-y-auto">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">{{ editingProdukDetail ? 'Edit Produk Detail' : 'Tambah Produk Detail' }}</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="text-xs text-gray-400 font-medium">Produk</label>
                            <SearchSelect v-model="form.id_produk" :options="list_produk" value-key="id_produk" label-key="nama_produk" placeholder="Pilih Produk..." />
                            <p v-if="form.errors.id_produk" class="text-sm text-red-500 mt-1">{{ form.errors.id_produk }}</p>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 font-medium">Kategori</label>
                            <SearchSelect v-model="form.category_id" :options="list_kategori" value-key="categorycode" label-key="categoryname" placeholder="Pilih Kategori..." />
                            <p v-if="form.errors.category_id" class="text-sm text-red-500 mt-1">{{ form.errors.category_id }}</p>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 font-medium">Kode Barang (t_barang)</label>
                            <div class="relative" ref="barangWrapper">
                                <div v-if="selectedBarang" class="flex items-center justify-between border rounded-md px-3 py-2 bg-slate-50">
                                    <div class="text-sm">
                                        <span class="font-semibold">{{ selectedBarang.kode_barang }}</span>
                                        <span v-if="selectedBarang.nama_barang" class="text-gray-500"> - {{ selectedBarang.nama_barang }}</span>
                                    </div>
                                    <button type="button" @click="clearBarang" class="text-gray-400 hover:text-red-500">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <template v-else>
                                    <div class="relative">
                                        <Search class="absolute left-2 top-2.5 w-4 h-4 text-gray-400" />
                                        <input
                                            v-model="barangKeyword"
                                            @focus="openBarangDropdown"
                                            placeholder="Cari nama / kode barang..."
                                            class="w-full border rounded-md pl-8 pr-2 py-2 text-sm"
                                        />
                                    </div>

                                    <div v-if="showBarangDropdown" class="absolute left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg z-50 max-h-56 overflow-y-auto">
                                        <div v-if="barangOptions.length === 0" class="text-center text-gray-500 py-4 text-sm">
                                            Tidak ada barang tersedia
                                        </div>
                                        <button
                                            v-for="opt in barangOptions"
                                            :key="opt.id_barang"
                                            type="button"
                                            @click="chooseBarang(opt)"
                                            class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm"
                                        >
                                            <span class="font-medium">{{ opt.kode_barang }}</span>
                                            <span class="text-gray-500"> - {{ opt.nama_barang }}</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Hanya barang yang belum terhubung ke produk detail lain yang muncul di daftar.</p>
                            <p v-if="form.errors.kode_barang" class="text-sm text-red-500 mt-1">{{ form.errors.kode_barang }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Type</label>
                                <SearchSelect v-model="form.id_tipe" :options="list_tipe" value-key="id_tipe" label-key="nama" placeholder="Pilih Type..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Satuan</label>
                                <SearchSelect v-model="form.id_satuan" :options="list_satuan" value-key="id_satuan" label-key="nama" placeholder="Pilih Satuan..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Berat</label>
                                <SearchSelect v-model="form.id_berat" :options="list_berat" value-key="id_berat" label-key="nama" placeholder="Pilih Berat..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Ukuran</label>
                                <SearchSelect v-model="form.id_ukuran" :options="list_ukuran" value-key="id_ukuran" label-key="nama" placeholder="Pilih Ukuran..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Warna</label>
                                <SearchSelect v-model="form.id_warna" :options="list_warna" value-key="id_warna" label-key="nama" placeholder="Pilih Warna..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">Karakter</label>
                                <SearchSelect v-model="form.id_karakter" :options="list_karakter" value-key="id_karakter" label-key="nama" placeholder="Pilih Karakter..." />
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 font-medium">UOM</label>
                                <SearchSelect v-model="form.id_uom" :options="list_uom" value-key="id_uom" label-key="nama_uom" placeholder="Pilih UOM..." />
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 font-medium">Foto Produk</label>
                            <div class="flex flex-wrap gap-3 mt-2">
                                <div v-for="(p, index) in previews" :key="index" class="relative w-20 h-20 rounded-md overflow-hidden border">
                                    <img :src="p.url" class="w-full h-full object-cover" />
                                    <button type="button" @click="removePreview(index)" class="absolute top-0.5 right-0.5 bg-black/60 text-white rounded-full p-0.5">
                                        <X class="w-3 h-3" />
                                    </button>
                                </div>

                                <label class="w-20 h-20 rounded-md border-2 border-dashed flex items-center justify-center cursor-pointer text-gray-400 hover:text-blue-500 hover:border-blue-400">
                                    <ImagePlus class="w-6 h-6" />
                                    <input type="file" accept="image/*" multiple class="hidden" @change="handleFoto" />
                                </label>
                            </div>
                            <p v-if="form.errors.foto" class="text-sm text-red-500 mt-1">{{ form.errors.foto }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <Button type="button" variant="outline" :disabled="isSubmitting" @click="closeModal">Batal</Button>
                            <Button type="submit" :disabled="isSubmitting" class="bg-blue-600 text-white flex items-center justify-center">
                                <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
                                {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p>Yakin ingin menghapus produk detail {{ produkDetailToDelete?.produk?.nama_produk }}?</p>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="destroyProdukDetail">Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
