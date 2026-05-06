<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Plus, Pencil, Trash2, Upload } from "lucide-vue-next";
import { computed, ref } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { formatCurrency } from "@/lib/formatters";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import type { BreadcrumbItem } from "@/types";

type Project = { id: string; project_code: string; name: string };

const props = defineProps<{
  budget: any;
  projects: Project[];
  can: { edit: boolean; editLines: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "#" },
  { title: "RKAP", href: "/budgets" },
  {
    title: `${props.budget.year} (${props.budget.period})`,
    href: `/budgets/${props.budget.id}`,
  },
];

const formatMoney = formatCurrency;

const headerModal = ref(false);
const lineModal = ref(false);
const editLineModal = ref(false);
const importModal = ref(false);
const editLineTarget = ref<any>(null);
const deleteLineDialogOpen = ref(false);
const deleteLineTarget = ref<any>(null);

const updateHeaderPeriod = (value: unknown) => {
  headerForm.period = value == null ? "ANNUAL" : String(value);
};

const updateHeaderStatus = (value: unknown) => {
  headerForm.status = value == null ? "DRAFT" : String(value);
};

const updateLineCategory = (value: unknown) => {
  lineForm.category = value == null ? "OPEX" : String(value);
};

const updateLineProjectId = (value: unknown) => {
  lineForm.project_id =
    value === "__none__" || value == null ? "" : String(value);
};

const headerForm = useForm({
  organization_id: props.budget.organization_id,
  year: props.budget.year,
  period: props.budget.period,
  status: props.budget.status,
});

const saveHeader = () => {
  headerForm.put(`/budgets/${props.budget.id}`, {
    onSuccess: () => (headerModal.value = false),
  });
};

const lineForm = useForm({
  cost_center: "",
  project_id: "",
  gl_account: "",
  category: "OPEX",
  allocated_amount: "",
});

const openAddLine = () => {
  editLineTarget.value = null;
  lineForm.reset();
  lineForm.category = "OPEX";
  lineModal.value = true;
};

const addLine = () => {
  lineForm.post(`/budgets/${props.budget.id}/lines`, {
    onSuccess: () => {
      lineModal.value = false;
      lineForm.reset();
      lineForm.category = "OPEX";
    },
  });
};

const openEditLine = (line: any) => {
  editLineTarget.value = line;
  lineForm.cost_center = line.cost_center ?? "";
  lineForm.project_id = line.project_id ?? "";
  lineForm.gl_account = line.gl_account;
  lineForm.category = line.category;
  lineForm.allocated_amount = String(line.allocated_amount ?? "");
  editLineModal.value = true;
};

const updateLine = () => {
  lineForm.put(`/budgets/${props.budget.id}/lines/${editLineTarget.value.id}`, {
    onSuccess: () => {
      editLineModal.value = false;
      editLineTarget.value = null;
      lineForm.reset();
      lineForm.category = "OPEX";
    },
  });
};

const importForm = useForm({
  file: null as File | null,
});

const importBudget = () => {
  importForm.post(`/budgets/${props.budget.id}/import`, {
    onSuccess: () => {
      importModal.value = false;
      importForm.reset();
    },
  });
};

const destroyLine = (line: any) => {
  deleteLineTarget.value = line;
  deleteLineDialogOpen.value = true;
};

const confirmDestroyLine = (): void => {
  if (!deleteLineTarget.value) {
    return;
  }

  router.delete(
    `/budgets/${props.budget.id}/lines/${deleteLineTarget.value.id}`,
    {
      onFinish: () => {
        deleteLineDialogOpen.value = false;
        deleteLineTarget.value = null;
      },
    },
  );
};

const totals = computed(() => {
  const lines = props.budget.lines ?? [];
  const allocated = lines.reduce(
    (s: number, l: any) => s + Number(l.allocated_amount ?? 0),
    0,
  );
  const committed = lines.reduce(
    (s: number, l: any) => s + Number(l.committed_amount ?? 0),
    0,
  );
  const realized = lines.reduce(
    (s: number, l: any) => s + Number(l.realized_amount ?? 0),
    0,
  );
  return {
    allocated,
    committed,
    realized,
    available: allocated - committed - realized,
  };
});
</script>

