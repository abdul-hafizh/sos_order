<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import { Loader2, Lock, User, X, LogIn, Eye, EyeOff } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'success']);

const showPassword = ref(false);

const form = useForm({
    user: '',
    password: '',
    remember: false,
});

watch(
    () => props.show,
    (isShown) => {
        if (isShown) {
            form.clearErrors();
            form.reset('password');
        }
    }
);

const handleClose = () => {
    if (props.closeable) {
        emit('close');
    }
};

const submitLogin = () => {
    form.post(route('login'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password');
            emit('success');
            emit('close');
        },
        onError: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Modal :show="show" :closeable="closeable" maxWidth="md" @close="handleClose">
        <div class="p-6 font-sans space-y-6 relative bg-white rounded-3xl overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md">
                        <LogIn class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Masuk Ke Akun SOS ORDER</h3>
                        <p class="text-xs text-slate-500">Masukkan username & password Anda untuk melanjutkan SPK</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="handleClose"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submitLogin" class="space-y-4">
                <div class="space-y-1.5">
                    <Label for="user_modal" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Username</Label>
                    <div class="relative">
                        <User class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <Input
                            id="user_modal"
                            v-model="form.user"
                            type="text"
                            placeholder="nama.pengguna"
                            class="pl-10 h-10 text-xs rounded-xl border-slate-200 focus:border-indigo-600 focus:ring-indigo-100 bg-slate-50 font-medium w-full"
                            required
                        />
                    </div>
                    <InputError :message="form.errors.user" />
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <Label for="password_modal" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password</Label>
                    </div>
                    <div class="relative">
                        <Lock class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                        <Input
                            id="password_modal"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••"
                            class="pl-10 pr-10 h-10 text-xs rounded-xl border-slate-200 focus:border-indigo-600 focus:ring-indigo-100 bg-slate-50 font-medium w-full"
                            required
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                            tabindex="-1"
                        >
                            <EyeOff v-if="showPassword" class="w-4 h-4" />
                            <Eye v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        class="w-full h-11 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl text-xs shadow-md transition flex items-center justify-center"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Memverifikasi...' : 'Masuk Sekarang' }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>
