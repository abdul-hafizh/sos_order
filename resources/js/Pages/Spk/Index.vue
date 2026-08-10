<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Search,
    Package,
    CheckCircle,
    Truck,
    ClipboardList,
    Eye,
    X,
    RefreshCcw,
} from 'lucide-vue-next';

const props = defineProps({
    spks: Object,
    filters: Object,
});

const selectedSpk = ref(null);
const showDetail = ref(false);

const showMasterModal = ref(false);
const masterSpk = ref(null);

const masterForm = ref({
    kode_barang: '',
    nama_barang: '',
    harga_beli: 0,
    harga_jual: 0,
    satuan: '',
    stok: 0,
});

const openMasterBarang = (spk) => {
    masterSpk.value = spk;

    const randomNumbers = Math.floor(1000000 + Math.random() * 9000000);
    const otomatisKodeBarang = `R${randomNumbers}`;

    masterForm.value = {
        kode_barang: otomatisKodeBarang,
        nama_barang: spk.nama_barang || '',
        harga_beli: spk.harga_beli || 0,
        harga_jual: spk.harga_jual || 0,
        satuan: spk.satuan || '',
        stok: 0,
    };

    showMasterModal.value = true;
};

const params = ref({
    search: props.filters?.search || '',
    status_validasi: props.filters?.status_validasi || '',
    status_kirim_barang: props.filters?.status_kirim_barang || '',
    status_terima_barang: props.filters?.status_terima_barang || '',
    per_page: props.filters?.per_page || 10,
});

