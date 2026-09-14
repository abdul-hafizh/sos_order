<script setup>
import { ref } from 'vue';
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Send, Pencil } from 'lucide-vue-next';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/Components/ui/item';

const props = defineProps({
    users: Object,
    filters: Object,
});

const showModal = ref(false);
const editingUser = ref(null);

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
});

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('user-telegram.index'), params.value, { preserveState: true, replace: true });
    }, 300);
};

const onPerPageChange = () => {
    router.get(route('user-telegram.index'), params.value, { preserveState: true, replace: true });
};

const form = useForm({
    telegram_chat_id: '',
});

const openModal = (item) => {
    editingUser.value = item;
    form.telegram_chat_id = item.telegram_chat_id ?? '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    form.put(route('user-telegram.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    showModal.value = false;
    editingUser.value = null;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Head title="User Telegram" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">User Telegram</label>
                <p class="text-sm text-gray-400">Master Data | User Telegram</p>
            </div>
        </template>

        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline" class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">
                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Send class="w-7 h-7 text-gray-50" />
                    </ItemMedia>
                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Telegram Chat ID User</ItemTitle>
                        <ItemDescription class="text-sm">Atur Telegram Chat ID tiap user. Klik tombol edit untuk mengubahnya.</ItemDescription>
                    </ItemContent>
                </Item>
            </div>

            <div class="flex gap-2 mb-3 justify-between">
                <select v-model="params.per_page" @change="onPerPageChange" class="border-gray-300 rounded-md text-xs bg-white">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option>
                </select>
                <Input v-model="params.search" @input="onSearchInput" placeholder="Cari user..." class="max-w-xs border-gray-300 rounded-md" />
            </div>

            <div class="rounded-md border bg-white overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>Nama User</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telegram Chat ID</TableHead>
                            <TableHead class="text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in users.data" :key="item.id">
                            <TableCell class="font-mono text-xs">{{ item.user }}</TableCell>
                            <TableCell class="font-bold text-slate-800">{{ item.nama_user }}</TableCell>
                            <TableCell class="text-slate-600">{{ item.email }}</TableCell>
                            <TableCell class="text-slate-600">{{ item.telegram_chat_id || '-' }}</TableCell>
                            <TableCell class="text-center">
                                <div class="flex gap-2 justify-center">
                                    <Button variant="ghost" size="xs" class="bg-blue-500 text-white rounded-md hover:bg-blue-600" @click="openModal(item)" title="Edit Telegram Chat ID">
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center">
                <Link v-for="(link, index) in users.links" :key="index" :href="link.url ?? '#'" :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <!-- Edit Modal -->
            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-2xl w-full max-w-sm shadow-xl space-y-4">
                    <h2 class="font-bold text-lg border-b pb-2 text-slate-900">Edit Telegram Chat ID</h2>
                    <p class="text-sm font-medium text-slate-600">{{ editingUser?.nama_user }} ({{ editingUser?.user }})</p>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Telegram Chat ID</label>
                            <Input v-model="form.telegram_chat_id" placeholder="Masukkan Telegram Chat ID" class="border-gray-300 rounded-md" />
                            <p v-if="form.errors.telegram_chat_id" class="text-xs text-red-500 mt-1">{{ form.errors.telegram_chat_id }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                            <Button type="submit" class="bg-blue-600 text-white font-bold" :disabled="form.processing">Simpan</Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
