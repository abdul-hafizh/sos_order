<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { Check, ChevronDown, Search } from "lucide-vue-next";

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => [],
    },
    valueKey: {
        type: String,
        default: "id",
    },
    labelKey: {
        type: String,
        default: "name",
    },
    placeholder: {
        type: String,
        default: "Pilih Data...",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const keyword = ref("");
const wrapper = ref(null);

const filtered = computed(() => {
    if (!keyword.value) return props.options;

    return props.options.filter((item) =>
        String(item[props.labelKey])
            .toLowerCase()
            .includes(keyword.value.toLowerCase())
    );
});

const selectedLabel = computed(() => {
    const item = props.options.find(
        (x) => x[props.valueKey] == props.modelValue
    );

    return item ? item[props.labelKey] : "";
});

const choose = (item) => {
    emit("update:modelValue", item[props.valueKey]);
    keyword.value = "";
    open.value = false;
};

const toggleOpen = () => {
    if (props.disabled) return;
    open.value = !open.value;
};

const closeOutside = (e) => {
    if (!wrapper.value?.contains(e.target)) {
        open.value = false;
    }
};

watch(open, (v) => {
    if (v) keyword.value = "";
});

onMounted(() => {
    document.addEventListener("click", closeOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeOutside);
});
</script>

<template>
    <div class="relative" ref="wrapper">

        <button
            type="button"
            @click="toggleOpen"
            :disabled="disabled"
            class="w-full border rounded-md px-3 py-2 flex justify-between items-center"
            :class="disabled ? 'bg-gray-100 cursor-not-allowed opacity-70' : 'bg-white'"
        >
            <span
                :class="selectedLabel ? 'text-black' : 'text-gray-400'"
            >
                {{ selectedLabel || placeholder }}
            </span>

            <ChevronDown class="w-4 h-4 text-gray-500" />
        </button>

        <div
            v-if="open && !disabled"
            class="absolute left-0 right-0 mt-1 bg-white border rounded-lg shadow-lg z-50"
        >
            <div class="p-2 border-b">

                <div class="relative">

                    <Search class="absolute left-2 top-2.5 w-4 h-4 text-gray-400"/>

                    <input
                        v-model="keyword"
                        class="w-full border rounded-md pl-8 pr-2 py-2 text-sm"
                        placeholder="Cari..."
                    />

                </div>

            </div>

            <div class="max-h-60 overflow-y-auto">

                <div
                    v-if="filtered.length == 0"
                    class="text-center text-gray-500 py-6"
                >
                    Data tidak ditemukan
                </div>

                <button
                    v-for="item in filtered"
                    :key="item[valueKey]"
                    type="button"
                    @click="choose(item)"
                    class="w-full text-left px-3 py-2 hover:bg-blue-50 flex justify-between"
                >

                    <span>
                        {{ item[labelKey] }}
                    </span>

                    <Check
                        v-if="item[valueKey] == modelValue"
                        class="w-4 h-4 text-blue-600"
                    />

                </button>

            </div>

        </div>

    </div>
</template>