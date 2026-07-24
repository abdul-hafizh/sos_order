<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import SearchSelect from '@/Components/SearchSelect.vue';
import {
    Package,
    Pencil,
    Trash,
    Plus,
    X,
    Search,
    Layers,
    ShoppingBag
} from 'lucide-vue-next';

const props = defineProps({
    barangs: Object,
    filters: Object,
    list_satuan: Array,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingBarang = ref(null);
const barangToDelete = ref(null);
const activeTab = ref('produk');

const params = ref({
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || 10,
});

watch(params, (newParams) => {
    router.get(route('barang.index'), newParams, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

const generateKodeVarian = () => {
    return 'V' + Math.random().toString(36).slice(2, 8).toUpperCase();
};

const emptyVariant = () => ({
    nama_variant: 'Default',
    kode_variant: generateKodeVarian(),
    harga_beli: 0,
    harga_jual: 0,
    harga_jual_jumbo: 0,
    stok: 0,
    gambars: [],
    previews: [],
    old_gambars: [],
    deleted_gambar_ids: [],
});

const form = useForm({
    kode_barang: '',
    nama_barang: '',
    harga_beli: 0,
    harga_beli_before: 0,
    harga_jual: 0,
    harga_jual_before: 0,
    harga_jual_jumbo: 0,
    harga_jual_jumbo_before: 0,
    satuan: '',
    stok: 0,
    qty_pos: 0,
    min_stok: 0,
    max_stok: 0,
    category_code: '',
    variants: [emptyVariant()],
});

const generateKodeBarang = () => {
    const randomNumbers = Math.floor(1000000 + Math.random() * 9000000);
    return `R${randomNumbers}`;
};

const categoryMaster = ref([]);
const categoryKeyword = ref('');
const showCategoryDropdown = ref(false);

const loadCategory = async () => {
    const res = await axios.get(route('category.list'));
    categoryMaster.value = res.data;
};

onMounted(() => {
    loadCategory();
});

const filteredCategory = computed(() => {
    if (!categoryKeyword.value) return categoryMaster.value;

    return categoryMaster.value.filter(
        (item) =>
            item.categoryname.toLowerCase().includes(categoryKeyword.value.toLowerCase()) ||
            item.categorycode.toLowerCase().includes(categoryKeyword.value.toLowerCase())
    );
});

const selectCategory = (item) => {
    form.category_code = item.categorycode;
    categoryKeyword.value = item.categoryname;
    showCategoryDropdown.value = false;
};

const rupiah = (value) => {
    if (!value) return 'Rp 0';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
};

const openModal = (item = null) => {
    editingBarang.value = item;
    activeTab.value = 'produk';

    if (item) {
        form.kode_barang = item.kode_barang;
        form.nama_barang = item.nama_barang;
        form.harga_beli = item.harga_beli ?? 0;
        form.harga_beli_before = item.harga_beli_before ?? 0;
        form.harga_jual = item.harga_jual ?? 0;
        form.harga_jual_before = item.harga_jual_before ?? 0;
        form.harga_jual_jumbo = item.harga_jual_jumbo ?? 0;
        form.harga_jual_jumbo_before = item.harga_jual_jumbo_before ?? 0;
        form.satuan = item.satuan ?? '';
        form.stok = item.stok ?? 0;
        form.qty_pos = item.qty_pos ?? 0;
        form.min_stok = item.min_stok ?? 0;
        form.max_stok = item.max_stok ?? 0;
        form.category_code = item.category_code ?? '';

        categoryKeyword.value = item.category?.categoryname || '';

        form.variants = item.details?.length
        ? item.details.map((detail) => ({
            id_barang_detail: detail.id_barang_detail,
            nama_variant: detail.nama_variant,
            kode_variant: detail.kode_variant,
            harga_beli: detail.harga_beli ?? 0,
            harga_jual: detail.harga_jual ?? 0,
            harga_jual_jumbo: detail.harga_jual_jumbo ?? 0,
            stok: detail.stok ?? 0,

            gambars: [],
            old_gambars: detail.gambars || [],

            previews: detail.gambars?.map((g) => ({
                type: 'old',
                id_barang_gambar: g.id_barang_gambar,
                url: `/storage/${g.path_file}`,
            })) || [],

            deleted_gambar_ids: [],
        }))
        : [emptyVariant()];
    } else {
        form.reset();
        form.kode_barang = generateKodeBarang();
        form.variants = [emptyVariant()];
        categoryKeyword.value = '';
    }

    showCategoryDropdown.value = false;
    showModal.value = true;
};

const addVariant = () => {
    form.variants.push(emptyVariant());
};

const removeVariant = (index) => {
    if (form.variants.length === 1) return;
    form.variants.splice(index, 1);
};

const handleImages = (event, index) => {
    const files = Array.from(event.target.files || []);

    files.forEach((file) => {
        form.variants[index].gambars.push(file);

        form.variants[index].previews.push({
            type: 'new',
            file,
            url: URL.createObjectURL(file),
        });
    });

    event.target.value = '';
};

const removePreview = (variantIndex, imageIndex) => {
    const variant = form.variants[variantIndex];
    const preview = variant.previews[imageIndex];

    if (preview.type === 'old') {
        variant.deleted_gambar_ids.push(preview.id_barang_gambar);
    }

    if (preview.type === 'new') {
        const fileIndex = variant.gambars.findIndex((file) => file === preview.file);

        if (fileIndex !== -1) {
            variant.gambars.splice(fileIndex, 1);
        }

        URL.revokeObjectURL(preview.url);
    }

    variant.previews.splice(imageIndex, 1);
};

const submit = () => {
    if (editingBarang.value) {
        router.post(
            route('barang.update', editingBarang.value.id_barang),
            {
                ...form.data(),
                _method: 'put',
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    showModal.value = false;
                },
            }
        );
    } else {
        form.post(route('barang.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const confirmDelete = (item) => {
    barangToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyBarang = () => {
    router.delete(route('barang.destroy', barangToDelete.value.id_barang), {
        onSuccess: () => {
            showDeleteModal.value = false;
            barangToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Barang" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Barang</h2>
                <p class="text-sm text-gray-400">Master produk, varian, stok, dan gambar</p>
            </div>
        </template>

        <div class="p-6">
            <div class="rounded-3xl bg-gradient-to-r from-blue-700 to-indigo-700 p-6 text-white shadow-lg mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl bg-white/20 p-4">
                            <ShoppingBag class="w-9 h-9" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Katalog Barang</h1>
                            <p class="text-sm text-blue-100">
                                Kelola produk seperti ecommerce: gambar, harga, stok, dan banyak varian.
                            </p>
                        </div>
                    </div>

                    <Button class="bg-white text-blue-700 hover:bg-blue-50" @click="openModal()">
                        <Plus class="w-4 h-4 mr-2" />
                        Tambah Barang
                    </Button>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
                    <div class="flex items-center gap-2">
                        <select v-model="params.per_page" class="border-gray-300 rounded-xl text-sm">
                            <option value="10">10 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                        </select>
                    </div>

                    <div class="relative w-full md:max-w-sm">
                        <Search class="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                        <Input
                            v-model="params.search"
                            placeholder="Cari kode atau nama barang..."
                            class="pl-9 rounded-xl"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-5">
                    <div
                        v-for="b in barangs.data"
                        :key="b.id_barang"
                        class="group rounded-2xl md:rounded-3xl border border-gray-100 bg-white shadow-sm hover:shadow-xl transition overflow-hidden"
                    >
                        <div class="relative h-28 md:h-52 bg-gray-100">
                            <img
                                v-if="b.produk?.gambars?.[0]?.path_file"
                                :src="`/storage/${b.produk.gambars[0].path_file}`"
                                class="w-full h-full object-cover group-hover:scale-105 transition"
                            />

                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <Package class="w-8 h-8 md:w-14 md:h-14" />
                            </div>

                            <div class="absolute top-2 left-2 md:top-3 md:left-3 bg-white/90 text-[10px] md:text-xs px-2 md:px-3 py-0.5 md:py-1 rounded-full shadow">
                                {{ b.kode_barang }}
                            </div>

                            <div v-if="b.category" class="absolute top-2 right-2 md:top-3 md:right-3 bg-blue-700/90 text-white text-[10px] md:text-xs px-2 md:px-3 py-0.5 md:py-1 rounded-full shadow">
                                {{ b.category.categoryname }}
                            </div>
                        </div>

                        <div class="p-3 md:p-5">
                            <h3 class="font-bold text-sm md:text-lg text-gray-800 line-clamp-1">
                                {{ b.nama_barang }}
                            </h3>

                            <p class="text-blue-700 font-semibold text-sm md:text-base mt-1">
                                {{ rupiah(b.harga_jual) }}
                            </p>

                            <div class="flex items-center gap-3 text-xs md:text-sm text-gray-500 mt-2 md:mt-3">
                                <span class="hidden md:flex items-center gap-1">
                                    <Layers class="w-4 h-4" />
                                    {{ b.details?.length || 0 }} varian
                                </span>
                                <span>Stok: {{ b.stok ?? 0 }}</span>
                                <span>{{ b.satuan }}</span>
                            </div>

                            <div class="hidden md:flex flex-wrap gap-2 mt-4">
                                <span
                                    v-for="v in b.details?.slice(0, 4)"
                                    :key="v.id_barang_detail"
                                    class="text-xs bg-gray-100 px-3 py-1 rounded-full"
                                >
                                    {{ v.nama_variant }}
                                </span>

                                <span
                                    v-if="b.details?.length > 4"
                                    class="text-xs bg-gray-100 px-3 py-1 rounded-full"
                                >
                                    +{{ b.details.length - 4 }}
                                </span>
                            </div>

                            <div class="flex justify-end gap-2 mt-3 md:mt-5 pt-3 md:pt-4 border-t">
                                <Button size="sm" variant="outline" @click="openModal(b)" class="text-xs md:text-sm px-2 md:px-3">
                                    <Pencil class="w-4 h-4 md:mr-1" />
                                    <span class="hidden md:inline">Edit</span>
                                </Button>

                                <Button size="sm" class="bg-red-600 text-white text-xs md:text-sm px-2 md:px-3" @click="confirmDelete(b)">
                                    <Trash class="w-4 h-4 md:mr-1" />
                                    <span class="hidden md:inline">Hapus</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-1 justify-center md:justify-end">
                    <Link
                        v-for="(link, index) in barangs.links"
                        :key="index"
                        :href="link.url ?? '#'"
                    >
                        <Button
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </Link>
                </div>
            </div>

            <div v-if="showModal" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl w-full max-w-6xl max-h-[92vh] overflow-hidden shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b">
                        <div>
                            <h2 class="text-xl font-bold">
                                {{ editingBarang ? 'Edit Barang' : 'Tambah Barang' }}
                            </h2>
                            <p class="text-sm text-gray-400">
                                Lengkapi data produk.
                            </p>
                        </div>

                        <button @click="showModal = false" class="p-2 rounded-full hover:bg-gray-100">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="overflow-y-auto max-h-[72vh] p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="relative">
                                <label class="text-sm font-medium text-gray-600">Kategori</label>
                                <Input
                                    v-model="categoryKeyword"
                                    placeholder="Cari kategori..."
                                    class="mt-1 rounded-xl"
                                    @focus="showCategoryDropdown = true"
                                    @input="showCategoryDropdown = true"
                                />

                                <div
                                    v-if="showCategoryDropdown"
                                    class="absolute z-50 w-full mt-1 bg-white border rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <div
                                        v-for="item in filteredCategory"
                                        :key="item.categorycode"
                                        @click="selectCategory(item)"
                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer"
                                    >
                                        <div class="font-medium">{{ item.categoryname }}</div>
                                        <div class="text-xs text-gray-500">{{ item.categorycode }}</div>
                                    </div>

                                    <div v-if="filteredCategory.length === 0" class="px-3 py-2 text-gray-400 text-sm">
                                        Tidak ada kategori ditemukan
                                    </div>
                                </div>

                                <p v-if="form.errors.category_code" class="text-xs text-red-500 mt-1">
                                    {{ form.errors.category_code }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Satuan</label>
                                <SearchSelect
                                    v-model="form.satuan"
                                    :options="list_satuan"
                                    value-key="nama"
                                    label-key="nama"
                                    placeholder="Pilih Satuan..."
                                />
                                <p v-if="form.errors.satuan" class="text-xs text-red-500 mt-1">
                                    {{ form.errors.satuan }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Kode Barang</label>
                                <Input v-model="form.kode_barang" class="mt-1 rounded-xl" required />
                                <p v-if="form.errors.kode_barang" class="text-xs text-red-500 mt-1">
                                    {{ form.errors.kode_barang }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Nama Barang</label>
                                <Input v-model="form.nama_barang" class="mt-1 rounded-xl" required />
                                <p v-if="form.errors.nama_barang" class="text-xs text-red-500 mt-1">
                                    {{ form.errors.nama_barang }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Beli</label>
                                <Input v-model="form.harga_beli" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Beli Sebelumnya</label>
                                <Input v-model="form.harga_beli_before" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Jual</label>
                                <Input v-model="form.harga_jual" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Jual Sebelumnya</label>
                                <Input v-model="form.harga_jual_before" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Jual Jumbo</label>
                                <Input v-model="form.harga_jual_jumbo" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Harga Jual Jumbo Sebelumnya</label>
                                <Input v-model="form.harga_jual_jumbo_before" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Stok Utama</label>
                                <Input v-model="form.stok" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Qty POS</label>
                                <Input v-model="form.qty_pos" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Min Stok</label>
                                <Input v-model="form.min_stok" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Max Stok</label>
                                <Input v-model="form.max_stok" type="number" class="mt-1 rounded-xl" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6 pt-5 border-t sticky bottom-0 bg-white">
                            <Button type="button" variant="outline" @click="showModal = false">
                                Batal
                            </Button>

                            <Button type="submit" class="bg-blue-700 text-white" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Barang' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-3xl w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p class="text-sm text-gray-500">
                        Yakin ingin menghapus
                        <b>{{ barangToDelete?.nama_barang }}</b>?
                    </p>

                    <div class="flex justify-end gap-2 mt-5">
                        <Button variant="outline" @click="showDeleteModal = false">
                            Batal
                        </Button>

                        <Button class="bg-red-600 text-white" @click="destroyBarang">
                            Hapus
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>