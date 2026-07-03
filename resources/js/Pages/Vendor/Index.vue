<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Building2, Pencil, Trash } from 'lucide-vue-next'; // Menggunakan ikon yang sesuai
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/Components/ui/item';

const props = defineProps({
    vendors: Object,
    filters: Object
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingVendor = ref(null);
const vendorToDelete = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10
});

watch(params, () => {
    router.get(route('vendor.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const form = useForm({
    kode_vendor: '',
    nama_vendor: '',
    alamat: '',
    telp: '',
    pic: '',
    kota: ''
});

const editVendor = (vendor) => {
    editingVendor.value = vendor;
    form.kode_vendor = vendor.kode_vendor;
    form.nama_vendor = vendor.nama_vendor;
    form.alamat = vendor.alamat;
    form.telp = vendor.telp;
    form.pic = vendor.pic;
    form.kota = vendor.kota;
    showModal.value = true;
};

const submit = () => {
    if (editingVendor.value) {
        form.put(route('vendor.update', editingVendor.value.id_vendor), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('vendor.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const confirmDelete = (vendor) => {
    vendorToDelete.value = vendor;
    showDeleteModal.value = true;
};

const destroyVendor = () => {
    router.delete(route('vendor.destroy', vendorToDelete.value.id_vendor), {
        onSuccess: () => {
            showDeleteModal.value = false;
            vendorToDelete.value = null;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    editingVendor.value = null;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Head title="Vendor" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">Vendor</label>
                <p class="text-sm text-gray-400">Master | Vendor</p>
            </div>
        </template>

        <div class="py-7 w-full px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Building2 class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data Vendor</ItemTitle>
                        <ItemDescription class="text-sm">Kelola daftar vendor dan informasi kontak secara terpusat.</ItemDescription>
                    </ItemContent>
                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" variant="outline" @click="showModal = true">
                            + Tambah Data Vendor
                        </Button>
                    </ItemActions>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <Input v-model="params.search" placeholder="Cari vendor..." class="max-w-xs bg-white border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kode</TableHead>
                            <TableHead>Nama Vendor</TableHead>
                            <TableHead>Alamat</TableHead>
                            <TableHead>Telepon</TableHead>
                            <TableHead>PIC</TableHead>
                            <TableHead>Kota</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="vendor in vendors.data" :key="vendor.id_vendor">
                            <TableCell>{{ vendor.kode_vendor }}</TableCell>
                            <TableCell class="font-medium">{{ vendor.nama_vendor }}</TableCell>
                            <TableCell>{{ vendor.alamat }}</TableCell>
                            <TableCell>{{ vendor.telp }}</TableCell>
                            <TableCell>{{ vendor.pic }}</TableCell>
                            <TableCell>{{ vendor.kota }}</TableCell>
                            <TableCell>
                                <div class="flex gap-2 items-center">
                                    <Button variant="ghost" size="xs" class="bg-blue-500 p-1 text-white rounded-md" @click="editVendor(vendor)">
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="xs" class="bg-red-500 p-1 text-white rounded-md" @click="confirmDelete(vendor)">
                                        <Trash class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-end items-center">
                <Link v-for="(link, index) in vendors.links" :key="index" :href="link.url ?? '#'">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3" v-html="link.label"></Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-lg shadow-xl">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">{{ editingVendor ? 'Edit Vendor' : 'Tambah Vendor' }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <Input v-model="form.kode_vendor" placeholder="Kode Vendor" required />
                        <Input v-model="form.nama_vendor" placeholder="Nama Vendor" required />
                        <Input v-model="form.alamat" placeholder="Alamat" />
                        <div class="grid grid-cols-2 gap-4">
                            <Input v-model="form.telp" placeholder="Telepon" />
                            <Input v-model="form.kota" placeholder="Kota" />
                        </div>
                        <Input v-model="form.pic" placeholder="PIC" />
                        
                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                            <Button type="submit" class="bg-blue-600 text-white">Simpan</Button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p class="text-gray-600 mb-6">Yakin ingin menghapus vendor <strong>{{ vendorToDelete?.nama_vendor }}</strong>?</p>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 text-white" @click="destroyVendor">Ya, Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>