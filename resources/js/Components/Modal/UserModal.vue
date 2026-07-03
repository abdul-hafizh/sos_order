<script setup>
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/Components/ui/select';

const props = defineProps({
    show: Boolean,
    mode: String, 
    form: Object,
    user: Object,
    cabangs: Array,
    menus: Array
});

const emit = defineEmits(['close', 'submit', 'delete']);

const toggleAllMenus = () => {
    if (props.form.list_menu.length === props.menus.length) {
        props.form.list_menu = [];
    } else {
        props.form.list_menu = props.menus.map(m => `${m.parent_menu}~${m.link_menu}~${m.nama_menu}`);
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white p-6 rounded-lg w-full max-w-2xl shadow-xl max-h-[90vh] flex flex-col">
            
            <div v-if="mode === 'delete'">
                <h2 class="font-bold mb-4 text-lg border-b pb-2 text-red-600">Konfirmasi Hapus</h2>
                <p class="py-4">Apakah Anda yakin ingin menghapus user <strong>{{ user?.user }}</strong>?</p>
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button variant="outline" @click="$emit('close')">Batal</Button>
                    <Button class="bg-red-600 hover:bg-red-700 text-white" @click="$emit('delete')">Ya, Hapus</Button>
                </div>
            </div>

            <form v-else @submit.prevent="$emit('submit')" class="flex flex-col h-full">
                <h2 class="font-bold mb-4 text-lg border-b pb-2">
                    {{ mode === 'edit' ? 'Edit User' : 'Tambah User' }}
                </h2>
                
                <div class="overflow-y-auto pr-2 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <Input v-model="form.user" placeholder="Username" required />
                        <Input v-model="form.nama_user" placeholder="Nama Lengkap" required />
                    </div>
                    <Input v-model="form.email" type="email" placeholder="Email" required />
                    
                    <Select v-model="form.type_user">
                        <SelectTrigger><SelectValue placeholder="Pilih Tipe User" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">Cabang</SelectItem>
                            <SelectItem value="1">SOS</SelectItem>
                            <SelectItem value="2">Jumbo</SelectItem>
                        </SelectContent>
                    </Select>

                    <div class="border p-4 rounded-md">
                        <div class="flex justify-between mb-2">
                            <label class="text-sm font-semibold">List Menu Akses</label>
                            <button type="button" class="text-xs text-blue-600" @click="toggleAllMenus">
                                {{ form.list_menu.length === menus.length ? 'Hapus Semua' : 'Pilih Semua' }}
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <label v-for="menu in menus" :key="menu.link_menu" class="flex items-center gap-2">
                                <input type="checkbox" :value="`${menu.parent_menu}~${menu.link_menu}~${menu.nama_menu}`" v-model="form.list_menu" />
                                {{ menu.nama_menu }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t mt-4">
                    <Button type="button" variant="outline" @click="$emit('close')">Batal</Button>
                    <Button type="submit" :disabled="form.processing">Simpan Data</Button>
                </div>
            </form>
        </div>
    </div>
</template>