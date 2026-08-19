<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue'; // Pastikan import ini
import { Loader2, Eye, EyeOff } from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
    user: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk ke Procsy" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 relative overflow-hidden p-4">
        
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-blue-400 mix-blend-multiply filter blur-[100px] opacity-20 animate-pulse"></div>
            <div class="absolute bottom-[10%] right-[5%] w-[30%] h-[30%] rounded-full bg-indigo-400 mix-blend-multiply filter blur-[100px] opacity-20"></div>
        </div>

        <div class="w-full max-w-[400px] relative z-10">
            <div class="bg-white/80 backdrop-blur-xl border border-gray-300/50 p-8 rounded-2xl shadow-xl shadow-slate-200/50">
                <div class="text-center space-y-2 mb-8">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Selamat Datang</h1>
                    <p class="text-sm text-slate-500">Masukkan akun Anda untuk melanjutkan</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <Label for="user">Username</Label>
                        <Input id="user" v-model="form.user" type="text" placeholder="nama.pengguna" class="h-10 bg-white/50" required />
                        <InputError :message="form.errors.user" />
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="password">Password</Label>
                            <Link :href="route('password.request')" class="text-xs text-blue-600 hover:underline">Lupa password?</Link>
                        </div>
                        <div class="relative">
                            <Input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••" class="h-10 bg-white/50 pr-10" required />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <Button type="submit" class="w-full h-10 font-medium bg-blue-600 hover:bg-blue-700 text-white" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>