<template>
  <Head :title="`RKAP ${budget.year} (${budget.period})`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 w-full">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <Button variant="outline" size="icon" as-child>
              <Link href="/budgets"><ArrowLeft class="h-4 w-4" /></Link>
            </Button>
            <div>
              <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                RKAP {{ budget.year }} ({{ budget.period }})
              </h1>
              <p class="text-zinc-500 mt-1">
                {{ budget.organization?.code }} -
                {{ budget.organization?.name }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <Dialog v-model:open="headerModal">
            <DialogTrigger as-child>
              <Button
                variant="outline"
                :disabled="!can.edit || budget.status !== 'DRAFT'"
              >
                <Pencil class="h-4 w-4 mr-2" />Edit RKAP
              </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
              <DialogHeader><DialogTitle>Edit RKAP</DialogTitle></DialogHeader>
              <form @submit.prevent="saveHeader" class="space-y-4 mt-2">
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label>Tahun</Label>
                    <Input
                      v-model="headerForm.year"
                      inputmode="numeric"
                      required
                    />
                    <span
                      v-if="headerForm.errors.year"
                      class="text-xs text-red-500"
                      >{{ headerForm.errors.year }}</span
                    >
                  </div>
                  <div class="grid gap-2">
                    <Label>Periode</Label>
                    <Select
                      :model-value="headerForm.period"
                      @update:model-value="updateHeaderPeriod"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Pilih periode" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="ANNUAL">ANNUAL</SelectItem>
                        <SelectItem value="Q1">Q1</SelectItem>
                        <SelectItem value="Q2">Q2</SelectItem>
                        <SelectItem value="Q3">Q3</SelectItem>
                        <SelectItem value="Q4">Q4</SelectItem>
                      </SelectContent>
                    </Select>
                    <span
                      v-if="headerForm.errors.period"
                      class="text-xs text-red-500"
                      >{{ headerForm.errors.period }}</span
                    >
                  </div>
                </div>

                <div class="grid gap-2">
                  <Label>Status</Label>
                  <Select
                    :model-value="headerForm.status"
                    @update:model-value="updateHeaderStatus"
                  >
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Pilih status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="DRAFT">DRAFT</SelectItem>
                      <SelectItem value="ACTIVE">ACTIVE</SelectItem>
                      <SelectItem value="CLOSED">CLOSED</SelectItem>
                    </SelectContent>
                  </Select>
                  <span
                    v-if="headerForm.errors.status"
                    class="text-xs text-red-500"
                    >{{ headerForm.errors.status }}</span
                  >
                </div>

                <div class="flex justify-end gap-2 pt-2">
                  <Button
                    type="button"
                    variant="outline"
                    @click="headerModal = false"
                    >Batal</Button
                  >
                  <Button type="submit" :disabled="headerForm.processing"
                    >Simpan</Button
                  >
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div
          class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4"
        >
          <div class="text-xs text-zinc-500">Allocated</div>
          <div class="text-lg font-semibold text-zinc-900 dark:text-white">
            {{ formatMoney(totals.allocated) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4"
        >
          <div class="text-xs text-zinc-500">Committed</div>
          <div class="text-lg font-semibold text-zinc-900 dark:text-white">
            {{ formatMoney(totals.committed) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4"
        >
          <div class="text-xs text-zinc-500">Realized</div>
          <div class="text-lg font-semibold text-zinc-900 dark:text-white">
            {{ formatMoney(totals.realized) }}
          </div>
        </div>
        <div
          class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4"
        >
          <div class="text-xs text-zinc-500">Available</div>
          <div class="text-lg font-semibold text-zinc-900 dark:text-white">
            {{ formatMoney(totals.available) }}
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
          Budget Lines
        </h2>

        <div class="flex gap-2">
          <Dialog v-model:open="importModal">
            <DialogTrigger as-child>
              <Button variant="outline" :disabled="!can.editLines">
                <Upload class="h-4 w-4 mr-2" />Import Excel
              </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
              <DialogHeader
                ><DialogTitle>Import Budget Lines</DialogTitle></DialogHeader
              >
              <form @submit.prevent="importBudget" class="space-y-4 mt-2">
                <div class="grid gap-2">
                  <Label>File Excel/CSV</Label>
                  <Input
                    type="file"
                    @input="importForm.file = $event.target.files[0]"
                    accept=".xlsx,.xls,.csv"
                    required
                  />
                  <span
                    v-if="importForm.errors.file"
                    class="text-xs text-red-500"
                    >{{ importForm.errors.file }}</span
                  >
                  <p class="text-xs text-zinc-500">
                    Format: gl_account, category (OPEX/CAPEX), allocated_amount,
                    cost_center, project_id
                  </p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                  <Button
                    type="button"
                    variant="outline"
                    @click="importModal = false"
                    >Batal</Button
                  >
                  <Button type="submit" :disabled="importForm.processing"
                    >Upload</Button
                  >
                </div>
              </form>
            </DialogContent>
          </Dialog>

          <Dialog v-model:open="lineModal">
            <DialogTrigger as-child>
              <Button @click="openAddLine" :disabled="!can.editLines"
                ><Plus class="h-4 w-4 mr-2" />Tambah Line</Button
              >
            </DialogTrigger>
            <DialogContent class="sm:max-w-lg">
              <DialogHeader
                ><DialogTitle>Tambah Budget Line</DialogTitle></DialogHeader
              >
              <form @submit.prevent="addLine" class="space-y-4 mt-2">
                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label>GL Account</Label>
                    <Input
                      v-model="lineForm.gl_account"
                      placeholder="6100-OPX"
                      required
                    />
                    <span
                      v-if="lineForm.errors.gl_account"
                      class="text-xs text-red-500"
                      >{{ lineForm.errors.gl_account }}</span
                    >
                  </div>
                  <div class="grid gap-2">
                    <Label>Kategori</Label>
                    <Select
                      :model-value="lineForm.category"
                      @update:model-value="updateLineCategory"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Pilih kategori" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="OPEX">OPEX</SelectItem>
                        <SelectItem value="CAPEX">CAPEX</SelectItem>
                      </SelectContent>
                    </Select>
                    <span
                      v-if="lineForm.errors.category"
                      class="text-xs text-red-500"
                      >{{ lineForm.errors.category }}</span
                    >
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="grid gap-2">
                    <Label>Cost Center</Label>
                    <Input
                      v-model="lineForm.cost_center"
                      placeholder="CC-001"
                    />
                    <span
                      v-if="lineForm.errors.cost_center"
                      class="text-xs text-red-500"
                      >{{ lineForm.errors.cost_center }}</span
                    >
                  </div>
                  <div class="grid gap-2">
                    <Label>Project (opsional)</Label>
                    <Select
                      :model-value="lineForm.project_id || '__none__'"
                      @update:model-value="updateLineProjectId"
                    >
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Tanpa project" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="__none__">Tanpa project</SelectItem>
                        <SelectItem v-for="p in projects" :key="p.id" :value="p.id">
                          {{ p.project_code }} - {{ p.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <span
                      v-if="lineForm.errors.project_id"
                      class="text-xs text-red-500"
                      >{{ lineForm.errors.project_id }}</span
                    >
                  </div>
                </div>

                <div class="grid gap-2">
                  <Label>Allocated Amount</Label>
                  <Input
                    v-model="lineForm.allocated_amount"
                    inputmode="decimal"
                    placeholder="10000000"
                    required
                  />
                  <span
                    v-if="lineForm.errors.allocated_amount"
                    class="text-xs text-red-500"
                    >{{ lineForm.errors.allocated_amount }}</span
                  >
                </div>

                <div class="flex justify-end gap-2 pt-2">
                  <Button
                    type="button"
                    variant="outline"
                    @click="lineModal = false"
                    >Batal</Button
                  >
                  <Button type="submit" :disabled="lineForm.processing"
                    >Simpan</Button
                  >
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <Dialog v-model:open="editLineModal">
        <DialogContent class="sm:max-w-lg">
          <DialogHeader
            ><DialogTitle>Edit Budget Line</DialogTitle></DialogHeader
          >
          <form @submit.prevent="updateLine" class="space-y-4 mt-2">
            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>GL Account</Label>
                <Input v-model="lineForm.gl_account" required />
                <span
                  v-if="lineForm.errors.gl_account"
                  class="text-xs text-red-500"
                  >{{ lineForm.errors.gl_account }}</span
                >
              </div>
              <div class="grid gap-2">
                <Label>Kategori</Label>
                <Select
                  :model-value="lineForm.category"
                  @update:model-value="updateLineCategory"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Pilih kategori" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="OPEX">OPEX</SelectItem>
                    <SelectItem value="CAPEX">CAPEX</SelectItem>
                  </SelectContent>
                </Select>
                <span
                  v-if="lineForm.errors.category"
                  class="text-xs text-red-500"
                  >{{ lineForm.errors.category }}</span
                >
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="grid gap-2">
                <Label>Cost Center</Label>
                <Input v-model="lineForm.cost_center" />
                <span
                  v-if="lineForm.errors.cost_center"
                  class="text-xs text-red-500"
                  >{{ lineForm.errors.cost_center }}</span
                >
              </div>
              <div class="grid gap-2">
                <Label>Project (opsional)</Label>
                <Select
                  :model-value="lineForm.project_id || '__none__'"
                  @update:model-value="updateLineProjectId"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tanpa project" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__none__">Tanpa project</SelectItem>
                    <SelectItem v-for="p in projects" :key="p.id" :value="p.id">
                      {{ p.project_code }} - {{ p.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <span
                  v-if="lineForm.errors.project_id"
                  class="text-xs text-red-500"
                  >{{ lineForm.errors.project_id }}</span
                >
              </div>
            </div>

            <div class="grid gap-2">
              <Label>Allocated Amount</Label>
              <Input
                v-model="lineForm.allocated_amount"
                inputmode="decimal"
                required
              />
              <span
                v-if="lineForm.errors.allocated_amount"
                class="text-xs text-red-500"
                >{{ lineForm.errors.allocated_amount }}</span
              >
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <Button
                type="button"
                variant="outline"
                @click="editLineModal = false"
                >Batal</Button
              >
              <Button type="submit" :disabled="lineForm.processing"
                >Simpan</Button
              >
            </div>
          </form>
        </DialogContent>
      </Dialog>

      <div
        class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden"
      >
        <table class="w-full text-sm text-left">
          <thead
            class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
          >
            <tr>
              <th class="px-5 py-4">GL</th>
              <th class="px-5 py-4">Kategori</th>
              <th class="px-5 py-4">Cost Center</th>
              <th class="px-5 py-4">Project</th>
              <th class="px-5 py-4 text-right">Allocated</th>
              <th class="px-5 py-4 text-right">Committed</th>
              <th class="px-5 py-4 text-right">Realized</th>
              <th class="px-5 py-4 text-right">Available</th>
              <th class="px-5 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            <tr
              v-for="l in budget.lines"
              :key="l.id"
              class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
            >
              <td
                class="px-5 py-4 font-mono font-semibold text-indigo-600 dark:text-indigo-400"
              >
                {{ l.gl_account }}
              </td>
              <td class="px-5 py-4 text-zinc-500">{{ l.category }}</td>
              <td class="px-5 py-4 text-zinc-500">
                {{ l.cost_center ?? "-" }}
              </td>
              <td class="px-5 py-4 text-zinc-500">
                <span v-if="l.project"
                  >{{ l.project.project_code }} - {{ l.project.name }}</span
                >
                <span v-else>-</span>
              </td>
              <td class="px-5 py-4 text-right text-zinc-500">
                {{ formatMoney(l.allocated_amount) }}
              </td>
              <td class="px-5 py-4 text-right text-zinc-500">
                {{ formatMoney(l.committed_amount) }}
              </td>
              <td class="px-5 py-4 text-right text-zinc-500">
                {{ formatMoney(l.realized_amount) }}
              </td>
              <td class="px-5 py-4 text-right text-zinc-500">
                {{
                  formatMoney(
                    Number(l.allocated_amount ?? 0) -
                      Number(l.committed_amount ?? 0) -
                      Number(l.realized_amount ?? 0),
                  )
                }}
              </td>
              <td class="px-5 py-4 flex justify-end gap-2">
                <Button
                  size="icon"
                  variant="ghost"
                  @click="openEditLine(l)"
                  :disabled="!can.editLines"
                >
                  <Pencil class="h-4 w-4" />
                </Button>
                <Button
                  size="icon"
                  variant="ghost"
                  class="text-red-500"
                  @click="destroyLine(l)"
                  :disabled="!can.editLines"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </td>
            </tr>
            <tr v-if="!budget.lines?.length">
              <td colspan="9" class="py-12 text-center text-zinc-500">
                Belum ada budget lines.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog
      v-model:open="deleteLineDialogOpen"
      variant="danger"
      title="Hapus budget line"
      :message="
        deleteLineTarget
          ? `Apakah Anda yakin ingin menghapus budget line ${deleteLineTarget.gl_account}?`
          : 'Apakah Anda yakin ingin menghapus budget line ini?'
      "
      confirm-label="Hapus"
      @confirm="confirmDestroyLine"
    />
  </AppLayout>
</template>
