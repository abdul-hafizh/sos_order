<script setup>
import { ref } from "vue";
import { useForm, router, Link, Head } from "@inertiajs/vue3";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import SearchSelect from "@/Components/SearchSelect.vue";
import {
    Package,
    Pencil,
    X,
    Search,
    Layers,
    CirclePile,
    ArrowUp,
    ArrowDown,
    Minus,
    ImagePlus,
    Loader2,
} from "lucide-vue-next";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";

const props = defineProps({
    barangs: Object,
    filters: Object,
    list_satuan: Array,
    list_produk: Array,
    list_kategori: Array,
    list_tipe: Array,
    list_berat: Array,
    list_ukuran: Array,
    list_warna: Array,
    list_karakter: Array,
    list_uom: Array,
});

const showModal = ref(false);
const editingBarang = ref(null);
const activeTab = ref("barang");
const isSubmitting = ref(false);

const params = ref({
    search: props.filters.search || "",
    per_page: props.filters.per_page || 10,
});

const appliedSearch = ref(props.filters.search || "");
const searchData = () => {
    appliedSearch.value = params.value.search;
    router.get(
        route("barang.index"),
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
const resetSearch = () => {
    params.value.search = "";
    appliedSearch.value = "";
    router.get(
        route("barang.index"),
        {
            search: "",
            per_page: params.value.per_page,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const changePerPage = () => {
    router.get(
        route("barang.index"),
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

const generateKodeVarian = () => {
    return "V" + Math.random().toString(36).slice(2, 8).toUpperCase();
};

const emptyVariant = () => ({
    nama_variant: "Default",
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

const emptyDetail = () => ({
    id_produk: null,
    category_id: null,
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

const form = useForm({
    nama_barang: "",
    harga_beli: 0,
    harga_beli_before: 0,
    harga_jual: 0,
    harga_jual_before: 0,
    harga_jual_jumbo: 0,
    harga_jual_jumbo_before: 0,
    stok: 0,
    qty_pos: 0,
    min_stok: 0,
    max_stok: 0,
    variants: [emptyVariant()],
    detail: emptyDetail(),
});

const detailPreviews = ref([]);

const handleDetailFoto = (event) => {
    const files = Array.from(event.target.files || []);

    files.forEach((file) => {
        form.detail.foto.push(file);
        detailPreviews.value.push({
            type: "new",
            file,
            url: URL.createObjectURL(file),
        });
    });

    event.target.value = "";
};

const removeDetailPreview = (index) => {
    const preview = detailPreviews.value[index];

    if (preview.type === "old") {
        form.detail.deleted_gambar_ids.push(preview.id_produk_gambar);
    }

    if (preview.type === "new") {
        const fileIndex = form.detail.foto.findIndex(
            (file) => file === preview.file,
        );

        if (fileIndex !== -1) {
            form.detail.foto.splice(fileIndex, 1);
        }

        URL.revokeObjectURL(preview.url);
    }

    detailPreviews.value.splice(index, 1);
};

const rupiah = (value) => {
    if (!value) return "Rp 0";

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value);
};

const openModal = (item) => {
    editingBarang.value = item;
    activeTab.value = "barang";
    form.clearErrors();

    form.nama_barang = item.nama_barang;
    form.harga_beli = item.harga_beli ?? 0;
    form.harga_beli_before = item.harga_beli_before ?? 0;
    form.harga_jual = item.harga_jual ?? 0;
    form.harga_jual_before = item.harga_jual_before ?? 0;
    form.harga_jual_jumbo = item.harga_jual_jumbo ?? 0;
    form.harga_jual_jumbo_before = item.harga_jual_jumbo_before ?? 0;
    form.stok = item.stok ?? 0;
    form.qty_pos = item.qty_pos ?? 0;
    form.min_stok = item.min_stok ?? 0;
    form.max_stok = item.max_stok ?? 0;

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

              previews:
                  detail.gambars?.map((g) => ({
                      type: "old",
                      id_barang_gambar: g.id_barang_gambar,
                      url: `/storage/${g.path_file}`,
                  })) || [],

              deleted_gambar_ids: [],
          }))
        : [emptyVariant()];

    const produk = item.produk;

    form.detail = {
        id_produk: produk?.id_produk ?? null,
        category_id: produk?.category_id ?? null,
        id_tipe: produk?.id_tipe ?? null,
        id_satuan: produk?.id_satuan ?? null,
        id_berat: produk?.id_berat ?? null,
        id_ukuran: produk?.id_ukuran ?? null,
        id_warna: produk?.id_warna ?? null,
        id_karakter: produk?.id_karakter ?? null,
        id_uom: produk?.id_uom ?? null,
        foto: [],
        deleted_gambar_ids: [],
    };

    detailPreviews.value = (produk?.gambars || []).map((g) => ({
        type: "old",
        id_produk_gambar: g.id_produk_gambar,
        url: `/storage/${g.path_file}`,
    }));

    showModal.value = true;
};

const closeModal = () => {
    if (isSubmitting.value) return;

    showModal.value = false;
    editingBarang.value = null;

    detailPreviews.value.forEach(
        (p) => p.type === "new" && URL.revokeObjectURL(p.url),
    );
    detailPreviews.value = [];

    form.reset();
    form.clearErrors();
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
            type: "new",
            file,
            url: URL.createObjectURL(file),
        });
    });

    event.target.value = "";
};

const removePreview = (variantIndex, imageIndex) => {
    const variant = form.variants[variantIndex];
    const preview = variant.previews[imageIndex];

    if (preview.type === "old") {
        variant.deleted_gambar_ids.push(preview.id_barang_gambar);
    }

    if (preview.type === "new") {
        const fileIndex = variant.gambars.findIndex(
            (file) => file === preview.file,
        );

        if (fileIndex !== -1) {
            variant.gambars.splice(fileIndex, 1);
        }

        URL.revokeObjectURL(preview.url);
    }

    variant.previews.splice(imageIndex, 1);
};

const isEmptyValue = (value) => value === null || value === undefined || value === "";

// Tab "Barang": semua isian wajib diisi.
const requiredBarangFields = [
    ["nama_barang", "Nama Barang"],
    ["harga_beli", "Harga Beli"],
    ["harga_beli_before", "Harga Beli Sebelumnya"],
    ["harga_jual", "Harga Jual"],
    ["harga_jual_before", "Harga Jual Sebelumnya"],
    ["harga_jual_jumbo", "Harga Jual Jumbo"],
    ["harga_jual_jumbo_before", "Harga Jual Jumbo Sebelumnya"],
    ["stok", "Stok Utama"],
    ["qty_pos", "Qty POS"],
    ["min_stok", "Min Stok"],
    ["max_stok", "Max Stok"],
];

// Tab "Barang Detail": semua wajib diisi kecuali Berat, Ukuran, Warna & Karakter.
const requiredDetailFields = [
    ["category_id", "Kategori"],
    ["id_tipe", "Type"],
    ["id_satuan", "Satuan"],
    ["id_uom", "UOM"],
];

const submit = () => {
    if (!editingBarang.value) return;

    for (const [field, label] of requiredBarangFields) {
        if (isEmptyValue(form[field])) {
            alert(`${label} pada tab Barang wajib diisi.`);
            activeTab.value = "barang";
            return;
        }
    }

    for (const [field, label] of requiredDetailFields) {
        if (isEmptyValue(form.detail[field])) {
            alert(`${label} pada tab Barang Detail wajib diisi.`);
            activeTab.value = "detail";
            return;
        }
    }

    if (detailPreviews.value.length === 0) {
        alert("Foto Produk pada tab Barang Detail wajib diisi minimal 1 foto.");
        activeTab.value = "detail";
        return;
    }

    isSubmitting.value = true;

    router.post(
        route("barang.update", editingBarang.value.id_barang),
        {
            ...form.data(),
            _method: "put",
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // Beri jeda supaya toast "berhasil diupdate" sempat terbaca,
                // baru refresh halaman - reload ini juga otomatis menutup modal.
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            },
            onError: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const getTrend = (current, before) => {
    if (current > before) return { icon: ArrowUp, color: "text-green-600" };
    if (current < before) return { icon: ArrowDown, color: "text-red-600" };
    return { icon: Minus, color: "text-gray-400" };
};

const showVendorModal = ref(false);
const selectedBarang = ref(null);
const vendorList = ref([]);
const vendorMaster = ref([]);
const showAddVendor = ref(false);
const editingVendor = ref(null);
const showDeleteVendorModal = ref(false);
const vendorToDelete = ref(null);

const vendorForm = useForm({
    id_barang_vendor: null,
    kode_barang: "",
    kode_vendor: "",
    active: true,
});

const openVendorModal = async (barang) => {
    selectedBarang.value = barang;

    vendorForm.kode_barang = barang.kode_barang;

    const [vendorBarang, vendorMasterData] = await Promise.all([
        axios.get(route("barang-vendor.index", barang.kode_barang)),
        axios.get(route("barang-vendor.vendor-list")),
    ]);

    vendorList.value = vendorBarang.data.vendors;

    vendorMaster.value = vendorMasterData.data;

    showVendorModal.value = true;
};

const saveVendor = () => {
    if (editingVendor.value) {
        vendorForm.put(
            route("barang-vendor.update", editingVendor.value.id_barang_vendor),
            {
                preserveScroll: true,

                onSuccess: async () => {
                    editingVendor.value = null;

                    showAddVendor.value = false;

                    vendorForm.reset();

                    await loadVendor();
                },
            },
        );
    } else {
        vendorForm.post(route("barang-vendor.store"), {
            preserveScroll: true,

            onSuccess: async () => {
                showAddVendor.value = false;

                vendorForm.reset();

                await loadVendor();
            },
        });
    }
};

const editVendor = (vendor) => {
    editingVendor.value = vendor;

    vendorForm.id_barang_vendor = vendor.id_barang_vendor;
    vendorForm.kode_barang = vendor.kode_barang;
    vendorForm.kode_vendor = vendor.kode_vendor;
    vendorForm.active = vendor.active;

    showAddVendor.value = true;
};

const loadVendor = async () => {
    const response = await axios.get(
        route("barang-vendor.index", selectedBarang.value.kode_barang),
    );

    vendorList.value = response.data.vendors;
};
const confirmDeleteVendor = (vendor) => {
    vendorToDelete.value = vendor;

    showDeleteVendorModal.value = true;
};

const destroyVendor = () => {
    router.delete(
        route("barang-vendor.destroy", vendorToDelete.value.id_barang_vendor),
        {
            preserveScroll: true,

            onSuccess: async () => {
                showDeleteVendorModal.value = false;

                vendorToDelete.value = null;

                await loadVendor();
            },
        },
    );
};
</script>

<template>
    <Head title="Barang" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Barang</h2>
                <p class="text-sm text-gray-400">
                    Master produk, varian, stok, dan gambar
                </p>
            </div>
        </template>

        <div class="p-6">
            <div
                class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5"
                >
                    <div class="flex items-center gap-2">
                        <select
                            v-model="params.per_page"
                            @change="changePerPage"
                            class="border-gray-300 rounded-xl text-sm"
                        >
                            <option value="10">10 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="relative max-w-xs">
                            <Input
                                v-model="params.search"
                                @keyup.enter="searchData"
                                placeholder="Cari Katalog Barang..."
                                class="pr-10 border-gray-300 rounded-md w-full"
                            />

                            <button
                                v-if="params.search"
                                type="button"
                                @click="resetSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition duration-200"
                                title="Bersihkan pencarian"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    v-if="appliedSearch"
                    class="flex items-center gap-1.5 text-sm text-gray-500 animate-fade-in my-5 mx-2"
                >
                    <span>Menampilkan hasil untuk:</span>
                    <span
                        class="bg-blue-50 border border-blue-100 text-blue-700 font-semibold px-2 py-0.5 rounded-full"
                    >
                        {{ appliedSearch }}
                    </span>
                </div>
                
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4"
                >
                    <div
                        v-for="b in barangs.data"
                        :key="b.id_barang"
                        class="group flex flex-col rounded-2xl md:rounded-3xl border border-gray-100 bg-white shadow-sm hover:shadow-xl transition duration-300 overflow-hidden"
                    >
                        <div
                            class="relative h-36 md:h-52 bg-gray-100 overflow-hidden"
                        >
                            <img
                                v-if="b.produk?.gambars?.[0]?.path_file"
                                :src="`/storage/${b.produk.gambars[0].path_file}`"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-50"
                            >
                                <Package class="w-10 h-10 md:w-14 md:h-14" />
                            </div>

                            <div
                                class="absolute top-3 left-3 bg-white/90 backdrop-blur-md text-[11px] md:text-xs font-medium px-2.5 py-1 rounded-full shadow-sm text-gray-700"
                            >
                                {{ b.kode_barang }}
                            </div>

                            <div
                                v-if="b.category"
                                class="absolute top-3 right-3 bg-blue-600/90 backdrop-blur-md text-white text-[11px] md:text-xs font-medium px-2.5 py-1 rounded-full shadow-sm"
                            >
                                {{ b.category.categoryname }}
                            </div>
                        </div>

                        <div class="flex flex-col flex-grow p-4 md:p-5">
                            <h3
                                class="font-bold text-base md:text-lg text-gray-800 line-clamp-1 mb-3"
                            >
                                {{ b.nama_barang }}
                            </h3>

                            <div
                                class="space-y-1.5 bg-gray-50/70 p-3 rounded-xl border border-gray-100/80 mb-3"
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
                                        <span>{{ rupiah(b.harga_beli) }}</span>
                                        <component
                                            :is="
                                                getTrend(
                                                    b.harga_beli,
                                                    b.harga_beli_before,
                                                ).icon
                                            "
                                            :class="
                                                getTrend(
                                                    b.harga_beli,
                                                    b.harga_beli_before,
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
                                        <span>{{ rupiah(b.harga_jual) }}</span>
                                        <component
                                            :is="
                                                getTrend(
                                                    b.harga_jual,
                                                    b.harga_jual_before,
                                                ).icon
                                            "
                                            :class="
                                                getTrend(
                                                    b.harga_jual,
                                                    b.harga_jual_before,
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
                                            rupiah(b.harga_jual_jumbo)
                                        }}</span>
                                        <component
                                            :is="
                                                getTrend(
                                                    b.harga_jual_jumbo,
                                                    b.harga_jual_jumbo_before,
                                                ).icon
                                            "
                                            :class="
                                                getTrend(
                                                    b.harga_jual_jumbo,
                                                    b.harga_jual_jumbo_before,
                                                ).color
                                            "
                                            class="w-3.5 h-3.5"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between text-xs md:text-sm text-gray-500 mb-3"
                            >
                                <span
                                    class="flex items-center gap-1.5 font-medium text-gray-600"
                                >
                                    <Layers class="w-4 h-4 text-gray-400" />
                                    {{ b.details?.length || 0 }} Varian
                                </span>
                                <span
                                    class="bg-gray-100 px-2.5 py-0.5 rounded-md font-medium text-gray-700"
                                >
                                    Stok: {{ b.stok ?? 0 }} {{ b.satuan }}
                                </span>
                            </div>

                            <div class="hidden md:flex flex-wrap gap-1.5 mb-4">
                                <span
                                    v-for="v in b.details?.slice(0, 4)"
                                    :key="v.id_barang_detail"
                                    class="text-[11px] bg-gray-100 text-gray-600 px-2.5 py-0.5 rounded-md"
                                >
                                    {{ v.nama_variant }}
                                </span>
                                <span
                                    v-if="b.details?.length > 4"
                                    class="text-[11px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md font-medium"
                                >
                                    +{{ b.details.length - 4 }} lainnya
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-end gap-1.5 md:gap-2 mt-auto pt-3 border-t border-gray-100"
                            >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="openModal(b)"
                                    class="text-xs md:text-sm rounded-md px-2.5 py-1.5 h-8 hover:bg-gray-50"
                                >
                                    <Pencil
                                        class="w-3.5 h-3.5 md:mr-1 text-gray-500"
                                    />
                                    <span class="hidden md:inline">Edit</span>
                                </Button>

                                <Button
                                    size="sm"
                                    class="bg-emerald-600 rounded-sm hover:bg-emerald-700 text-white text-xs md:text-sm px-2.5 py-1.5 h-8 shadow-sm"
                                    @click="openVendorModal(b)"
                                >
                                    <CirclePile class="w-3.5 h-3.5 md:mr-1" />
                                    <span class="hidden md:inline">Vendor</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div
                    class="mt-6 flex flex-wrap gap-1 justify-center md:justify-end"
                >
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

            <div
                v-if="showModal"
                class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
            >
                <div
                    class="bg-white rounded-3xl w-full max-w-6xl max-h-[92vh] overflow-hidden shadow-2xl"
                >
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b"
                    >
                        <div>
                            <h2 class="text-xl font-bold">Edit Barang</h2>
                            <p class="text-sm text-gray-400">
                                Lengkapi data produk.
                            </p>
                        </div>

                        <button
                            @click="closeModal"
                            :disabled="isSubmitting"
                            class="p-2 rounded-full hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-1 px-6 pt-4 border-b">
                        <button
                            type="button"
                            @click="activeTab = 'barang'"
                            class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition"
                            :class="
                                activeTab === 'barang'
                                    ? 'border-blue-600 text-blue-700'
                                    : 'border-transparent text-gray-400 hover:text-gray-600'
                            "
                        >
                            Barang
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'detail'"
                            class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition"
                            :class="
                                activeTab === 'detail'
                                    ? 'border-blue-600 text-blue-700'
                                    : 'border-transparent text-gray-400 hover:text-gray-600'
                            "
                        >
                            Barang Detail
                        </button>
                    </div>

                    <form
                        @submit.prevent="submit"
                        class="overflow-y-auto max-h-[68vh] p-6"
                    >
                        <!-- Tab: Barang -->
                        <div
                            v-show="activeTab === 'barang'"
                            class="grid grid-cols-1 md:grid-cols-2 gap-5"
                        >
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Nama Barang
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.nama_barang"
                                    class="mt-1 rounded-xl"
                                    required
                                />
                                <p
                                    v-if="form.errors.nama_barang"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ form.errors.nama_barang }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Beli
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_beli"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Beli Sebelumnya
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_beli_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_jual"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Sebelumnya
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_jual_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Jumbo
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_jual_jumbo"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Jumbo Sebelumnya
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.harga_jual_jumbo_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Stok Utama
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Qty POS
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.qty_pos"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Min Stok
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.min_stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Max Stok
                                    <span class="text-red-500">*</span></label
                                >
                                <Input
                                    v-model="form.max_stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>
                        </div>

                        <!-- Tab: Barang Detail (sama seperti edit di menu Produk Detail) -->
                        <div v-show="activeTab === 'detail'" class="space-y-4">
                            <div
                                class="border rounded-xl px-3 py-2 bg-slate-50"
                            >
                                <div class="text-sm">
                                    <span class="text-gray-400 text-xs block"
                                        >Kode Barang (t_barang)</span
                                    >
                                    <span class="font-semibold">{{
                                        editingBarang?.kode_barang
                                    }}</span>
                                    <span class="text-gray-500">
                                        - {{ editingBarang?.nama_barang }}</span
                                    >
                                </div>
                            </div>

                            <div v-if="false">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Produk</label
                                >
                                <SearchSelect
                                    v-model="form.detail.id_produk"
                                    :options="list_produk"
                                    value-key="id_produk"
                                    label-key="nama_produk"
                                    placeholder="Pilih Produk..."
                                />
                                <p class="text-xs text-gray-400 mt-1">
                                    Kosongkan untuk otomatis dibuat/disinkron
                                    mengikuti Nama Barang di tab "Barang".
                                </p>
                            </div>

                            <div>
                                <label class="text-xs text-gray-400 font-medium"
                                    >Kategori
                                    <span class="text-red-500">*</span></label
                                >
                                <SearchSelect
                                    v-model="form.detail.category_id"
                                    :options="list_kategori"
                                    value-key="categorycode"
                                    label-key="categoryname"
                                    placeholder="Pilih Kategori..."
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Type
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_tipe"
                                        :options="list_tipe"
                                        value-key="id_tipe"
                                        label-key="nama"
                                        placeholder="Pilih Type..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Satuan
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_satuan"
                                        :options="list_satuan"
                                        value-key="id_satuan"
                                        label-key="nama"
                                        placeholder="Pilih Satuan..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Berat</label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_berat"
                                        :options="list_berat"
                                        value-key="id_berat"
                                        label-key="nama"
                                        placeholder="Pilih Berat..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Ukuran</label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_ukuran"
                                        :options="list_ukuran"
                                        value-key="id_ukuran"
                                        label-key="nama"
                                        placeholder="Pilih Ukuran..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Warna</label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_warna"
                                        :options="list_warna"
                                        value-key="id_warna"
                                        label-key="nama"
                                        placeholder="Pilih Warna..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >Karakter</label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_karakter"
                                        :options="list_karakter"
                                        value-key="id_karakter"
                                        label-key="nama"
                                        placeholder="Pilih Karakter..."
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-xs text-gray-400 font-medium"
                                        >UOM
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <SearchSelect
                                        v-model="form.detail.id_uom"
                                        :options="list_uom"
                                        value-key="id_uom"
                                        label-key="nama_uom"
                                        placeholder="Pilih UOM..."
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="text-xs text-gray-400 font-medium"
                                    >Foto Produk
                                    <span class="text-red-500">*</span></label
                                >
                                <div class="flex flex-wrap gap-3 mt-2">
                                    <div
                                        v-for="(p, index) in detailPreviews"
                                        :key="index"
                                        class="relative w-20 h-20 rounded-md overflow-hidden border"
                                    >
                                        <img
                                            :src="p.url"
                                            class="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            @click="removeDetailPreview(index)"
                                            class="absolute top-0.5 right-0.5 bg-black/60 text-white rounded-full p-0.5"
                                        >
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>

                                    <label
                                        class="w-20 h-20 rounded-md border-2 border-dashed flex items-center justify-center cursor-pointer text-gray-400 hover:text-blue-500 hover:border-blue-400"
                                    >
                                        <ImagePlus class="w-6 h-6" />
                                        <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            class="hidden"
                                            @change="handleDetailFoto"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex justify-end gap-3 mt-6 pt-5 border-t sticky bottom-0 bg-white"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="isSubmitting"
                                @click="closeModal"
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                class="bg-blue-700 text-white flex items-center justify-center"
                                :disabled="isSubmitting"
                            >
                                <Loader2
                                    v-if="isSubmitting"
                                    class="w-4 h-4 mr-2 animate-spin"
                                />
                                {{
                                    isSubmitting
                                        ? "Menyimpan..."
                                        : "Simpan Barang"
                                }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                v-if="showVendorModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-5xl rounded-xl bg-white shadow-2xl">
                    <div
                        class="flex items-center justify-between border-b px-6 py-4"
                    >
                        <div>
                            <h2 class="text-xl font-bold">Vendor Barang</h2>
                            <p class="text-sm text-gray-500">
                                Kelola vendor yang memasok barang ini.
                            </p>
                        </div>

                        <Button
                            variant="outline"
                            @click="showVendorModal = false"
                        >
                            Tutup
                        </Button>
                    </div>

                    <div class="p-6">
                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-4 rounded-lg border bg-slate-50 p-4 mb-6"
                        >
                            <div>
                                <p class="text-xs text-gray-500">Kode Barang</p>

                                <p class="font-semibold">
                                    {{ selectedBarang?.kode_barang }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">Nama Barang</p>

                                <p class="font-semibold">
                                    {{ selectedBarang?.nama_barang }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Total Vendor
                                </p>

                                <p class="font-semibold text-blue-600">
                                    {{ vendorList.length }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-semibold">Daftar Vendor</h3>
                                <p class="text-sm text-gray-500">
                                    Vendor yang terhubung dengan barang ini.
                                </p>
                            </div>

                            <Button
                                class="bg-blue-600 text-white"
                                @click="showAddVendor = !showAddVendor"
                            >
                                {{
                                    showAddVendor
                                        ? "Tutup Form"
                                        : "+ Tambah Vendor"
                                }}
                            </Button>
                        </div>

                        <div
                            v-if="showAddVendor"
                            class="mb-6 rounded-lg border bg-gray-50 p-5"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Vendor
                                    </label>

                                    <SearchSelect
                                        v-model="vendorForm.kode_vendor"
                                        :options="vendorMaster"
                                        value-key="kode_vendor"
                                        label-key="nama_vendor"
                                        placeholder="Cari Vendor..."
                                    />

                                    <div
                                        v-if="vendorForm.errors.kode_vendor"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ vendorForm.errors.kode_vendor }}
                                    </div>
                                </div>

                                <div class="flex items-end">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            v-model="vendorForm.active"
                                        />

                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="mt-5 flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    @click="showAddVendor = false"
                                >
                                    Batal
                                </Button>

                                <Button
                                    class="bg-green-600 text-white"
                                    @click="saveVendor"
                                >
                                    Simpan Vendor
                                </Button>
                            </div>
                        </div>

                        <div class="rounded-lg border overflow-hidden">
                            <Table>
                                <TableHeader class="bg-gray-100">
                                    <TableRow>
                                        <TableHead class="w-16"> No </TableHead>

                                        <TableHead> Kode Vendor </TableHead>

                                        <TableHead> Nama Vendor </TableHead>

                                        <TableHead> Status </TableHead>

                                        <TableHead class="text-center">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-if="vendorList.length === 0">
                                        <TableCell
                                            colspan="5"
                                            class="py-8 text-center text-gray-400"
                                        >
                                            Belum ada vendor.
                                        </TableCell>
                                    </TableRow>

                                    <TableRow
                                        v-for="(v, index) in vendorList"
                                        :key="v.id_barang_vendor"
                                    >
                                        <TableCell>
                                            {{ index + 1 }}
                                        </TableCell>

                                        <TableCell>
                                            {{ v.kode_vendor }}
                                        </TableCell>

                                        <TableCell>
                                            {{ v.vendor?.nama_vendor }}
                                        </TableCell>

                                        <TableCell>
                                            <span
                                                v-if="v.active"
                                                class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"
                                            >
                                                Aktif
                                            </span>

                                            <span
                                                v-else
                                                class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
                                            >
                                                Non Aktif
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <div
                                                class="flex justify-center gap-2"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    @click="editVendor(v)"
                                                >
                                                    Edit
                                                </Button>

                                                <Button
                                                    size="sm"
                                                    class="bg-red-600 text-white"
                                                    @click="
                                                        confirmDeleteVendor(v)
                                                    "
                                                >
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
                <div
                    v-if="showDeleteVendorModal"
                    class="fixed inset-0 bg-black/50 flex items-center justify-center z-[999]"
                >
                    <div class="bg-white rounded-lg p-6 w-full max-w-sm">
                        <h2 class="font-bold text-lg">Hapus Vendor</h2>

                        <p class="mt-2">Yakin ingin menghapus vendor ini?</p>

                        <div class="flex justify-end gap-2 mt-5">
                            <Button
                                variant="outline"
                                @click="showDeleteVendorModal = false"
                            >
                                Batal
                            </Button>

                            <Button
                                class="bg-red-600 text-white"
                                @click="destroyVendor"
                            >
                                Hapus
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
