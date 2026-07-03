<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Package,
    Pencil,
    Trash,
    Plus,
    X,
    ImagePlus,
    Search,
    Layers,
    ShoppingBag
} from 'lucide-vue-next';

const props = defineProps({
    barangs: Object,
    filters: Object,
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

const emptyVariant = () => ({
    nama_variant: 'Default',
    kode_variant: '',
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
    harga_jual: 0,
    satuan: '',
    stok: 0,
    variants: [emptyVariant()],
});

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
        form.harga_jual = item.harga_jual ?? 0;
        form.satuan = item.satuan ?? '';
        form.stok = item.stok ?? 0;

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
        form.variants = [emptyVariant()];
    }

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

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div
                        v-for="b in barangs.data"
                        :key="b.id_barang"
                        class="group rounded-3xl border border-gray-100 bg-white shadow-sm hover:shadow-xl transition overflow-hidden"
                    >
                        <div class="relative h-52 bg-gray-100">
                            <img
                                v-if="b.details?.flatMap(d => d.gambars || [])?.[0]?.path_file"
                                :src="`/storage/${b.details.flatMap(d => d.gambars || [])[0].path_file}`"
                                class="w-full h-full object-cover group-hover:scale-105 transition"
                            />

                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <Package class="w-14 h-14" />
                            </div>

                            <div class="absolute top-3 left-3 bg-white/90 text-xs px-3 py-1 rounded-full shadow">
                                {{ b.kode_barang }}
                            </div>
                        </div>

                        <div class="p-5">
                            <h3 class="font-bold text-lg text-gray-800 line-clamp-1">
                                {{ b.nama_barang }}
                            </h3>

                            <p class="text-blue-700 font-semibold mt-1">
                                {{ rupiah(b.harga_jual) }}
                            </p>

                            <div class="flex items-center gap-3 text-sm text-gray-500 mt-3">
                                <span class="flex items-center gap-1">
                                    <Layers class="w-4 h-4" />
                                    {{ b.details?.length || 0 }} varian
                                </span>
                                <span>Stok: {{ b.stok ?? 0 }}</span>
                                <span>{{ b.satuan }}</span>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-4">
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

                            <div class="flex justify-end gap-2 mt-5 pt-4 border-t">
                                <Button size="sm" variant="outline" @click="openModal(b)">
                                    <Pencil class="w-4 h-4 mr-1" />
                                    Edit
                                </Button>

                                <Button size="sm" class="bg-red-600 text-white" @click="confirmDelete(b)">
                                    <Trash class="w-4 h-4 mr-1" />
                                    Hapus
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
                                Lengkapi data produk, varian, dan gambar produk.
                            </p>
                        </div>

                        <button @click="showModal = false" class="p-2 rounded-full hover:bg-gray-100">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="flex border-b px-6">
                        <button
                            class="px-4 py-3 text-sm"
                            :class="activeTab === 'produk' ? 'border-b-2 border-blue-700 text-blue-700 font-bold' : 'text-gray-500'"
                            @click="activeTab = 'produk'"
                        >
                            Data Produk
                        </button>

                        <button
                            class="px-4 py-3 text-sm"
                            :class="activeTab === 'variant' ? 'border-b-2 border-blue-700 text-blue-700 font-bold' : 'text-gray-500'"
                            @click="activeTab = 'variant'"
                        >
                            Varian & Gambar
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="overflow-y-auto max-h-[72vh] p-6">
                        <div v-if="activeTab === 'produk'" class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
                                <label class="text-sm font-medium text-gray-600">Harga Jual</label>
                                <Input v-model="form.harga_jual" type="number" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Satuan</label>
                                <Input v-model="form.satuan" placeholder="pcs, box, rim" class="mt-1 rounded-xl" />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600">Stok Utama</label>
                                <Input v-model="form.stok" type="number" class="mt-1 rounded-xl" />
                            </div>
                        </div>

                        <div v-if="activeTab === 'variant'" class="space-y-5">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-gray-800">Varian Produk</h3>
                                    <p class="text-sm text-gray-400">
                                        Tambahkan ukuran, warna, tipe, dan upload beberapa gambar.
                                    </p>
                                </div>

                                <Button type="button" class="bg-blue-700 text-white" @click="addVariant">
                                    <Plus class="w-4 h-4 mr-2" />
                                    Tambah Varian
                                </Button>
                            </div>

                            <div
                                v-for="(variant, index) in form.variants"
                                :key="index"
                                class="rounded-3xl border border-gray-200 p-5 bg-gray-50"
                            >
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="font-bold text-gray-700">
                                        Varian #{{ index + 1 }}
                                    </h4>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="text-red-600"
                                        :disabled="form.variants.length === 1"
                                        @click="removeVariant(index)"
                                    >
                                        <Trash class="w-4 h-4 mr-1" />
                                        Hapus
                                    </Button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Nama Varian</label>
                                        <Input
                                            v-model="variant.nama_variant"
                                            placeholder="Merah / XL / Tipe A"
                                            class="mt-1 rounded-xl"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Kode Varian</label>
                                        <Input
                                            v-model="variant.kode_variant"
                                            placeholder="BRG-MRH"
                                            class="mt-1 rounded-xl"
                                        />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Stok Varian</label>
                                        <Input v-model="variant.stok" type="number" class="mt-1 rounded-xl" />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Harga Beli</label>
                                        <Input v-model="variant.harga_beli" type="number" class="mt-1 rounded-xl" />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Harga Jual</label>
                                        <Input v-model="variant.harga_jual" type="number" class="mt-1 rounded-xl" />
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Harga Jual Jumbo</label>
                                        <Input v-model="variant.harga_jual_jumbo" type="number" class="mt-1 rounded-xl" />
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <label class="text-sm font-medium text-gray-600">Gambar Varian</label>

                                    <label
                                        class="mt-2 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-3xl p-6 bg-white cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition"
                                    >
                                        <ImagePlus class="w-8 h-8 text-blue-700 mb-2" />
                                        <span class="text-sm font-medium text-gray-700">
                                            Klik untuk upload banyak gambar
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            JPG, PNG, WEBP maksimal 2MB per gambar
                                        </span>

                                        <input
                                            type="file"
                                            multiple
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleImages($event, index)"
                                        />
                                    </label>

                                    <div v-if="variant.previews?.length" class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-4">
                                        <div
                                            v-for="(preview, imageIndex) in variant.previews"
                                            :key="imageIndex"
                                            class="relative rounded-2xl overflow-hidden border bg-white group"
                                        >
                                            <img :src="preview.url" class="w-full h-28 object-cover" />

                                            <button
                                                type="button"
                                                class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 opacity-90"
                                                @click="removePreview(index, imageIndex)"
                                            >
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
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