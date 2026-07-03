<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun" />

        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-blue-900 via-blue-700 to-blue-500 p-16 flex-col justify-between text-white relative">
            <div class="text-3xl font-extrabold tracking-tighter text-white">PROCSY</div>
            
            <div class="space-y-6">
                <h2 class="text-5xl font-bold leading-tight text-white">Kelola Vendor & Pengadaan dalam Satu Pintu.</h2>
                
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/10">
                    <div class="flex text-blue-300 mb-3 text-xl">★★★★★</div>
                    <p class="text-blue-50 text-sm italic mb-4">
                        "Bergabunglah dengan ratusan perusahaan yang telah mendigitalisasi proses pengadaan mereka untuk transparansi dan efisiensi yang lebih baik."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-400 flex items-center justify-center font-bold text-blue-900">P</div>
                        <span class="font-semibold text-sm">Procsy Onboarding Team <br/> <span class="text-blue-200 font-normal">Support Sistem</span></span>
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-blue-200">© 2026 Procsy Procurement System.</div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-sm">
                <div class="flex justify-between items-center mb-10">
                    <div class="text-blue-700 font-bold text-xl lg:hidden">PROCSY</div>
                    <div class="flex items-center gap-2 ml-auto">
                        <span class="text-sm text-gray-500">Sudah punya akun?</span>
                        <Link :href="route('login')" class="px-5 py-2 border border-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-50 transition">MASUK</Link>
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 mb-2">Daftar Akun Baru</h1>
                <p class="text-gray-500 mb-8">Mulai langkah awal efisiensi pengadaan perusahaan Anda.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" class="mb-1.5" />
                        <TextInput id="name" v-model="form.name" type="text" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 focus:ring-blue-500" placeholder="Masukkan nama lengkap" required autofocus />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email Perusahaan" class="mb-1.5" />
                        <TextInput id="email" v-model="form.email" type="email" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 focus:ring-blue-500" placeholder="user@perusahaan.com" required />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    
                    <div>
                        <InputLabel for="password" value="Password" class="mb-1.5" />
                        <TextInput id="password" v-model="form.password" type="password" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 focus:ring-blue-500" placeholder="Minimal 8 karakter" required />
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Konfirmasi Password" class="mb-1.5" />
                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 focus:ring-blue-500" placeholder="Ulangi password" required />
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>

                    <PrimaryButton :disabled="form.processing" class="w-full py-4 bg-blue-700 hover:bg-blue-800 rounded-xl justify-center mt-4 shadow-lg shadow-blue-200 transition-all font-bold">
                        {{ form.processing ? 'Memproses...' : 'Buat Akun Sekarang' }}
                    </PrimaryButton>

                    <p class="text-center text-xs text-gray-500 mt-6 px-4">
                        Dengan mendaftar, Anda menyetujui <a href="#" class="text-blue-600 hover:underline">Syarat & Ketentuan</a> serta <a href="#" class="text-blue-600 hover:underline">Kebijakan Privasi</a> Procsy.
                    </p>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>