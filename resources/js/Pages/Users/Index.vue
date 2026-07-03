<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Users, Pencil, Trash } from '@lucide/vue'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/Components/ui/item'
import {
    SelectLabel,
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';

const props = defineProps({
    users: Object, filters: Object, cabangs: Array, menus: {
        type: Array,
        default: () => []
    }
});

const showModal = ref(false);
const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || 10,
    sort: props.filters.sort || 'user',
    order: props.filters.order || 'asc'
});


const editingUser = ref(null);

const editUser = (user) => {
    editingUser.value = user;
    form.user = user.user;
    form.nama_user = user.nama_user;
    form.email = user.email;
    form.type_user = String(user.type_user);
    form.kode_cabang = user.kode_cabang;

    form.list_menu = user.list_menu ? user.list_menu.split('#').filter(Boolean) : [];

    showModal.value = true;
};

const formatMenuString = (menu) => `${menu.parent_menu}~${menu.link_menu}~${menu.nama_menu}`;

const handleCheckboxChange = (val, menu) => {
    const menuString = formatMenuString(menu);
    if (val) {
        if (!form.list_menu.includes(menuString)) {
            form.list_menu.push(menuString);
        }
    } else {
        form.list_menu = form.list_menu.filter(item => item !== menuString);
    }
};

const toggleAllMenus = () => {
    if (form.list_menu.length === props.menus.length) {
        form.list_menu = [];
    } else {
        form.list_menu = props.menus.map(menu => formatMenuString(menu));
    }
};

watch(params, () => {
    router.get(route('users.index'), params.value, { preserveState: true, replace: true });
}, { deep: true });

const form = useForm({
    user: '',
    nama_user: '',
    email: '',
    type_user: '0',
    kode_cabang: '',
    pwd: '',
    konf_pwd: '',
    list_menu: []
});

