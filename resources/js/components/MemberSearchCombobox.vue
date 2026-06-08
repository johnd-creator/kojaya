<script setup lang="ts">
import { useDebounceFn } from "@vueuse/core";
import axios from "axios";
import { Check, ChevronsUpDown, Search } from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import {
  ComboboxContent,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxPortal,
  ComboboxRoot,
  ComboboxViewport,
} from "reka-ui";
import { cn } from "@/lib/utils";

interface Member {
  id: number;
  name: string;
  member_no: string;
  status: string;
}

const props = withDefaults(
  defineProps<{
    modelValue?: number | null;
    placeholder?: string;
  }>(),
  {
    modelValue: null,
    placeholder: "Cari anggota...",
  },
);

const emit = defineEmits<{
  (e: "update:modelValue", value: number | null): void;
}>();

const open = ref(false);
const search = ref("");
const options = ref<Member[]>([]);
const loading = ref(false);

const selectedLabel = computed(() => {
  if (!props.modelValue) return "";
  const found = options.value.find((m) => m.id === props.modelValue);
  if (found) return `${found.name} (${found.member_no})`;
  return "";
});

async function fetchMembers(query: string) {
  if (!query || query.length < 1) {
    options.value = [];
    return;
  }
  loading.value = true;
  try {
    const { data } = await axios.get("/api/v1/members", {
      params: { search: query, per_page: 10 },
    });
    options.value = data.data ?? [];
  } catch {
    options.value = [];
  } finally {
    loading.value = false;
  }
}

const debouncedFetch = useDebounceFn(fetchMembers, 300);

watch(search, (val) => {
  if (val) {
    open.value = true;
    debouncedFetch(val);
  } else {
    open.value = false;
    options.value = [];
  }
});

function selectMember(member: Member) {
  emit("update:modelValue", member.id);
  search.value = "";
  open.value = false;
}

function handleInputBlur() {
  if (props.modelValue) {
    search.value = "";
  }
}
</script>

<template>
  <ComboboxRoot v-model:open="open" v-model:search-term="search">
    <div class="relative">
      <div
        :class="
          cn(
            'flex h-10 w-full items-center rounded-md border bg-white px-3 text-sm dark:bg-zinc-950',
            'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
            open && 'border-ring ring-ring/50 ring-[3px]',
          )
        "
      >
        <Search class="mr-2 h-4 w-4 shrink-0 text-zinc-400" />
        <ComboboxInput
          class="flex-1 bg-transparent text-sm outline-none placeholder:text-zinc-400"
          :placeholder="selectedLabel || placeholder"
          autocomplete="off"
          @blur="handleInputBlur"
        />
        <template v-if="loading">
          <span class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600" />
        </template>
        <ChevronsUpDown v-else class="h-4 w-4 shrink-0 text-zinc-400" />
      </div>
    </div>

    <ComboboxPortal>
      <ComboboxContent
        class="z-50 max-h-64 w-[var(--reka-popper-anchor-width)] overflow-auto rounded-md border bg-white shadow-md dark:bg-zinc-950"
        :side-offset="4"
      >
        <ComboboxViewport>
          <ComboboxEmpty class="px-3 py-6 text-center text-sm text-zinc-500">
            Anggota tidak ditemukan.
          </ComboboxEmpty>
          <ComboboxGroup>
            <ComboboxItem
              v-for="member in options"
              :key="member.id"
              :value="String(member.id)"
              class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-3 py-2 text-sm outline-none data-[highlighted]:bg-zinc-100 dark:data-[highlighted]:bg-zinc-800"
              @select="selectMember(member)"
            >
              <ComboboxItemIndicator class="shrink-0">
                <Check class="h-4 w-4 text-indigo-600" />
              </ComboboxItemIndicator>
              <span>{{ member.name }}</span>
              <span class="ml-auto text-xs text-zinc-400">{{ member.member_no }}</span>
            </ComboboxItem>
          </ComboboxGroup>
        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxPortal>
  </ComboboxRoot>
</template>
