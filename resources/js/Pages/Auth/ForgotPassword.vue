<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import { Loader2 } from 'lucide-vue-next';

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
    <Head title="Lupa Password" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 relative overflow-hidden p-4">
        
        <!-- Elemen Dekoratif Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-blue-400 mix-blend-multiply filter blur-[100px] opacity-20 animate-pulse"></div>
            <div class="absolute bottom-[10%] right-[5%] w-[30%] h-[30%] rounded-full bg-indigo-400 mix-blend-multiply filter blur-[100px] opacity-20"></div>
        </div>

        <div class="w-full max-w-[400px] relative z-10">
            <div class="bg-white/80 backdrop-blur-xl border border-gray-300/50 p-8 rounded-2xl shadow-xl shadow-slate-200/50">
                <div class="text-center space-y-2 mb-8">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Lupa Password?</h1>
                    <p class="text-sm text-slate-500">Masukkan email Anda untuk menerima tautan atur ulang password.</p>
                </div>

                <div v-if="status" class="mb-6 p-4 rounded-xl bg-blue-50 text-blue-700 text-xs border border-blue-200 text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <Label for="email">Email Perusahaan</Label>
                        <Input 
                            id="email" 
                            v-model="form.email" 
                            type="email" 
                            placeholder="user@perusahaan.com" 
                            class="h-10 bg-white/50" 
                            required 
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <Button type="submit" class="w-full h-10 font-medium bg-blue-600 hover:bg-blue-700 text-white" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset' }}
                    </Button>
                </form>
            </div>

            <div class="text-center mt-6">
                <Link :href="route('login')" class="text-sm text-slate-500 hover:text-blue-600 font-medium transition">
                    ← Kembali ke Login
                </Link>
            </div>
        </div>
    </div>
</template>