const updateSort = (field) => {
    params.value.order = (params.value.sort === field && params.value.order === 'asc') ? 'desc' : 'asc';
    params.value.sort = field;
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const showDeleteModal = ref(false);
const userToDelete = ref(null);

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const destroyUser = () => {
    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
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

    <Head title="Users" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">
                    Users
                </label>
                <p class="text-sm text-gray-400">
                    Master | User
                </p>
            </div>

        </template>
        <div class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md">
            <div class="mb-5">
                <Item variant="outline"
                    class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left">

                    <ItemMedia variant="icon" class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0">
                        <Users class="w-7 h-7 text-gray-50" />
                    </ItemMedia>

                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">Data User</ItemTitle>
                        <ItemDescription class="text-sm">
                            Kelola daftar pengguna, hak akses, dan informasi akun secara terpusat.
                        </ItemDescription>
                    </ItemContent>

                    <ItemActions class="w-full md:w-auto">
                        <Button class="w-full md:w-auto rounded-md bg-blue-700 text-white" variant="outline"
                            @click="showModal = true">
                            + Tambah Data User
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
                <Input v-model="params.search" placeholder="Cari..."
                    class="max-w-xs bg-white border-gray-300 rounded-md" />
            </div>


            <div class="rounded-md border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="cursor-pointer" @click="updateSort('user')">User {{ params.sort === 'user'
                                ?
                                (params.order === 'asc' ? '▲' : '▼') : '' }}</TableHead>
                            <TableHead class="cursor-pointer" @click="updateSort('nama_user')">Nama {{ params.sort ===
                                'nama_user' ? (params.order === 'asc' ? '▲' : '▼') : '' }}</TableHead>
                            <TableHead>Tipe User</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.user }}</TableCell>
                            <TableCell>{{ user.nama_user }}</TableCell>
                            <TableCell>{{ user.type_user }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <div class="flex gap-2 items-center mb-1">
                                    <Button variant="ghost" size="xs" class="bg-blue-500 p-1 text-white rounded-md"
                                        @click="editUser(user)">
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="xs" class="bg-red-500 p-1 text-white rounded-md"
                                        @click="confirmDelete(user)">
                                        <Trash class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4 flex flex-wrap gap-1 justify-center md:justify-end items-center">
                <Link v-for="(link, index) in users.links" :key="index" :href="link.url ?? '#'"
                    :class="{ 'hidden sm:inline-flex': !link.active && !link.label.includes('Previous') && !link.label.includes('Next') }">
                    <Button :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" class="px-3">
                        <span v-html="link.label"></span>
                    </Button>
                </Link>
            </div>

            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-2xl shadow-xl max-h-[90vh] flex flex-col">
                    <h2 class="font-bold mb-4 text-lg border-b pb-2">
                        {{ editingUser ? 'Edit User' : 'Tambah User' }}
                    </h2>

                    <form @submit.prevent="submit" class="space-y-4 overflow-y-auto pr-2">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col space-y-2">
                                <label for="User" class="text-xs text-gray-500 font-medium">User</label>
                                <Input v-model="form.user" placeholder="Isi Username" required />
                            </div>

                            <div class="flex flex-col space-y-2">
                                <label for="Nama Lengkap" class="text-xs text-gray-500 font-medium">Nama Lengkap</label>
                                <Input v-model="form.nama_user" placeholder="Isi Nama Lengkap" required />
                            </div>

                            <div class="flex flex-col space-y-2">
                                <label for="Email" class="text-xs text-gray-500 font-medium">Email</label>
                                <Input v-model="form.email" type="email" placeholder="Isi Email" required />
                            </div>
                            <div class="flex flex-col space-y-2">
                                <label for="Tipe User" class="text-xs text-gray-500 font-medium">Tipe User</label>
                                <Select v-model="form.type_user" :portal="false">
                                    <SelectTrigger class="w-full z-101">
                                        <SelectValue placeholder="Pilih Tipe User" />
                                    </SelectTrigger>
                                    <SelectContent position="popper" class="z-[100] relative">
                                        <SelectGroup>
                                            <SelectLabel>Tipe User</SelectLabel>
                                            <SelectItem value="0">Cabang</SelectItem>
                                            <SelectItem value="1">SOS</SelectItem>
                                            <SelectItem value="2">Jumbo</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <label for="Pilih Cabang" class="text-xs text-gray-500 font-medium">Cabang</label>
                            <Select v-model="form.kode_cabang" :portal="false">
                                <SelectTrigger class="w-full z-101">
                                    <SelectValue placeholder="Pilih Cabang" />
                                </SelectTrigger>
                                <SelectContent position="popper" class="z-[100]">
                                    <SelectItem v-for="c in cabangs" :key="c.kode_cabang" :value="c.kode_cabang">
                                        {{ c.cabang_nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Input v-model="form.pwd" type="password" placeholder="Password" />
                            <Input v-model="form.konf_pwd" type="password" placeholder="Konfirmasi Password" />
                        </div>

                        <div class="border p-4 rounded-md">
                            <div class="flex items-center justify-between mb-3">
                                <label class="font-semibold text-sm">List Menu Akses</label>

                                <div class="flex flex-col items-end">
                                    <button type="button" class="text-xs text-blue-600 hover:underline font-medium"
                                        @click.prevent="toggleAllMenus">
                                        {{ form.list_menu.length === menus.length ? 'Hapus Semua' : 'Pilih Semua' }}
                                    </button>
                                    <span class="text-[10px] text-gray-400">
                                        {{ form.list_menu.length }} / {{ menus.length }} terpilih
                                    </span>
                                </div>
                            </div>

                            <div v-for="menu in menus" :key="menu.link_menu"
                                class="flex items-start space-x-2 p-1 hover:bg-gray-50 rounded">
                                <input type="checkbox" :id="menu.link_menu"
                                    :value="`${menu.parent_menu}~${menu.link_menu}~${menu.nama_menu}`"
                                    v-model="form.list_menu" @change="handleCheckboxChange"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 cursor-pointer accent-blue-600" />
                                <label :for="menu.link_menu" class="text-sm font-medium cursor-pointer pt-0.5">
                                    {{ menu.parent_menu }} => {{ menu.nama_menu }}
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t">
                            <Button type="button" variant="outline" @click="showModal = false"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800">
                                Batal
                            </Button>
                            <Button type="submit" :disabled="form.processing"
                                class="bg-blue-600 hover:bg-blue-700 text-white">
                                Simpan Data
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white p-6 rounded-lg w-full max-w-sm shadow-xl">
                    <h2 class="font-bold text-lg mb-2">Konfirmasi Hapus</h2>
                    <p class="text-gray-600 mb-6">
                        Apakah Anda yakin ingin menghapus user <strong>{{ userToDelete?.user }}</strong>?
                        Aksi ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
                        <Button class="bg-red-600 hover:bg-red-700 text-white" @click="destroyUser">Ya, Hapus</Button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>