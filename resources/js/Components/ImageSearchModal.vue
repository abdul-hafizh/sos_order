<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { Button } from '@/Components/ui/button';
import { UploadCloud, X, Sparkles, Loader2, Image as ImageIcon } from 'lucide-vue-next';

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

const emit = defineEmits(['close']);

const selectedFile = ref(null);
const previewUrl = ref(null);
const isProcessing = ref(false);
const fileInputRef = ref(null);

watch(
    () => props.show,
    (isShown) => {
        if (!isShown) {
            resetModal();
        }
    }
);

const resetModal = () => {
    selectedFile.value = null;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = null;
    isProcessing.value = false;
};

const handleClose = () => {
    if (props.closeable && !isProcessing.value) {
        emit('close');
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    selectedFile.value = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);
};

const removeSelectedImage = () => {
    selectedFile.value = null;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const executeSearch = () => {
    if (!selectedFile.value) {
        alert('Silakan pilih foto sampel terlebih dahulu.');
        return;
    }

    isProcessing.value = true;
    const formData = new FormData();
    formData.append('image', selectedFile.value);

    router.post(route('dashboard.search-image'), formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: false,
        onFinish: () => {
            isProcessing.value = false;
        },
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            isProcessing.value = false;
            alert(errors.image || 'Gagal memproses pencarian foto AI.');
        },
    });
};
</script>

<template>
    <Modal :show="show" :closeable="closeable && !isProcessing" maxWidth="md" @close="handleClose">
        <div class="p-6 font-sans space-y-5 relative bg-white rounded-3xl overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shrink-0">
                        <Sparkles class="w-5 h-5 text-yellow-300" />
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Pencarian Foto AI</h3>
                        <p class="text-xs text-slate-500">Upload foto sampel untuk mencari varian barang serupa</p>
                    </div>
                </div>

                <button
                    v-if="!isProcessing"
                    type="button"
                    @click="handleClose"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <input
                ref="fileInputRef"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onFileSelected"
            />

            <!-- Upload Dropzone (When No Image Selected) -->
            <div
                v-if="!previewUrl"
                @click="triggerFileInput"
                class="border-2 border-dashed border-slate-300 hover:border-indigo-500 hover:bg-indigo-50/50 transition-all rounded-3xl p-8 flex flex-col items-center justify-center cursor-pointer bg-slate-50 text-center space-y-3 group"
            >
                <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200 group-hover:border-indigo-300 flex items-center justify-center text-indigo-600 shadow-2xs group-hover:scale-105 transition">
                    <UploadCloud class="w-7 h-7 text-indigo-600" />
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-slate-800 text-sm">Pilih Foto Sampel Barang</h4>
                    <p class="text-xs text-slate-400 font-medium">Klik di sini untuk menjelajahi galeri foto Anda</p>
                </div>
                <span class="text-[10px] font-extrabold text-indigo-600 bg-indigo-100 px-3 py-1 rounded-full uppercase tracking-wider">
                    Format: JPG, PNG, WEBP
                </span>
            </div>

            <!-- Image Preview Box (When Image Selected) -->
            <div v-else class="space-y-3">
                <div class="relative rounded-3xl overflow-hidden border-2 border-indigo-500/30 bg-slate-900 aspect-video flex items-center justify-center p-2 shadow-inner group">
                    <img :src="previewUrl" class="max-w-full max-h-full object-contain rounded-xl" />
                    
                    <button
                        v-if="!isProcessing"
                        type="button"
                        @click="removeSelectedImage"
                        class="absolute top-3 right-3 bg-black/75 hover:bg-black text-white p-2 rounded-full transition shadow-md"
                        title="Ganti Foto Sampel"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex items-center justify-between text-xs px-1">
                    <span class="text-slate-500 font-medium flex items-center truncate">
                        <ImageIcon class="w-4 h-4 mr-1 text-indigo-600 shrink-0" />
                        <span class="truncate font-bold text-slate-700">{{ selectedFile?.name }}</span>
                    </span>
                    <button
                        v-if="!isProcessing"
                        type="button"
                        @click="triggerFileInput"
                        class="text-indigo-600 font-bold hover:underline shrink-0 ml-2"
                    >
                        Ganti Foto
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <Button
                    type="button"
                    variant="outline"
                    class="w-1/3 h-11 rounded-2xl text-xs font-bold"
                    :disabled="isProcessing"
                    @click="handleClose"
                >
                    Batal
                </Button>

                <Button
                    type="button"
                    class="w-2/3 h-11 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl text-xs shadow-md transition flex items-center justify-center"
                    :disabled="!selectedFile || isProcessing"
                    @click="executeSearch"
                >
                    <Loader2 v-if="isProcessing" class="mr-2 h-4 w-4 animate-spin" />
                    <Sparkles v-else class="mr-2 h-4 w-4 text-yellow-300" />
                    {{ isProcessing ? 'Mencari Foto AI...' : 'Eksekusi Cari Foto AI' }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
