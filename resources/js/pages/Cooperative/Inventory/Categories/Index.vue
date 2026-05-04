<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { PackagePlus, Trash2 } from "lucide-vue-next";
import { ref } from "vue";
import {
  destroy,
  index,
  store,
  update,
} from "@/routes/cooperative/pos-categories";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";

const props = defineProps<{ categories: any[] }>();
const editingId = ref<number | null>(null);
const form = useForm({ name: "", slug: "", is_active: true });

const reset = () => {
  editingId.value = null;
  form.reset();
  form.is_active = true;
};

const edit = (category: any) => {
  editingId.value = category.id;
  form.name = category.name;
  form.slug = category.slug;
  form.is_active = category.is_active;
};

const submit = () => {
  if (editingId.value) {
    form.put(update(editingId.value).url, {
      preserveScroll: true,
      onSuccess: reset,
    });
    return;
  }

  form.post(store().url, { preserveScroll: true, onSuccess: reset });
};
</script>

<template>
  <Head title="Kategori POS" />
  <AppLayout
    :breadcrumbs="[
      { title: 'Inventory POS', href: '#' },
      { title: 'Kategori', href: index().url },
    ]"
  >
    <div
      class="mx-auto grid w-full max-w-6xl gap-6 p-6 lg:grid-cols-[360px_1fr]"
    >
      <form
        class="rounded-lg border bg-white p-4 dark:bg-zinc-900"
        @submit.prevent="submit"
      >
        <div class="flex items-center gap-2">
          <PackagePlus class="h-5 w-5" />
          <h1 class="text-xl font-semibold">
            {{ editingId ? "Edit Kategori" : "Kategori Baru" }}
          </h1>
        </div>
        <div class="mt-4 space-y-3">
          <label class="space-y-1">
            <span class="text-sm">Nama</span>
            <Input v-model="form.name" required />
          </label>
          <label class="space-y-1">
            <span class="text-sm">Slug</span>
            <Input v-model="form.slug" placeholder="auto dari nama" />
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="form.is_active" type="checkbox" />
            Aktif
          </label>
          <div class="flex gap-2">
            <Button class="flex-1" type="submit" :disabled="form.processing"
              >Simpan</Button
            >
            <Button type="button" variant="outline" @click="reset"
              >Reset</Button
            >
          </div>
        </div>
      </form>

      <div class="overflow-hidden rounded-lg border bg-white dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
          <thead
            class="border-b bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"
          >
            <tr>
              <th class="px-4 py-3">Kategori</th>
              <th>Produk</th>
              <th>Status</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="category in props.categories" :key="category.id">
              <td class="px-4 py-3">
                <div class="font-medium">{{ category.name }}</div>
                <div class="text-xs text-zinc-500">{{ category.slug }}</div>
              </td>
              <td>{{ category.products_count }}</td>
              <td>{{ category.is_active ? "ACTIVE" : "INACTIVE" }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2">
                  <Button size="sm" variant="outline" @click="edit(category)"
                    >Edit</Button
                  >
                  <Button
                    size="sm"
                    variant="outline"
                    @click="router.delete(destroy(category.id).url)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
