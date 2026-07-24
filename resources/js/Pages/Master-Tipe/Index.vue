<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Shapes, Pencil, Trash } from 'lucide-vue-next';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';

const props = defineProps({
    tipe: Object,
    filters: Object
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingTipe = ref(null);
const tipeToDelete = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

const form = useForm({ nama: '' });

watch(params, () => {
    router.get(route('master-tipe.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const openModal = (item = null) => {
    editingTipe.value = item;
    form.nama = item ? item.nama : '';
    showModal.value = true;
};

const submit = () => {
    if (editingTipe.value) {
        form.put(route('master-tipe.update', editingTipe.value.id_tipe), { onSuccess: () => closeModal() });
    } else {
        form.post(route('master-tipe.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (item) => {
    tipeToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyTipe = () => {
    router.delete(route('master-tipe.destroy', tipeToDelete.value.id_tipe), {
        onSuccess: () => {
            showDeleteModal.value = false;
            tipeToDelete.value = null;
        },
    });
};

const closeModal = () => {
    showModal.value = false;
    editingTipe.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Type" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">Type</label>
                <p class="text-sm text-gray-400">Master Data | Type</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Shapes class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data Type</ItemTitle>
                        <ItemDescription class="text-sm">Kelola daftar type produk.</ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" @click="openModal()">+ Tambah Type</Button>
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <Input v-model="params.search" placeholder="Cari type..." class="max-w-xs border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Nama Type</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in tipe.data" :key="item.id_tipe">
                            <TableCell>{{ item.id_tipe }}</TableCell>
                            <TableCell>{{ item.nama }}</TableCell>
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
                <Link v-for="(link, index) in tipe.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">{{ editingTipe ? 'Edit Type' : 'Tambah Type' }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <Input v-model="form.nama" placeholder="Nama Type" required />
                        <p v-if="form.errors.nama" class="text-sm text-red-500 -mt-2">{{ form.errors.nama }}</p>
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
                    <p>Yakin ingin menghapus {{ tipeToDelete?.nama }}?</p>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="destroyTipe">Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
