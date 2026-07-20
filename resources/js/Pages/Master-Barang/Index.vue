<script setup>
import { ref, computed, onMounted } from "vue";
import { useForm, router, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Package,
    Pencil,
    Trash,
    ArrowUp,
    ArrowDown,
    Minus,
    CirclePile,
} from "lucide-vue-next";
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from "@/Components/ui/item";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/Components/ui/tooltip";
import SearchSelect from "@/Components/SearchSelect.vue";

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

const props = defineProps({ barangs: Object, filters: Object });

const showModal = ref(false);
const activeTab = ref("umum");
const editingBarang = ref(null);
const showDeleteModal = ref(false);
const barangToDelete = ref(null);

const params = ref({
    search: props.filters.search || "",
    per_page: props.filters.per_page || 10,
});

const searchData = () => {
    router.get(
        route("master-barang.index"),
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

    router.get(
        route("master-barang.index"),
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
        route("master-barang.index"),
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

const confirmDelete = (item) => {
    barangToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyBarang = () => {
    router.delete(
        route("master-barang.destroy", barangToDelete.value.id_barang),
        {
            onSuccess: () => {
                showDeleteModal.value = false;
                barangToDelete.value = null;
            },
        },
    );
};

const form = useForm({
    kode_barang: "",
    nama_barang: "",

    harga_beli: 0,
    harga_beli_before: 0,

    harga_jual: 0,
    harga_jual_before: 0,

    harga_jual_jumbo: 0,
    harga_jual_jumbo_before: 0,

    margin: 0,

    satuan: "",
    satuan_pos: "",

    qty_pos: 0,

    stok: 0,

    min_vendor: 0,
    min_cabang: 0,
    min_stok: 0,
    max_stok: 0,

    kirim_langsung: false,
    active: true,

    category_code: "",
});

const getTrend = (current, before) => {
    if (current > before) return { icon: ArrowUp, color: "text-green-600" };
    if (current < before) return { icon: ArrowDown, color: "text-red-600" };
    return { icon: Minus, color: "text-gray-400" };
};

const currency = (value) => {
    return new Intl.NumberFormat("id-ID").format(value ?? 0);
};

const openModal = (item = null) => {
    editingBarang.value = item;

    if (item) {
        form.defaults(item);
        form.reset();

        const category = categoryMaster.value.find(
            (c) => c.code === item.category_code,
        );

        categoryKeyword.value = category ? category.name : "";
    } else {
        form.reset();

        categoryKeyword.value = "";
    }

    showCategoryDropdown.value = false;

    showModal.value = true;
};

const submit = () => {
    if (editingBarang.value) {
        form.put(route("master-barang.update", editingBarang.value.id_barang), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        if (form.harga_jual < form.harga_beli) {
            alert("Harga jual tidak boleh lebih kecil dari harga beli");
            return;
        }

        if (form.harga_jual_jumbo && form.harga_jual_jumbo < form.harga_beli) {
            alert("Harga jual jumbo tidak boleh lebih kecil dari harga beli");
            return;
        }

        form.post(route("master-barang.store"), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const categoryMaster = ref([]);
const categoryKeyword = ref("");
const categorySearch = ref("");
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
            item.name
                .toLowerCase()
                .includes(categoryKeyword.value.toLowerCase()) ||
            item.code
                .toLowerCase()
                .includes(categoryKeyword.value.toLowerCase()),
    );
});

const selectCategory = (item) => {
    form.category_code = item.code;

    categoryKeyword.value = item.name;

    showCategoryDropdown.value = false;
};
</script>
x
<template>
    <Head title="Barang" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight"
                    >Barang</label
                >
                <p class="text-sm text-gray-400">Master | Barang</p>
            </div>
        </template>

        <div
            class="py-7 w-full px-6 bg-white border border-gray-200 rounded-2xl shadow-md"
        >
            <div class="mb-5">
                <Item
                    variant="outline"
                    class="py-3 gap-3 flex-col md:flex-row items-center"
                >
                    <ItemMedia
                        class="bg-slate-500 p-3 rounded-md shadow-sm shrink-0"
                    >
                        <Package class="text-white w-7 h-7" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold"
                            >Data Barang</ItemTitle
                        >
                        <ItemDescription class="text-sm"
                            >Kelola stok, harga, dan informasi barang secara
                            terpusat.
                        </ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button
                            class="w-full md:w-auto bg-blue-700 text-white"
                            @click="openModal()"
                            >+ Tambah Barang</Button
                        >
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select
                    v-model="params.per_page"
                    @change="changePerPage"
                    class="border-gray-300 rounded-md text-xs bg-white"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <div class="relative max-w-xs">
                    <Input
                        v-model="params.search"
                        @keyup.enter="searchData"
                        placeholder="Cari produk..."
                        class="pr-10 border-gray-300 rounded-md"
                    />

                    <button
                        v-if="params.search"
                        type="button"
                        @click="resetSearch"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div class="rounded-md border bg-white">
                <Table>
                    <TableHeader class="bg-gray-100">
                        <TableRow>
                            <TableHead class="w-[120px]"
                                >Id & <br />
                                Kode Kategori</TableHead
                            >
                            <TableHead>Barang</TableHead>
                            <TableHead class="w-[220px]">Harga</TableHead>
                            <TableHead class="w-[180px]">Stok</TableHead>
                            <TableHead class="w-[180px]">Minimum</TableHead>
                            <TableHead class="w-[170px] text-center"
                                >Action</TableHead
                            >
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow
                            v-for="b in barangs.data"
                            :key="b.id_barang"
                            class="align-top"
                        >
                            <TableCell>
                                <div class="font-semibold">
                                    {{ b.kode_barang }}
                                </div>

                                <div
                                    class="text-xs mt-1 inline-flex px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"
                                >
                                    {{ b.category_code || "-" }}
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="font-medium">
                                    {{ b.nama_barang }}
                                </div>

                                <div class="text-xs text-gray-500 mt-1">
                                    <div>
                                        <span class="font-medium"
                                            >Satuan :</span
                                        >
                                        {{ b.satuan }}
                                    </div>

                                    <div>
                                        <span class="font-medium">POS :</span>
                                        {{ b.satuan_pos || "-" }}
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="space-y-1 text-xs">
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <span class="text-gray-500">Beli</span>

                                        <div class="flex items-center gap-1">
                                            {{ currency(b.harga_beli) }}

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
                                                class="w-3 h-3"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <span class="text-gray-500">Jual</span>

                                        <div class="flex items-center gap-1">
                                            {{ currency(b.harga_jual) }}

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
                                                class="w-3 h-3"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-gray-500">HOP</span>
                                        <span>{{
                                            currency(b.harga_jual_jumbo)
                                        }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-gray-500"
                                            >Margin</span
                                        >
                                        <span>{{ b.margin }}%</span>
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="font-semibold text-lg">
                                    {{ b.stok }}
                                </div>

                                <div class="text-xs text-gray-500 mt-2">
                                    <div>
                                        Qty POS :
                                        <span class="font-medium">
                                            {{ b.qty_pos }}
                                        </span>
                                    </div>

                                    <div>
                                        Status :
                                        <span
                                            :class="
                                                b.active
                                                    ? 'text-green-600'
                                                    : 'text-red-600'
                                            "
                                            class="font-medium"
                                        >
                                            {{
                                                b.active ? "Aktif" : "Non Aktif"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="text-xs space-y-1">
                                    <div class="flex justify-between">
                                        <span>Vendor</span>
                                        <span>{{ b.min_vendor }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Cabang</span>
                                        <span>{{ b.min_cabang }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Min Stok</span>
                                        <span>{{ b.min_stok }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span>Max Stok</span>
                                        <span>{{ b.max_stok }}</span>
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="flex flex-col items-center gap-2">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    size="sm"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow shadow-md"
                                                    @click="openModal(b)"
                                                >
                                                    <Pencil class="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>

                                            <TooltipContent>
                                                Edit Barang
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    size="sm"
                                                    class="bg-green-600 hover:bg-green-700 text-white rounded-md shadow shadow-md"
                                                    @click="openVendorModal(b)"
                                                >
                                                   <CirclePile class="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>

                                            <TooltipContent>
                                                Kelola Vendor
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    size="sm"
                                                    class="bg-red-600 hover:bg-red-700 text-white rounded-md shadow shadow-md"
                                                    @click="confirmDelete(b)"
                                                >
                                                    <Trash class="w-4 h-4" />
                                                </Button>
                                            </TooltipTrigger>

                                            <TooltipContent>
                                                Hapus Barang
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div
                class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center"
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
                        class="px-3"
                        v-html="link.label"
                    ></Button>
                </Link>
            </div>

            <div
                v-if="showModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            >
                <div class="bg-white p-6 rounded-lg w-full max-w-lg shadow-xl">
                    <h2 class="font-bold mb-4 border-b pb-2">
                        Form Data Barang
                    </h2>
                    <div class="flex gap-4 mb-4 border-b text-sm">
                        <button
                            @click="activeTab = 'umum'"
                            :class="{
                                'border-b-2 border-blue-600 font-bold':
                                    activeTab === 'umum',
                            }"
                        >
                            Umum
                        </button>
                        <button
                            @click="activeTab = 'harga'"
                            :class="{
                                'border-b-2 border-blue-600 font-bold':
                                    activeTab === 'harga',
                            }"
                        >
                            Harga
                        </button>
                        <button
                            @click="activeTab = 'stok'"
                            :class="{
                                'border-b-2 border-blue-600 font-bold':
                                    activeTab === 'stok',
                            }"
                        >
                            Stok
                        </button>
                        <button
                            @click="activeTab = 'lainnya'"
                            :class="{
                                'border-b-2 border-blue-600 font-bold':
                                    activeTab === 'lainnya',
                            }"
                        >
                            Lainnya
                        </button>
                    </div>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div
                            v-if="activeTab === 'umum'"
                            class="grid grid-cols-1 gap-3"
                        >
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Kode Barang</label
                                >
                                <Input
                                    v-model="form.kode_barang"
                                    placeholder="Kode Barang"
                                    required
                                />
                                <p
                                    v-if="form.errors.kode_barang"
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.kode_barang }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Nama Barang</label
                                >
                                <Input
                                    v-model="form.nama_barang"
                                    placeholder="Nama Barang"
                                    required
                                />
                                <p
                                    v-if="form.errors.nama_barang"
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.nama_barang }}
                                </p>
                            </div>
                            <div class="col-span-2 relative">
                                <label
                                    class="text-xs text-gray-400 font-medium"
                                >
                                    Kategori
                                </label>

                                <Input
                                    v-model="categoryKeyword"
                                    placeholder="Cari kategori..."
                                    @focus="showCategoryDropdown = true"
                                    @input="showCategoryDropdown = true"
                                />

                                <div
                                    v-if="showCategoryDropdown"
                                    class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                >
                                    <div
                                        v-for="item in filteredCategory"
                                        :key="item.code"
                                        @click="selectCategory(item)"
                                        class="px-3 py-2 hover:bg-blue-100 cursor-pointer"
                                    >
                                        <div class="font-medium">
                                            {{ item.name }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            {{ item.code }}
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
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.category_code }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Satuan</label
                                >
                                <Input
                                    v-model="form.satuan"
                                    placeholder="Satuan"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Satuan POS</label
                                >
                                <Input
                                    v-model="form.satuan_pos"
                                    placeholder="Satuan"
                                />
                            </div>
                        </div>
                        <div
                            v-if="activeTab === 'harga'"
                            class="grid grid-cols-1 gap-3"
                        >
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Harga Beli</label
                                >
                                <Input
                                    v-model="form.harga_beli"
                                    type="number"
                                    placeholder="Harga Beli"
                                />
                                <p
                                    v-if="form.errors.harga_beli"
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.harga_beli }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Harga Jual</label
                                >
                                <Input
                                    v-model="form.harga_jual"
                                    type="number"
                                    placeholder="Harga Jual"
                                />
                                <p
                                    v-if="form.errors.harga_jual"
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.harga_jual }}
                                </p>
                            </div>

                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Harga Jual Jumbo</label
                                >
                                <Input
                                    v-model="form.harga_jual_jumbo"
                                    type="number"
                                    placeholder="Harga Jual Jumbo"
                                />
                                <p
                                    v-if="form.errors.harga_jual_jumbo"
                                    class="text-sm text-red-500 mt-1"
                                >
                                    {{ form.errors.harga_jual_jumbo }}
                                </p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Margin</label
                                >
                                <Input
                                    v-model="form.margin"
                                    type="number"
                                    placeholder="Margin"
                                />
                            </div>
                        </div>
                        <div
                            v-if="activeTab === 'stok'"
                            class="grid grid-cols-1 gap-3"
                        >
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >QTY POS</label
                                >
                                <Input
                                    v-model="form.qty_pos"
                                    type="number"
                                    placeholder="QTY POS"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Min Vendor</label
                                >
                                <Input
                                    v-model="form.min_vendor"
                                    type="number"
                                    placeholder="Min Vendor"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Min Cabang</label
                                >
                                <Input
                                    v-model="form.min_cabang"
                                    type="number"
                                    placeholder="Min Cabang"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Min Stok</label
                                >
                                <Input
                                    v-model="form.min_stok"
                                    type="number"
                                    placeholder="Min Stok"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs text-gray-400 font-medium"
                                    >Max Stok</label
                                >
                                <Input
                                    v-model="form.max_stok"
                                    type="number"
                                    placeholder="Max Stok"
                                />
                            </div>
                        </div>
                        <div
                            v-if="activeTab === 'lainnya'"
                            class="grid grid-cols-1 gap-3"
                        >
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.active" />
                                Active
                            </label>

                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="form.kirim_langsung"
                                />
                                Kirim Langsung
                            </label>
                        </div>
                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <Button
                                type="button"
                                variant="outline"
                                @click="showModal = false"
                                >Batal</Button
                            >
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-600 text-white"
                            >
                                {{
                                    form.processing
                                        ? "Menyimpan..."
                                        : "Simpan Data"
                                }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                v-if="showDeleteModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            >
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p>
                        Yakin ingin menghapus {{ barangToDelete?.nama_barang }}?
                    </p>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button
                            variant="outline"
                            @click="showDeleteModal = false"
                            >Batal</Button
                        >
                        <Button
                            class="bg-red-600 text-white rounded-sm"
                            @click="destroyBarang"
                            >Hapus</Button
                        >
                    </div>
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
