<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import {
  Plus,
  Edit,
  Trash2,
  Eye,
  Download,
  AlertCircle,
  CheckCircle2,
  Info,
  Loader2,
  Mail,
  ArrowRight,
  LayoutGrid,
  ShieldCheck,
  MousePointerClick,
} from "lucide-vue-next";
import { Alert, AlertTitle, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from "@/components/ui/card";
import DataTable from "@/components/ui/data-table/DataTable.vue";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogClose,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import PageHeader from "@/components/ui/page-header/PageHeader.vue";
import StatusBadge from "@/components/ui/status-badge/StatusBadge.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import { formatCurrency } from "@/lib/formatters";

const mockData = {
  data: [
    {
      id: 1,
      user: { name: "John Doe" },
      amount: 150000,
      status: "APPROVED",
      date: "2024-03-01",
    },
    {
      id: 2,
      user: { name: "Jane Smith" },
      amount: 75000,
      status: "PENDING",
      date: "2024-03-02",
    },
    {
      id: 3,
      user: { name: "Bob Johnson" },
      amount: 500000,
      status: "REJECTED",
      date: "2024-02-28",
    },
    {
      id: 4,
      user: { name: "Alice Brown" },
      amount: 1200000,
      status: "PAID",
      date: "2024-02-25",
    },
    {
      id: 5,
      user: { name: "Charlie Wilson" },
      amount: 25000,
      status: "DRAFT",
      date: "2024-03-03",
    },
  ],
  links: [
    { url: null, label: "&laquo; Previous", active: false },
    { url: "#", label: "1", active: true },
    { url: "#", label: "Next &raquo;", active: false },
  ],
  from: 1,
  to: 5,
  total: 5,
  last_page: 1,
  current_page: 1,
  path: "/settings/components",
  per_page: 10,
};

const columns = [
  { header: "Date", key: "date" },
  { header: "User", key: "user.name" },
  {
    header: "Amount",
    key: "amount",
    format: (val: number) => formatCurrency(val),
    align: "right" as const,
  },
  { header: "Status", key: "status", align: "center" as const },
  { header: "Actions", slot: "actions", align: "right" as const },
];

const handleRowClick = (_row: any) => {};

const sectionCardClass =
  "overflow-hidden border-zinc-200/80 bg-white/95 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:bg-zinc-900/80";

const previewPanelClass =
  "rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-zinc-900 dark:to-zinc-950";
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      { title: 'Settings', href: '/settings' },
      { title: 'UI Components', href: '#' },
    ]"
  >
    <Head title="UI Components" />

    <SettingsLayout
      content-wrapper-class="md:max-w-none"
      content-class="max-w-none space-y-8"
    >
      <div class="space-y-8">
        <div
          class="relative overflow-hidden rounded-3xl border border-zinc-200/80 bg-gradient-to-br from-white via-emerald-50/80 to-zinc-100 p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-zinc-950 dark:via-emerald-950/20 dark:to-zinc-900 sm:p-8"
        >
          <div
            class="absolute inset-y-0 right-0 hidden w-1/3 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,0.18),_transparent_58%)] lg:block"
          />

          <div
            class="relative grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]"
          >
            <div class="space-y-5">
              <div class="flex flex-wrap gap-2">
                <span
                  class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                  Design System Preview
                </span>
                <span
                  class="inline-flex items-center rounded-full border border-zinc-200 bg-white/80 px-3 py-1 text-xs font-medium text-zinc-600 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-300"
                >
                  Light and dark ready
                </span>
              </div>

              <div class="space-y-3">
                <h2
                  class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50"
                >
                  Komponen yang lebih rapi, konsisten, dan terasa profesional.
                </h2>
                <p
                  class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                >
                  Halaman ini menampilkan fondasi UI yang dipakai di banyak
                  modul: tombol, feedback states, dialog, badge status, header
                  halaman, dan tabel data. Fokus perbaikannya ada pada
                  hierarchy, spacing, visual depth, serta presentasi komponen
                  pada berbagai surface.
                </p>
              </div>

              <div class="flex flex-wrap gap-3">
                <Button
                  class="shadow-sm shadow-emerald-950/15 hover:-translate-y-0.5 hover:shadow-md hover:shadow-emerald-950/20"
                >
                  <MousePointerClick class="h-4 w-4" />
                  Primary Action
                </Button>
                <Button
                  variant="outline"
                  class="bg-white/80 shadow-sm shadow-zinc-950/5 hover:bg-white dark:bg-zinc-900/70 dark:hover:bg-zinc-900"
                >
                  <LayoutGrid class="h-4 w-4" />
                  Explore Patterns
                </Button>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
              <div
                class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-700/80 dark:bg-zinc-900/70"
              >
                <div
                  class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400"
                >
                  Button polish
                </div>
                <div
                  class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-zinc-50"
                >
                  Subtle depth
                </div>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                  Shadow ringan, hover state, dan contrast yang lebih
                  meyakinkan.
                </p>
              </div>
              <div
                class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-700/80 dark:bg-zinc-900/70"
              >
                <div
                  class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400"
                >
                  Page structure
                </div>
                <div
                  class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-zinc-50"
                >
                  Better hierarchy
                </div>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                  Section showcase dipisah dengan card surface yang lebih
                  konsisten.
                </p>
              </div>
              <div
                class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-700/80 dark:bg-zinc-900/70"
              >
                <div
                  class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400"
                >
                  Readability
                </div>
                <div
                  class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-zinc-50"
                >
                  Cleaner demos
                </div>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                  Preview setiap komponen lebih mudah dipindai dan dibandingkan.
                </p>
              </div>
            </div>
          </div>
        </div>

        <Card :class="sectionCardClass">
          <CardHeader
            class="border-b border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-950/40"
          >
            <CardTitle class="text-base">Buttons</CardTitle>
            <CardDescription>
              Button variants, sizes, and interaction patterns with stronger
              visual depth.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div
              class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]"
            >
              <div class="grid gap-4 lg:grid-cols-2">
                <div :class="previewPanelClass">
                  <div
                    class="mb-3 text-sm font-medium text-zinc-600 dark:text-zinc-300"
                  >
                    Light surface
                  </div>
                  <div class="flex flex-wrap gap-3">
                    <Button
                      class="shadow-sm shadow-emerald-950/15 hover:-translate-y-0.5 hover:shadow-md hover:shadow-emerald-950/20"
                    >
                      Primary Action
                    </Button>
                    <Button
                      variant="secondary"
                      class="shadow-sm shadow-zinc-950/10"
                    >
                      Secondary Action
                    </Button>
                    <Button
                      variant="outline"
                      class="bg-white shadow-sm shadow-zinc-950/5"
                    >
                      Outline Action
                    </Button>
                  </div>
                </div>
                <div
                  class="rounded-2xl border border-zinc-700/80 bg-zinc-950 p-5 text-zinc-50 shadow-sm shadow-zinc-950/25"
                >
                  <div class="mb-3 text-sm font-medium text-zinc-400">
                    Dark surface
                  </div>
                  <div class="flex flex-wrap gap-3">
                    <Button
                      class="shadow-sm shadow-emerald-950/30 hover:-translate-y-0.5 hover:shadow-md hover:shadow-black/30"
                    >
                      Primary Action
                    </Button>
                    <Button
                      variant="secondary"
                      class="shadow-sm shadow-black/25"
                    >
                      Secondary Action
                    </Button>
                    <Button
                      variant="outline"
                      class="border-zinc-700 bg-zinc-900 shadow-sm shadow-black/20 hover:bg-zinc-800"
                    >
                      Outline Action
                    </Button>
                  </div>
                </div>
              </div>

              <div
                class="rounded-2xl border border-emerald-200/70 bg-emerald-50/70 p-5 shadow-sm shadow-emerald-950/5 dark:border-emerald-500/20 dark:bg-emerald-500/10"
              >
                <div class="flex items-start gap-3">
                  <div
                    class="rounded-xl bg-white p-2 text-emerald-600 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-300"
                  >
                    <ShieldCheck class="h-5 w-5" />
                  </div>
                  <div class="space-y-2">
                    <h3
                      class="text-sm font-semibold text-zinc-950 dark:text-zinc-50"
                    >
                      Recommended button treatment
                    </h3>
                    <p
                      class="text-sm leading-6 text-zinc-600 dark:text-zinc-300"
                    >
                      Gunakan shadow halus untuk primary action, outline yang
                      tetap terang di atas surface berbeda, dan hover state
                      kecil agar terasa hidup tanpa berlebihan.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-2">
                      <Button size="sm" class="shadow-sm shadow-emerald-950/15">
                        Approve Request
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        class="bg-white shadow-sm shadow-zinc-950/5 dark:bg-zinc-900"
                      >
                        Review First
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
              <div class="space-y-2">
                <h3 class="text-sm font-medium text-zinc-500">Variants</h3>
                <div :class="previewPanelClass">
                  <div class="flex flex-wrap gap-2">
                    <Button class="shadow-sm shadow-emerald-950/15"
                      >Default</Button
                    >
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="destructive">Destructive</Button>
                    <Button variant="outline" class="bg-white dark:bg-zinc-900"
                      >Outline</Button
                    >
                    <Button variant="ghost">Ghost</Button>
                    <Button variant="link">Link</Button>
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <h3 class="text-sm font-medium text-zinc-500">Sizes</h3>
                <div :class="previewPanelClass">
                  <div class="flex flex-wrap items-center gap-2">
                    <Button size="lg" class="shadow-sm shadow-emerald-950/15"
                      >Large</Button
                    >
                    <Button class="shadow-sm shadow-emerald-950/15"
                      >Default</Button
                    >
                    <Button size="sm" class="shadow-sm shadow-emerald-950/15"
                      >Small</Button
                    >
                    <Button size="icon" class="shadow-sm shadow-emerald-950/15">
                      <Plus class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <h3 class="text-sm font-medium text-zinc-500">With Icons</h3>
                <div :class="previewPanelClass">
                  <div class="flex flex-wrap gap-2">
                    <Button class="shadow-sm shadow-emerald-950/15">
                      <Mail class="mr-2 h-4 w-4" /> Login with Email
                    </Button>
                    <Button
                      variant="outline"
                      class="bg-white shadow-sm shadow-zinc-950/5 dark:bg-zinc-900"
                    >
                      <Loader2 class="mr-2 h-4 w-4 animate-spin" /> Please wait
                    </Button>
                    <Button class="shadow-sm shadow-emerald-950/15">
                      Next Step
                      <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <h3 class="text-sm font-medium text-zinc-500">Action Buttons</h3>
              <div :class="previewPanelClass">
                <div class="flex flex-wrap gap-2">
                  <Button
                    variant="outline"
                    size="icon"
                    title="Edit"
                    class="bg-white shadow-sm shadow-zinc-950/5 dark:bg-zinc-900"
                  >
                    <Edit class="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="icon"
                    class="border-destructive/50 bg-white text-destructive shadow-sm shadow-zinc-950/5 hover:border-destructive hover:bg-destructive/10 hover:text-destructive dark:bg-zinc-900"
                    title="Delete"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" title="View">
                    <Eye class="h-4 w-4" />
                  </Button>
                  <Button
                    variant="secondary"
                    size="icon"
                    title="Download"
                    class="shadow-sm shadow-zinc-950/10"
                  >
                    <Download class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <div
          class="grid gap-8 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]"
        >
          <Card :class="sectionCardClass">
            <CardHeader
              class="border-b border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-950/40"
            >
              <CardTitle class="text-base">Alerts</CardTitle>
              <CardDescription>
                Callout patterns for system feedback, warnings, and success
                states.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div :class="previewPanelClass">
                <Alert
                  class="border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
                >
                  <Info class="h-4 w-4" />
                  <AlertTitle>Heads up!</AlertTitle>
                  <AlertDescription>
                    You can add components to your app using the cli.
                  </AlertDescription>
                </Alert>
              </div>

              <div :class="previewPanelClass">
                <Alert variant="destructive">
                  <AlertCircle class="h-4 w-4" />
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>
                    Your session has expired. Please log in again.
                  </AlertDescription>
                </Alert>
              </div>

              <div :class="previewPanelClass">
                <div
                  class="relative grid w-full grid-cols-[auto_1fr] items-start gap-x-3 gap-y-0.5 rounded-xl border border-emerald-500/30 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                  <CheckCircle2
                    class="h-4 w-4 translate-y-0.5 text-emerald-600 dark:text-emerald-400"
                  />
                  <h5 class="font-medium leading-none tracking-tight">
                    Success
                  </h5>
                  <div class="text-sm leading-relaxed">
                    Your changes have been saved successfully.
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card :class="sectionCardClass">
            <CardHeader
              class="border-b border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-950/40"
            >
              <CardTitle class="text-base">Dialog and Status</CardTitle>
              <CardDescription>
                Overlay actions and standardized status language for
                transactional flows.
              </CardDescription>
            </CardHeader>
            <CardContent
              class="grid gap-6 lg:grid-cols-[minmax(0,0.75fr)_minmax(0,1fr)]"
            >
              <div :class="previewPanelClass">
                <div class="mb-4 space-y-1">
                  <h3
                    class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                  >
                    Modals (Dialog)
                  </h3>
                  <p class="text-sm text-zinc-500">
                    Cocok untuk konfirmasi aksi penting atau form singkat.
                  </p>
                </div>
                <Dialog>
                  <DialogTrigger as-child>
                    <Button class="shadow-sm shadow-emerald-950/15"
                      >Open Dialog</Button
                    >
                  </DialogTrigger>
                  <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                      <DialogTitle>Edit Profile</DialogTitle>
                      <DialogDescription>
                        Make changes to your profile here. Click save when
                        you're done.
                      </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right"> Name </Label>
                        <Input
                          id="name"
                          value="Pedro Duarte"
                          class="col-span-3"
                        />
                      </div>
                      <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="username" class="text-right">
                          Username
                        </Label>
                        <Input
                          id="username"
                          value="@peduarte"
                          class="col-span-3"
                        />
                      </div>
                    </div>
                    <DialogFooter>
                      <DialogClose as-child>
                        <Button type="submit">Save changes</Button>
                      </DialogClose>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </div>

              <div :class="previewPanelClass">
                <div class="mb-4 space-y-1">
                  <h3
                    class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                  >
                    Status Badges
                  </h3>
                  <p class="text-sm text-zinc-500">
                    Status dirancang ringkas dan mudah dipindai di tabel maupun
                    cards.
                  </p>
                </div>
                <div class="flex flex-wrap gap-3">
                  <StatusBadge status="APPROVED" />
                  <StatusBadge status="PENDING" />
                  <StatusBadge status="REJECTED" />
                  <StatusBadge status="PAID" />
                  <StatusBadge status="DRAFT" />
                  <StatusBadge status="SUBMITTED" />
                  <StatusBadge status="CANCELLED" />
                  <StatusBadge status="CUSTOM" variant="secondary" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card :class="sectionCardClass">
          <CardHeader
            class="border-b border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-950/40"
          >
            <CardTitle class="text-base">Page Header</CardTitle>
            <CardDescription>
              Standard header with breadcrumbs and actions, presented on a
              cleaner surface.
            </CardDescription>
          </CardHeader>
          <CardContent class="py-6">
            <div
              class="rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-zinc-900 dark:to-zinc-950"
            >
              <PageHeader
                title="Example Page"
                description="This is how a page header looks."
                :breadcrumbs="[
                  { title: 'Dashboard', href: '/dashboard' },
                  { title: 'Settings', href: '/settings' },
                  { title: 'Components' },
                ]"
              >
                <template #actions>
                  <Button
                    variant="outline"
                    class="bg-white shadow-sm shadow-zinc-950/5 dark:bg-zinc-900"
                  >
                    Export
                  </Button>
                  <Button class="shadow-sm shadow-emerald-950/15">
                    <Plus class="mr-2 h-4 w-4" />
                    New Item
                  </Button>
                </template>
              </PageHeader>
            </div>
          </CardContent>
        </Card>

        <Card :class="sectionCardClass">
          <CardHeader
            class="border-b border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-800 dark:bg-zinc-950/40"
          >
            <CardTitle class="text-base">Data Table</CardTitle>
            <CardDescription>
              Advanced table with search, pagination, and clearer row actions.
            </CardDescription>
          </CardHeader>
          <CardContent class="px-0 pb-0">
            <div class="px-6 pt-6">
              <div
                class="rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 p-4 shadow-sm shadow-zinc-950/5 dark:border-zinc-800/80 dark:from-zinc-900 dark:to-zinc-950"
              >
                <DataTable
                  :columns="columns"
                  :data="mockData"
                  search-placeholder="Search transactions..."
                  @row-click="handleRowClick"
                  row-clickable
                >
                  <template #actions>
                    <div class="flex items-center justify-end gap-2">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-zinc-500 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-500/10"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-zinc-500 hover:bg-destructive/10 hover:text-destructive"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </template>
                </DataTable>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
