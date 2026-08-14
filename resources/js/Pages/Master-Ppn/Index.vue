<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { Percent, Pencil, Trash } from 'lucide-vue-next';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';

const props = defineProps({
    ppn: Object,
    filters: Object,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingPpn = ref(null);
const ppnToDelete = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

const form = useForm({
    kode_ppn: '',
    nama_ppn: '',
    persen_ppn: 0,
    effective_from: '',
    effective_to: '',
    active: true,
    keterangan: '',
});

watch(params, () => {
    router.get(route('master-ppn.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const toDateInputValue = (value) => (value ? value.slice(0, 10) : '');

const formatDate = (value) => {
    if (!value) return '-';

    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const openModal = (item = null) => {
    editingPpn.value = item;

    if (item) {
        form.kode_ppn = item.kode_ppn;
        form.nama_ppn = item.nama_ppn;
        form.persen_ppn = item.persen_ppn ?? 0;
        form.effective_from = toDateInputValue(item.effective_from);
        form.effective_to = toDateInputValue(item.effective_to);
        form.active = !!item.active;
        form.keterangan = item.keterangan ?? '';
    } else {
        form.reset();
        form.active = true;
    }

    showModal.value = true;
};

const submit = () => {
    if (editingPpn.value) {
        form.put(route('master-ppn.update', editingPpn.value.id_ppn), { onSuccess: () => closeModal() });
    } else {
        form.post(route('master-ppn.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (item) => {
    ppnToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyPpn = () => {
    router.delete(route('master-ppn.destroy', ppnToDelete.value.id_ppn), {
        onSuccess: () => {
            showDeleteModal.value = false;
            ppnToDelete.value = null;
        },
    });
};

const closeModal = () => {
    showModal.value = false;
    editingPpn.value = null;
    form.reset();
};
</script>

<template>
    <Head title="PPN" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">PPN</label>
                <p class="text-sm text-gray-400">Master Data | PPN</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Percent class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data PPN</ItemTitle>
                        <ItemDescription class="text-sm">Kelola daftar tarif PPN.</ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" @click="openModal()">+ Tambah PPN</Button>
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <Input v-model="params.search" placeholder="Cari PPN..." class="max-w-xs border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kode</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Persen</TableHead>
                            <TableHead>Berlaku</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in ppn.data" :key="item.id_ppn">
                            <TableCell>{{ item.kode_ppn }}</TableCell>
                            <TableCell>{{ item.nama_ppn }}</TableCell>
                            <TableCell>{{ item.persen_ppn }}%</TableCell>
                            <TableCell class="text-xs text-gray-500">
                                <span v-if="item.effective_from || item.effective_to">
                                    {{ formatDate(item.effective_from) }} s/d {{ formatDate(item.effective_to) }}
                                </span>
                                <span v-else>-</span>
                            </TableCell>
                            <TableCell>
                                <span
                                    class="text-[11px] font-medium border px-2.5 py-0.5 rounded-full"
                                    :class="item.active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                >
                                    {{ item.active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-center">
                                <div class="flex gap-2 justify-center">
                                    <Button variant="ghost" size="xs" class="bg-blue-500 text-white rounded-md" @click="openModal(item)"><Pencil class="w-4 h-4" /></Button>
                                    <Button variant="ghost" size="xs" class="bg-red-500 text-white rounded-md" @click="confirmDelete(item)"><Trash class="w-4 h-4" /></Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center">
                <Link v-for="(link, index) in ppn.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-md shadow-xl">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">{{ editingPpn ? 'Edit PPN' : 'Tambah PPN' }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <Input v-model="form.kode_ppn" placeholder="Kode PPN (mis. PPN11)" required />
                            <p v-if="form.errors.kode_ppn" class="text-sm text-red-500 mt-1">{{ form.errors.kode_ppn }}</p>
                        </div>

                        <div>
                            <Input v-model="form.nama_ppn" placeholder="Nama PPN (mis. PPN 11%)" required />
                            <p v-if="form.errors.nama_ppn" class="text-sm text-red-500 mt-1">{{ form.errors.nama_ppn }}</p>
                        </div>

                        <div>
                            <Input v-model="form.persen_ppn" type="number" step="0.01" min="0" max="100" placeholder="Persen PPN" />
                            <p v-if="form.errors.persen_ppn" class="text-sm text-red-500 mt-1">{{ form.errors.persen_ppn }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500">Berlaku Dari</label>
                                <Input v-model="form.effective_from" type="date" class="mt-1" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Berlaku Sampai</label>
                                <Input v-model="form.effective_to" type="date" class="mt-1" />
                                <p v-if="form.errors.effective_to" class="text-xs text-red-500 mt-1">{{ form.errors.effective_to }}</p>
                            </div>
                        </div>

                        <div>
                            <Textarea v-model="form.keterangan" placeholder="Keterangan (opsional)" rows="2" />
                            <p v-if="form.errors.keterangan" class="text-sm text-red-500 mt-1">{{ form.errors.keterangan }}</p>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <Checkbox v-model="form.active" />
                            Aktif
                        </label>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                            <Button type="submit" class="bg-blue-600 text-white">Simpan</Button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p>Yakin ingin menghapus {{ ppnToDelete?.nama_ppn }}?</p>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="destroyPpn">Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
