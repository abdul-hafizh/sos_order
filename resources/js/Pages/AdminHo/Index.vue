<script setup>
import { router, Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Input } from "@/Components/ui/input";
import { ShieldCheck } from "lucide-vue-next";
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from "@/Components/ui/item";

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const updateTelegram = (user, telegramChatId) => {
    const value = telegramChatId.trim();

    if (value === (user.telegram_chat_id || "")) return;

    router.put(
        route("admin-ho.updateTelegram", user.id),
        { telegram_chat_id: value },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Admin GSOS" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">
                    Admin GSOS
                </label>
                <p class="text-sm text-gray-400">Master | Admin GSOS</p>
            </div>
        </template>

        <div
            class="py-7 w-full mx-auto px-6 bg-white border border-gray-200 rounded-2xl shadow-md"
        >
            <div class="mb-5">
                <Item
                    variant="outline"
                    class="py-3 gap-3 flex-col md:flex-row items-center md:items-center text-center md:text-left"
                >
                    <ItemMedia
                        variant="icon"
                        class="border rounded-md p-3 shadow-sm bg-slate-500 shrink-0"
                    >
                        <ShieldCheck class="w-7 h-7 text-gray-50" />
                    </ItemMedia>

                    <ItemContent class="w-full">
                        <ItemTitle class="text-lg font-semibold">
                            Admin GSOS
                        </ItemTitle>
                        <ItemDescription class="text-sm">
                            Semua user GSOS otomatis menjadi admin. Isi
                            Telegram Chat ID supaya user tersebut ikut
                            menerima notifikasi permintaan barang baru.
                        </ItemDescription>
                    </ItemContent>
                </Item>
            </div>

            <div class="rounded-md border bg-white overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-14">No</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telegram Chat ID</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(user, index) in users" :key="user.id">
                            <TableCell class="text-gray-500">{{
                                index + 1
                            }}</TableCell>
                            <TableCell class="font-medium">{{
                                user.user
                            }}</TableCell>
                            <TableCell>{{ user.nama_user }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <Input
                                    :model-value="user.telegram_chat_id"
                                    placeholder="Belum diisi"
                                    class="h-8 text-sm min-w-[160px]"
                                    @change="
                                        updateTelegram(
                                            user,
                                            $event.target.value,
                                        )
                                    "
                                />
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!users.length">
                            <TableCell
                                colspan="5"
                                class="text-center text-gray-400 py-8"
                            >
                                Tidak ada user dengan kode_cabang GSOS.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
