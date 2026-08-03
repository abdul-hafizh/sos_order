<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Building2, User as UserIcon } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = computed(() => usePage().props.auth?.user);
const cabangNama = computed(() => user.value?.cabang?.cabang_nama);
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header class="flex items-center gap-3 mb-5">
                            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl">
                                <UserIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">
                                    Informasi Akun
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Data akun dan cabang tempat Anda terdaftar.
                                </p>
                            </div>
                        </header>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-xl">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Username
                                </dt>
                                <dd class="text-sm font-semibold text-gray-900 mt-1">
                                    {{ user?.user || '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Nama Lengkap
                                </dt>
                                <dd class="text-sm font-semibold text-gray-900 mt-1">
                                    {{ user?.nama_user || '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Email
                                </dt>
                                <dd class="text-sm font-semibold text-gray-900 mt-1">
                                    {{ user?.email || '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                    Cabang
                                </dt>
                                <dd class="text-sm font-semibold text-gray-900 mt-1 flex items-center gap-1.5">
                                    <Building2 class="w-4 h-4 text-blue-600" />
                                    <span v-if="cabangNama">
                                        {{ cabangNama }}
                                        <span class="text-gray-400 font-normal">({{ user?.kode_cabang }})</span>
                                    </span>
                                    <span v-else class="text-gray-400 font-normal">
                                        {{ user?.kode_cabang || '-' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
