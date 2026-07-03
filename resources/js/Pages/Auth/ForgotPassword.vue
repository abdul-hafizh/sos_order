<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
    status: { type: String },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-900 via-blue-700 to-blue-500 p-16 flex-col justify-center text-white">
            <div class="text-3xl font-extrabold tracking-tighter mb-8">PROCSY</div>
            <h2 class="text-4xl font-bold leading-tight mb-6">Jangan khawatir, kami bantu akses kembali akun Anda.</h2>
            <p class="text-blue-100 italic">"Keamanan data pengadaan Anda adalah prioritas utama kami."</p>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-sm">
                <div class="mb-10 text-center lg:text-left">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Lupa Password?</h1>
                    <p class="text-gray-500">Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang password.</p>
                </div>

                <div v-if="status" class="mb-6 p-4 rounded-xl bg-blue-50 text-blue-700 text-sm border border-blue-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Email Perusahaan" class="mb-1.5" />
                        <TextInput
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 focus:ring-blue-500"
                            placeholder="user@perusahaan.com"
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <PrimaryButton
                        class="w-full py-4 bg-blue-700 hover:bg-blue-800 rounded-xl justify-center mt-2 shadow-lg shadow-blue-200 transition-all font-bold"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset' }}
                    </PrimaryButton>

                    <div class="text-center pt-4">
                        <Link :href="route('login')" class="text-sm text-blue-600 font-semibold hover:underline">
                            Kembali ke Login
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>