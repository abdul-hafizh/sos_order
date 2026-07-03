<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { LayoutGrid, Pencil, Trash } from '@lucide/vue';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';

const props = defineProps({
    divisi: Object,
    filters: Object
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingDivisi = ref(null);
const divisiToDelete = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

const form = useForm({ divisi_name: '' });

watch(params, () => {
    router.get(route('divisi.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const openModal = (item = null) => {
    editingDivisi.value = item;
    form.divisi_name = item ? item.divisi_name : '';
    showModal.value = true;
};

const submit = () => {
    if (editingDivisi.value) {
        form.put(route('divisi.update', editingDivisi.value.id_divisi), { onSuccess: () => closeModal() });
    } else {
        form.post(route('divisi.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (item) => {
    divisiToDelete.value = item;
    showDeleteModal.value = true;
};

const destroyDivisi = () => {
    router.delete(route('divisi.destroy', divisiToDelete.value.id_divisi), {
        onSuccess: () => {
            showDeleteModal.value = false;
            divisiToDelete.value = null;
        },
    });
};

const closeModal = () => {
    showModal.value = false;
    editingDivisi.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Divisi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">Divisi</label>
                <p class="text-sm text-gray-400">Master | Divisi</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <LayoutGrid class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data Divisi</ItemTitle>
                        <ItemDescription class="text-sm">Kelola daftar divisi perusahaan.</ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" @click="openModal()">+ Tambah Divisi</Button>
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <Input v-model="params.search" placeholder="Cari divisi..." class="max-w-xs border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Nama Divisi</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in divisi.data" :key="item.id_divisi">
                            <TableCell>{{ item.id_divisi }}</TableCell>
                            <TableCell>{{ item.divisi_name }}</TableCell>
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
                <Link v-for="(link, index) in divisi.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">{{ editingDivisi ? 'Edit Divisi' : 'Tambah Divisi' }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <Input v-model="form.divisi_name" placeholder="Nama Divisi" required />
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
                    <p>Yakin ingin menghapus {{ divisiToDelete?.divisi_name }}?</p>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="destroyDivisi">Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>