<script setup>
import { ref } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Tag, Pencil, ImageOff } from 'lucide-vue-next';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const showModal = ref(false);
const editingCategory = ref(null);
const preview = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('master-kategori.index'), params.value, { preserveState: true, replace: true });
    }, 300);
};

const onPerPageChange = () => {
    router.get(route('master-kategori.index'), params.value, { preserveState: true, replace: true });
};

const form = useForm({ gambar: null });

const openModal = (item) => {
    editingCategory.value = item;
    preview.value = item.gambar_url;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    form.gambar = file || null;
    preview.value = file ? URL.createObjectURL(file) : editingCategory.value?.gambar_url;
};

const submit = () => {
    router.post(
        route('master-kategori.update', editingCategory.value.categorycode),
        { ...form.data(), _method: 'put' },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: (errors) => form.setError(errors),
        }
    );
};

const closeModal = () => {
    showModal.value = false;
    editingCategory.value = null;
    preview.value = null;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Head title="Kategori" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">Kategori</label>
                <p class="text-sm text-gray-400">Master Data | Kategori</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Tag class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data Kategori</ItemTitle>
                        <ItemDescription class="text-sm">Kelola gambar kategori produk. Data kategori hanya bisa diedit gambarnya.</ItemDescription>
                    </ItemContent>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" @change="onPerPageChange" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <Input v-model="params.search" @input="onSearchInput" placeholder="Cari kategori..." class="max-w-xs border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-20">Gambar</TableHead>
                            <TableHead>Kode</TableHead>
                            <TableHead>Nama Kategori</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in categories.data" :key="item.categorycode">
                            <TableCell>
                                <div class="w-12 h-12 rounded-md border bg-gray-50 flex items-center justify-center overflow-hidden">
                                    <img v-if="item.gambar_url" :src="item.gambar_url" class="w-full h-full object-cover" />
                                    <ImageOff v-else class="w-5 h-5 text-gray-300" />
                                </div>
                            </TableCell>
                            <TableCell>{{ item.categorycode }}</TableCell>
                            <TableCell>{{ item.categoryname }}</TableCell>
                            <TableCell class="text-center">
                                <div class="flex gap-2 justify-center">
                                    <Button variant="ghost" size="xs" class="bg-blue-500 text-white rounded-md" @click="openModal(item)"><Pencil class="w-4 h-4" /></Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center">
                <Link v-for="(link, index) in categories.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">Edit Gambar Kategori</h2>
                    <p class="text-sm text-gray-500 mb-4">{{ editingCategory?.categoryname }}</p>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="w-full h-40 rounded-md border bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img v-if="preview" :src="preview" class="w-full h-full object-contain" />
                            <ImageOff v-else class="w-8 h-8 text-gray-300" />
                        </div>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="onFileChange" class="block w-full text-sm text-gray-600" />
                        <p v-if="form.errors.gambar" class="text-sm text-red-500 -mt-2">{{ form.errors.gambar }}</p>
                        <div class="flex justify-end gap-2 pt-4">
                            <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                            <Button type="submit" class="bg-blue-600 text-white" :disabled="form.processing">Simpan</Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
