<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import { Loader2 } from 'lucide-vue-next';

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
    <Head title="Daftar Akun Baru" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 relative overflow-hidden p-4">
        
        <!-- Elemen Dekoratif Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-blue-400 mix-blend-multiply filter blur-[100px] opacity-20 animate-pulse"></div>
            <div class="absolute bottom-[10%] right-[5%] w-[30%] h-[30%] rounded-full bg-indigo-400 mix-blend-multiply filter blur-[100px] opacity-20"></div>
        </div>

        <div class="w-full max-w-[400px] relative z-10">
            <div class="bg-white/80 backdrop-blur-xl border border-gray-300/50 p-8 rounded-2xl shadow-xl shadow-slate-200/50">
                <div class="text-center space-y-2 mb-8">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Buat Akun</h1>
                    <p class="text-sm text-slate-500">Mulai langkah awal efisiensi pengadaan Anda</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <Label for="name">Nama Lengkap</Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="Nama Anda" class="h-10 bg-white/50" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email Perusahaan</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="user@perusahaan.com" class="h-10 bg-white/50" required />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Password</Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="••••••••" class="h-10 bg-white/50" required />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">Konfirmasi Password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="••••••••" class="h-10 bg-white/50" required />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" class="w-full h-10 font-medium bg-blue-600 hover:bg-blue-700 text-white" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Memproses...' : 'Daftar Sekarang' }}
                    </Button>
                </form>
            </div>

            <p class="text-center text-sm text-slate-500 mt-6">
                Sudah punya akun? <Link :href="route('login')" class="text-blue-600 font-semibold hover:underline">Masuk di sini</Link>
            </p>
        </div>
    </div>
</template>