watch(params, (value) => {
    router.get(route('spk.index'), value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, { deep: true });

const rows = computed(() => props.spks?.data || []);

const totalSpk = computed(() => props.spks?.total || 0);

const belumValidasi = computed(() =>
    rows.value.filter((item) => Number(item.status_validasi) === 0).length
);

const sudahKirim = computed(() =>
    rows.value.filter((item) => Number(item.status_kirim_barang) === 1).length
);

const sudahTerima = computed(() =>
    rows.value.filter((item) => Number(item.status_terima_barang) === 1).length
);

const resetFilter = () => {
    params.value = {
        search: '',
        status_validasi: '',
        status_kirim_barang: '',
        status_terima_barang: '',
        per_page: 10,
    };
};

const openDetail = (spk) => {
    selectedSpk.value = spk;
    showDetail.value = true;
};

const closeDetail = () => {
    selectedSpk.value = null;
    showDetail.value = false;
};

const rupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDate = (value) => {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const isBarangBaru = (spk) => {
    return !spk.kode_barang || spk.keterangan === 'Permintaan barang baru';
};

const badgeClass = (status) => {
    return Number(status) === 1
        ? 'bg-green-100 text-green-700 border-green-200'
        : 'bg-red-100 text-red-700 border-red-200';
};

const statusText = (status, labelDone = 'Sudah', labelPending = 'Belum') => {
    return Number(status) === 1 ? labelDone : labelPending;
};

const updateValidasi = (spk) => {
    const nextStatus = Number(spk.status_validasi) === 1 ? 0 : 1;

    if (!confirm(
        `Apakah Anda yakin ingin mengubah status validasi menjadi ${
            nextStatus ? 'Sudah Validasi' : 'Belum Validasi'
        }?`
    )) {
        return;
    }

    router.put(route('spk.updateValidasi', spk.id_po), {
        status_validasi: nextStatus,
        keterangan: spk.keterangan,
    }, {
        preserveScroll: true,
    });
};

const updateKirim = (spk) => {
    const nextStatus = Number(spk.status_kirim_barang) === 1 ? 0 : 1;

    if (!confirm(
        `Apakah Anda yakin ingin mengubah status pengiriman menjadi ${
            nextStatus ? 'Sudah Dikirim' : 'Belum Dikirim'
        }?`
    )) {
        return;
    }

    router.put(route('spk.updateKirim', spk.id_po), {
        status_kirim_barang: nextStatus,
        keterangan: spk.keterangan,
    }, {
        preserveScroll: true,
    });
};

const closeMasterBarang = () => {
    showMasterModal.value = false;
    masterSpk.value = null;
};

const getSpkImages = (spk) => {
    if (spk.gambars?.length) {
        return spk.gambars
            .filter((img) => img.gambar)
            .map((img) => `/storage/${img.gambar}`);
    }

    if (spk.gambar_permintaan) {
        return [`/storage/${spk.gambar_permintaan}`];
    }

    const images = spk.barang?.produk?.gambars || [];

    return images
        .filter((img) => img.path_file)
        .map((img) => `/storage/${img.path_file}`);
};

const toggleKetersediaan = (spk) => {
    const nextStatus = Number(spk.is_available) === 1 ? 0 : 1;
    const statusLabel = nextStatus === 1 ? 'Tersedia' : 'Tidak Tersedia';

    if (!confirm(`Apakah Anda yakin ingin menandai barang ini sebagai "${statusLabel}"?`)) {
        return;
    }

    router.put(route('spk.updateKetersediaan', spk.id_po), {
        is_available: nextStatus,
    }, {
        preserveScroll: true,
    });
};

const updateTerima = (spk) => {
    const nextStatus = Number(spk.status_terima_barang) === 1 ? 0 : 1;

    if (!confirm(
        `Apakah Anda yakin ingin mengubah status penerimaan menjadi ${
            nextStatus ? 'Sudah Diterima' : 'Belum Diterima'
        }?`
    )) {
        return;
    }

    router.put(route('spk.updateTerima', spk.id_po), {
        status_terima_barang: nextStatus,
        qty_cabang_terima: nextStatus === 1 ? spk.qty : spk.qty_cabang_terima,
        keterangan: spk.keterangan,
    }, {
        preserveScroll: true,
    });
};

const changeKetersediaan = (spk, statusBaru) => {
    let label = 'Menunggu Verifikasi';
    if (statusBaru === 1) label = 'Tersedia';
    if (statusBaru === 2) label = 'Tidak Tersedia';

    if (!confirm(`Ubah status ketersediaan barang menjadi "${label}"?`)) {
        return;
    }

    router.put(route('spk.updateKetersediaan', spk.id_po), {
        is_available: statusBaru,
    }, {
        preserveScroll: true,
    });
};

const submitMasterBarang = () => {
    if (!masterSpk.value) return;

    if (!confirm('Apakah Anda yakin ingin membuat master barang dari SPK ini?')) {
        return;
    }

    router.post(route('spk.buatMasterBarang', masterSpk.value.id_po), masterForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeMasterBarang();
        },
        onError: (errors) => {
            console.log(errors);
            alert('Gagal membuat master barang. Cek kode barang atau field wajib.');
        },
    });
};

</script>

<template>
    <Head title="SPK" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    SPK
                </h2>
                <p class="text-sm text-gray-400">
                    Surat Permintaan Barang dari keranjang user.
                </p>
            </div>
        </template>

        <div class="py-7 px-6 w-full">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Total SPK</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ totalSpk }}</h3>
                        </div>
                        <Package class="w-9 h-9 text-blue-700" />
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Belum Validasi</p>
                            <h3 class="text-2xl font-bold text-red-600">{{ belumValidasi }}</h3>
                        </div>
                        <ClipboardList class="w-9 h-9 text-red-500" />
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Sudah Dikirim</p>
                            <h3 class="text-2xl font-bold text-green-600">{{ sudahKirim }}</h3>
                        </div>
                        <Truck class="w-9 h-9 text-green-600" />
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Sudah Diterima</p>
                            <h3 class="text-2xl font-bold text-indigo-600">{{ sudahTerima }}</h3>
                        </div>
                        <CheckCircle class="w-9 h-9 text-indigo-600" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2 relative">
                        <Search class="w-5 h-5 absolute left-4 top-3.5 text-gray-400" />
                        <Input
                            v-model="params.search"
                            class="pl-11 h-12 rounded-2xl"
                            placeholder="Cari barang, kode, cabang..."
                        />
                    </div>

                    <select v-model="params.status_validasi" class="h-12 rounded-2xl border-gray-200">
                        <option value="">Semua Validasi</option>
                        <option value="0">Belum Validasi</option>
                        <option value="1">Sudah Validasi</option>
                    </select>

                    <select v-model="params.status_kirim_barang" class="h-12 rounded-2xl border-gray-200">
                        <option value="">Semua Kirim</option>
                        <option value="0">Belum Dikirim</option>
                        <option value="1">Sudah Dikirim</option>
                    </select>

                    <div class="flex gap-2">
                        <select v-model="params.per_page" class="h-12 rounded-2xl border-gray-200 w-full">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>

                        <Button type="button" variant="outline" class="h-12 rounded-2xl" @click="resetFilter">
                            <RefreshCcw class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="rows.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="spk in rows"
                    :key="spk.id_po"
                    class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition p-5 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="w-16 h-16 rounded-2xl overflow-hidden border bg-gray-50 flex items-center justify-center shrink-0 shadow-sm">
                                <img
                                    v-if="getSpkImages(spk).length"
                                    :src="getSpkImages(spk)[0]"
                                    class="w-full h-full object-cover"
                                    alt="Gambar Produk"
                                />
                                <Package v-else class="w-6 h-6 text-gray-400" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                    <span class="text-[10px] font-semibold bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full">
                                        PO #{{ spk.id_po }}
                                    </span>

                                    <span
                                        v-if="isBarangBaru(spk)"
                                        class="text-[10px] font-semibold bg-orange-100 text-orange-700 px-2.5 py-0.5 rounded-full"
                                    >
                                        Barang Baru
                                    </span>
                                </div>

                                <h3 class="font-bold text-gray-900 text-sm leading-snug truncate">
                                    {{ spk.nama_barang }}
                                </h3>

                                <p class="text-xs text-gray-400 mt-0.5">
                                    <span v-if="spk.kode_barang" class="text-blue-700 font-semibold">
                                        {{ spk.kode_barang }}
                                    </span>
                                    <span v-else class="text-orange-600 font-semibold">
                                        Belum ada kode barang
                                    </span>
                                </p>
                            </div>

                            <Button type="button" variant="outline" class="rounded-xl p-2 h-9 w-9 shrink-0" @click="openDetail(spk)">
                                <Eye class="w-4 h-4" />
                            </Button>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 text-xs mt-3">
                            <div class="bg-gray-50 rounded-xl p-2.5">
                                <p class="text-gray-400">Cabang</p>
                                <p class="font-semibold text-gray-900 truncate" :title="spk.cabang?.cabang_nama">
                                    {{ spk.kode_cabang }} 
                                    <span v-if="spk.cabang?.cabang_nama" class="text-gray-500 font-normal">
                                        - {{ spk.cabang.cabang_nama }}
                                    </span>
                                </p>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-2.5">
                                <p class="text-gray-400">Qty</p>
                                <p class="font-semibold text-gray-900">{{ spk.qty }} {{ spk.satuan || '' }}</p>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-2.5">
                                <p class="text-gray-400">Harga Beli</p>
                                <p class="font-semibold text-gray-900">{{ rupiah(spk.harga_beli) }}</p>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-2.5">
                                <p class="text-gray-400">Tanggal</p>
                                <p class="font-semibold text-gray-900">{{ formatDate(spk.modified_date) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 mt-4 pt-4 border-t border-gray-100">
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-semibold text-gray-700">Ketersediaan Barang</span>
                                
                                <span 
                                    class="text-[11px] font-medium border px-2.5 py-0.5 rounded-full"
                                    :class="{
                                        'bg-yellow-50 text-yellow-700 border-yellow-200': !spk.is_available || Number(spk.is_available) === 0,
                                        'bg-green-50 text-green-700 border-green-200': Number(spk.is_available) === 1,
                                        'bg-red-50 text-red-700 border-red-200': Number(spk.is_available) === 2
                                    }"
                                >
                                    {{ 
                                        !spk.is_available || Number(spk.is_available) === 0 
                                            ? 'Menunggu Verifikasi' 
                                            : (Number(spk.is_available) === 1 ? 'Tersedia' : 'Tidak Tersedia') 
                                    }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-1.5 mt-1">
                                <button
                                    type="button"
                                    class="py-1.5 text-[11px] font-medium rounded-lg border text-center transition"
                                    :class="(!spk.is_available || Number(spk.is_available) === 0) ? 'bg-yellow-600 text-white border-yellow-600' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    @click="changeKetersediaan(spk, 0)"
                                >
                                    Tunggu
                                </button>
                                <button
                                    type="button"
                                    class="py-1.5 text-[11px] font-medium rounded-lg border text-center transition"
                                    :class="Number(spk.is_available) === 1 ? 'bg-green-600 text-white border-green-600' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    @click="changeKetersediaan(spk, 1)"
                                >
                                    Tersedia
                                </button>
                                <button
                                    type="button"
                                    class="py-1.5 text-[11px] font-medium rounded-lg border text-center transition"
                                    :class="Number(spk.is_available) === 2 ? 'bg-red-600 text-white border-red-600' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    @click="changeKetersediaan(spk, 2)"
                                >
                                    Tidak Ada
                                </button>
                            </div>
                        </div>

                        <Button
                            v-if="!spk.kode_barang"
                            type="button"
                            class="w-full bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-xs h-9 mt-1"
                            @click="openMasterBarang(spk)"
                        >
                            <Plus class="w-4 h-4 mr-1.5" />
                            Buat Master Barang
                        </Button>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-3xl border border-gray-100 shadow-sm p-12 text-center">
                <Package class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <h3 class="font-bold text-gray-700">Data SPK belum ada</h3>
                <p class="text-sm text-gray-400 mt-2">Belum ada pesanan yang masuk ke SPK.</p>
            </div>

            <div v-if="spks?.links?.length" class="mt-8 flex flex-wrap gap-2 justify-center">
                <Link
                    v-for="(link, index) in spks.links"
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

        <div v-if="showDetail && selectedSpk" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
                <div class="p-5 border-b flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">
                            Detail SPK #{{ selectedSpk.id_po }}
                        </h3>
                        <p class="text-sm text-gray-400">
                            {{ selectedSpk.nama_barang }}
                        </p>
                    </div>

                    <button type="button" class="p-2 rounded-full hover:bg-gray-100" @click="closeDetail">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 flex flex-col md:flex-row gap-5 text-sm">
                    <!-- Gambar Produk: kiri atas -->
                    <div class="w-full md:w-56 shrink-0 space-y-2">
                        <div class="w-full aspect-square rounded-2xl overflow-hidden border bg-gray-50 flex items-center justify-center">
                            <img
                                v-if="getSpkImages(selectedSpk).length"
                                :src="getSpkImages(selectedSpk)[0]"
                                class="w-full h-full object-cover"
                                alt="Gambar Produk"
                            />
                            <Package v-else class="w-10 h-10 text-gray-300" />
                        </div>

                        <div
                            v-if="getSpkImages(selectedSpk).length > 1"
                            class="grid grid-cols-4 gap-2"
                        >
                            <img
                                v-for="(image, index) in getSpkImages(selectedSpk).slice(1, 5)"
                                :key="index"
                                :src="image"
                                class="w-full aspect-square object-cover rounded-lg border"
                            />
                        </div>
                    </div>

                    <!-- Info: kanan, bertumpuk ke bawah -->
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Kode Barang</span>
                            <span class="font-semibold text-right">{{ selectedSpk.kode_barang || '-' }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Kode Cabang</span>
                            <span class="font-semibold text-right">{{ selectedSpk.kode_cabang || '-' }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Qty</span>
                            <span class="font-semibold text-right">{{ selectedSpk.qty }} {{ selectedSpk.satuan || '' }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Qty Diterima</span>
                            <span class="font-semibold text-right">{{ selectedSpk.qty_cabang_terima || 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Harga Beli</span>
                            <span class="font-semibold text-right">{{ rupiah(selectedSpk.harga_beli) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Harga Jual</span>
                            <span class="font-semibold text-right">{{ rupiah(selectedSpk.harga_jual) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Tanggal Validasi</span>
                            <span class="font-semibold text-right">{{ formatDate(selectedSpk.tgl_validasi) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Tanggal Kirim</span>
                            <span class="font-semibold text-right">{{ formatDate(selectedSpk.tgl_kirim_barang) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-gray-400">Tanggal Terima</span>
                            <span class="font-semibold text-right">{{ formatDate(selectedSpk.tgl_terima_barang) }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3 pt-3 border-t border-gray-100">
                            <span class="text-gray-400 shrink-0">Keterangan</span>
                            <span class="font-semibold text-right">{{ selectedSpk.keterangan || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t">
                    <Button type="button" class="w-full rounded-2xl" @click="closeDetail">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>

        <div
            v-if="showMasterModal && masterSpk"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden">
                <div class="p-5 border-b flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">
                            Buat Master Barang
                        </h3>
                        <p class="text-sm text-gray-400">
                            Dari SPK #{{ masterSpk.id_po }}
                        </p>
                    </div>

                    <button type="button" class="p-2 rounded-full hover:bg-gray-100" @click="closeMasterBarang">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitMasterBarang" class="p-5">
                    <div class="mb-5">
                        <p class="text-sm text-gray-400 mb-2">Gambar dari SPK</p>

                        <div v-if="getSpkImages(masterSpk).length" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <img
                                v-for="(image, index) in getSpkImages(masterSpk)"
                                :key="index"
                                :src="image"
                                class="w-full h-28 object-cover rounded-2xl border"
                            />
                        </div>

                        <p v-else class="text-sm text-gray-400">
                            Tidak ada gambar.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Kode Barang</label>
                            <Input v-model="masterForm.kode_barang" class="mt-1 rounded-xl" required />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Nama Barang</label>
                            <Input v-model="masterForm.nama_barang" class="mt-1 rounded-xl" required />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Harga Beli</label>
                            <Input v-model="masterForm.harga_beli" type="number" class="mt-1 rounded-xl" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Harga Jual</label>
                            <Input v-model="masterForm.harga_jual" type="number" class="mt-1 rounded-xl" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Satuan</label>
                            <Input v-model="masterForm.satuan" class="mt-1 rounded-xl" placeholder="pcs, box, rim" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Stok Awal</label>
                            <Input v-model="masterForm.stok" type="number" class="mt-1 rounded-xl" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-5 border-t">
                        <Button type="button" variant="outline" @click="closeMasterBarang">
                            Batal
                        </Button>

                        <Button type="submit" class="bg-blue-700 text-white">
                            Simpan Master Barang
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>