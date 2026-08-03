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
import { Button } from "@/Components/ui/button";
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

const toggleAdmin = (user) => {
    const confirmMessage = user.is_admin
        ? `Cabut hak admin dari ${user.nama_user}?`
        : `Jadikan ${user.nama_user} sebagai Admin HO? Admin HO sebelumnya (jika ada) akan otomatis dicabut.`;

    if (!confirm(confirmMessage)) return;

    router.put(
        route("admin-ho.toggle", user.id),
        {},
        { preserveScroll: true },
    );
};

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
    <Head title="Admin HO" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex-col">
                <label class="font-semibold text-xl text-gray-800 leading-tight">
                    Admin HO
                </label>
                <p class="text-sm text-gray-400">Master | Admin HO</p>
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
                            Kelola Admin HO
                        </ItemTitle>
                        <ItemDescription class="text-sm">
                            Tentukan satu user Head Office (HO) sebagai admin.
                            Hanya boleh ada 1 admin HO aktif dalam waktu
                            bersamaan.
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
                            <TableHead>Status</TableHead>
                            <TableHead class="text-center">Action</TableHead>
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
                            <TableCell>
                                <span
                                    v-if="user.is_admin"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full"
                                >
                                    Admin HO
                                </span>
                                <span v-else class="text-xs text-gray-400"
                                    >Bukan Admin</span
                                >
                            </TableCell>
                            <TableCell class="text-center">
                                <Button
                                    size="xs"
                                    :class="
                                        user.is_admin
                                            ? 'bg-red-500 hover:bg-red-600 text-white rounded-md'
                                            : 'bg-blue-600 hover:bg-blue-700 text-white rounded-md'
                                    "
                                    @click="toggleAdmin(user)"
                                >
                                    {{
                                        user.is_admin
                                            ? "Cabut Admin"
                                            : "Jadikan Admin"
                                    }}
                                </Button>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="!users.length">
                            <TableCell
                                colspan="7"
                                class="text-center text-gray-400 py-8"
                            >
                                Tidak ada user dengan kode_cabang HO.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
