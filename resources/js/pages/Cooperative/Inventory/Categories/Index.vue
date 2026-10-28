<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Plus, Trash2 } from "lucide-vue-next";
import { ref } from "vue";
import WorkspaceHeader from "@/components/dashboard/WorkspaceHeader.vue";
import WorkspaceSummary from "@/components/dashboard/WorkspaceSummary.vue";
import InputError from "@/components/InputError.vue";
import PageContainer from "@/components/PageContainer.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/AppLayout.vue";
import {
  destroy,
  index,
  store,
  update,
} from "@/routes/cooperative/pos-categories";

const props = defineProps<{ categories: any[] }>();
const editorOpen = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: "", slug: "", is_active: true });

const reset = () => {
  editorOpen.value = false;
  form.clearErrors();
  editingId.value = null;
  form.reset();
  form.is_active = true;
};

const edit = (category: any) => {
  editorOpen.value = true;
  form.clearErrors();
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
    <PageContainer class="max-w-none gap-4">
      <WorkspaceHeader
        title="Kategori POS"
        description="Kelola pengelompokan katalog dan status kategori produk."
        ><Button
          size="sm"
          @click="
            reset();
            editorOpen = true;
          "
          ><Plus class="size-4" /> Tambah Kategori</Button
        ></WorkspaceHeader
      >
      <WorkspaceSummary
        :items="[
          { label: 'Total Kategori', value: categories.length },
          {
            label: 'Aktif',
            value: categories.filter((category) => category.is_active).length,
            tone: 'emerald',
          },
          {
            label: 'Nonaktif',
            value: categories.filter((category) => !category.is_active).length,
          },
        ]"
      />
      <Dialog v-model:open="editorOpen"
        ><DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-lg"
          ><DialogHeader
            ><DialogTitle>{{
              editingId ? "Edit Kategori" : "Tambah Kategori"
            }}</DialogTitle
            ><DialogDescription
              >Atur nama, slug, dan status kategori katalog
              POS.</DialogDescription
            ></DialogHeader
          >
          <form class="space-y-3" @submit.prevent="submit">
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
              <InputError
                v-for="(message, field) in form.errors"
                :key="field"
                :message="message"
                role="alert"
              />
              <div class="flex gap-2">
                <Button class="flex-1" type="submit" :disabled="form.processing"
                  >Simpan</Button
                >
                <Button type="button" variant="outline" @click="reset"
                  >Batal</Button
                >
              </div>
            </div>
          </form>
        </DialogContent></Dialog
      >
      <div
        class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900"
      >
        <div
          class="overflow-x-auto"
          role="region"
          aria-label="Daftar kategori POS, geser untuk melihat kolom lainnya"
          tabindex="0"
        >
          <table
            class="w-full min-w-[650px] text-left text-sm [&_th]:px-4 [&_td]:px-4 [&_td]:py-3"
          >
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
                <td>
                  <Badge
                    variant="outline"
                    :class="
                      category.is_active
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'
                    "
                    >{{ category.is_active ? "Aktif" : "Nonaktif" }}</Badge
                  >
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Button size="sm" variant="outline" @click="edit(category)"
                      >Edit</Button
                    >
                    <Button
                      size="sm"
                      variant="outline"
                      aria-label="Hapus kategori"
                      @click="router.delete(destroy(category.id).url)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
              <tr v-if="categories.length === 0">
                <td colspan="4" class="py-10 text-center text-zinc-500">
                  Belum ada kategori. Gunakan Tambah Kategori untuk
                  mengelompokkan produk.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </PageContainer>
  </AppLayout>
</template>
