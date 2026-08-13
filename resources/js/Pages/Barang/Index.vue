<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useForm, router, Link, Head } from "@inertiajs/vue3";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import SearchSelect from "@/Components/SearchSelect.vue";
import {
    Package,
    Pencil,
    Plus,
    X,
    Search,
    Layers,
    ShoppingBag,
    CirclePile,
    ArrowUp,
    ArrowDown,
    Minus,
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
});

const showModal = ref(false);
const editingBarang = ref(null);
const activeTab = ref("produk");

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

const form = useForm({
    nama_barang: "",
    harga_beli: 0,
    harga_beli_before: 0,
    harga_jual: 0,
    harga_jual_before: 0,
    harga_jual_jumbo: 0,
    harga_jual_jumbo_before: 0,
    satuan: "",
    stok: 0,
    qty_pos: 0,
    min_stok: 0,
    max_stok: 0,
    category_code: "",
    variants: [emptyVariant()],
});

const categoryMaster = ref([]);
const categoryKeyword = ref("");
const showCategoryDropdown = ref(false);

const loadCategory = async () => {
    const res = await axios.get(route("category.list"));
    categoryMaster.value = res.data;
};

onMounted(() => {
    loadCategory();
});

const filteredCategory = computed(() => {
    if (!categoryKeyword.value) return categoryMaster.value;

    return categoryMaster.value.filter(
        (item) =>
            item.categoryname
                .toLowerCase()
                .includes(categoryKeyword.value.toLowerCase()) ||
            item.categorycode
                .toLowerCase()
                .includes(categoryKeyword.value.toLowerCase()),
    );
});

const selectCategory = (item) => {
    form.category_code = item.categorycode;
    categoryKeyword.value = item.categoryname;
    showCategoryDropdown.value = false;
};

const rupiah = (value) => {
    if (!value) return "Rp 0";

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(value);
};

const openModal = (item = null) => {
    editingBarang.value = item;
    activeTab.value = "produk";

    if (item) {
        form.nama_barang = item.nama_barang;
        form.harga_beli = item.harga_beli ?? 0;
        form.harga_beli_before = item.harga_beli_before ?? 0;
        form.harga_jual = item.harga_jual ?? 0;
        form.harga_jual_before = item.harga_jual_before ?? 0;
        form.harga_jual_jumbo = item.harga_jual_jumbo ?? 0;
        form.harga_jual_jumbo_before = item.harga_jual_jumbo_before ?? 0;
        form.satuan = item.satuan ?? "";
        form.stok = item.stok ?? 0;
        form.qty_pos = item.qty_pos ?? 0;
        form.min_stok = item.min_stok ?? 0;
        form.max_stok = item.max_stok ?? 0;
        form.category_code = item.category_code ?? "";

        categoryKeyword.value = item.category?.categoryname || "";

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
    } else {
        form.reset();
        form.variants = [emptyVariant()];
        categoryKeyword.value = "";
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

const submit = () => {
    if (editingBarang.value) {
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
                    showModal.value = false;
                },
            },
        );
    } else {
        form.post(route("barang.store"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
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
                class="rounded-3xl bg-gradient-to-r from-blue-700 to-indigo-700 p-6 text-white shadow-lg mb-6"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl bg-white/20 p-4">
                            <ShoppingBag class="w-9 h-9" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Katalog Barang</h1>
                            <p class="text-sm text-blue-100">
                                Kelola produk seperti ecommerce: gambar, harga,
                                stok, dan banyak varian.
                            </p>
                        </div>
                    </div>

                    <Button
                        class="bg-white text-blue-700 hover:bg-blue-50"
                        @click="openModal()"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        Tambah Barang
                    </Button>
                </div>
            </div>

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
                            <h2 class="text-xl font-bold">
                                {{
                                    editingBarang
                                        ? "Edit Barang"
                                        : "Tambah Barang"
                                }}
                            </h2>
                            <p class="text-sm text-gray-400">
                                Lengkapi data produk.
                            </p>
                        </div>

                        <button
                            @click="showModal = false"
                            class="p-2 rounded-full hover:bg-gray-100"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form
                        @submit.prevent="submit"
                        class="overflow-y-auto max-h-[72vh] p-6"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="relative">
                                <label class="text-sm font-medium text-gray-600"
                                    >Kategori</label
                                >
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
                                        <div class="font-medium">
                                            {{ item.categoryname }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ item.categorycode }}
                                        </div>
                                    </div>

                                    <div
                                        v-if="filteredCategory.length === 0"
                                        class="px-3 py-2 text-gray-400 text-sm"
                                    >
                                        Tidak ada kategori ditemukan
                                    </div>
                                </div>

                                <p
                                    v-if="form.errors.category_code"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ form.errors.category_code }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Satuan</label
                                >
                                <SearchSelect
                                    v-model="form.satuan"
                                    :options="list_satuan"
                                    value-key="nama"
                                    label-key="nama"
                                    placeholder="Pilih Satuan..."
                                />
                                <p
                                    v-if="form.errors.satuan"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ form.errors.satuan }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Nama Barang</label
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
                                    >Harga Beli</label
                                >
                                <Input
                                    v-model="form.harga_beli"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Beli Sebelumnya</label
                                >
                                <Input
                                    v-model="form.harga_beli_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual</label
                                >
                                <Input
                                    v-model="form.harga_jual"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Sebelumnya</label
                                >
                                <Input
                                    v-model="form.harga_jual_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Jumbo</label
                                >
                                <Input
                                    v-model="form.harga_jual_jumbo"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Harga Jual Jumbo Sebelumnya</label
                                >
                                <Input
                                    v-model="form.harga_jual_jumbo_before"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Stok Utama</label
                                >
                                <Input
                                    v-model="form.stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Qty POS</label
                                >
                                <Input
                                    v-model="form.qty_pos"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Min Stok</label
                                >
                                <Input
                                    v-model="form.min_stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Max Stok</label
                                >
                                <Input
                                    v-model="form.max_stok"
                                    type="number"
                                    class="mt-1 rounded-xl"
                                />
                            </div>
                        </div>

                        <div
                            class="flex justify-end gap-3 mt-6 pt-5 border-t sticky bottom-0 bg-white"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="showModal = false"
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                class="bg-blue-700 text-white"
                                :disabled="form.processing"
                            >
                                {{
                                    form.processing
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
