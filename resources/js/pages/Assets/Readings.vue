<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeft,
  Gauge,
  Plus,
  Calendar as CalendarIcon,
  Save,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

interface Asset {
  id: string;
  code: string;
  name: string;
  readings: Array<{
    id: string;
    reading_value: number;
    reading_unit: string;
    recorded_at: string;
  }>;
}

interface Props {
  asset: Asset;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Asset Management", href: "#" },
  { title: "Assets", href: "/assets" },
  { title: props.asset.name, href: `/assets/${props.asset.id}` },
  { title: "Meter Readings", href: `/assets/${props.asset.id}/readings` },
];

const form = useForm({
  reading_value: "",
  reading_unit: "Hours",
  recorded_at: new Date().toISOString().split("T")[0],
});

const submit = () => {
  form.post(`/assets/${props.asset.id}/readings`, {
    onSuccess: () => {
      form.reset("reading_value");
    },
  });
};
</script>

<template>
  <Head :title="`Readings - ${asset.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 max-w-7xl mx-auto w-full"
    >
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Link :href="`/assets/${asset.id}`">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div class="flex-1">
          <h1
            class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white"
          >
            Meter Readings
          </h1>
          <p class="text-zinc-500 mt-1">{{ asset.name }} ({{ asset.code }})</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Reading Form -->
        <div class="lg:col-span-1">
          <Card class="border-zinc-200 dark:border-zinc-800 shadow-sm">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Plus class="h-5 w-5 text-zinc-500" />
                Add Record
              </CardTitle>
              <CardDescription
                >Input new meter reading or usage.</CardDescription
              >
            </CardHeader>
            <CardContent>
              <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                  <Label for="reading_value">Reading Value</Label>
                  <Input
                    id="reading_value"
                    v-model="form.reading_value"
                    type="number"
                    step="0.01"
                    placeholder="0.00"
                    required
                    class="font-mono"
                  />
                  <p
                    v-if="form.errors.reading_value"
                    class="text-sm text-red-500"
                  >
                    {{ form.errors.reading_value }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="reading_unit">Unit</Label>
                  <select
                    id="reading_unit"
                    v-model="form.reading_unit"
                    class="flex h-10 w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:ring-offset-zinc-950 dark:placeholder:text-zinc-400 dark:focus-visible:ring-zinc-300"
                  >
                    <option value="Hours">Hours (Running Hours)</option>
                    <option value="KM">Kilometers (Mileage)</option>
                    <option value="Units">Units</option>
                  </select>
                  <p
                    v-if="form.errors.reading_unit"
                    class="text-sm text-red-500"
                  >
                    {{ form.errors.reading_unit }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="recorded_at">Date Recorded</Label>
                  <Input
                    id="recorded_at"
                    v-model="form.recorded_at"
                    type="date"
                    required
                  />
                  <p
                    v-if="form.errors.recorded_at"
                    class="text-sm text-red-500"
                  >
                    {{ form.errors.recorded_at }}
                  </p>
                </div>

                <Button
                  type="submit"
                  class="w-full gap-2"
                  :disabled="form.processing"
                >
                  <Save class="h-4 w-4" />
                  {{ form.processing ? "Saving..." : "Save Record" }}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>

        <!-- History Table -->
        <div class="lg:col-span-2">
          <Card
            class="border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden"
          >
            <CardHeader class="bg-zinc-50/50 dark:bg-zinc-800/10">
              <CardTitle class="flex items-center gap-2">
                <Gauge class="h-5 w-5 text-zinc-500" />
                Reading History
              </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
              <div
                v-if="asset.readings.length === 0"
                class="flex flex-col items-center justify-center py-12 text-zinc-500"
              >
                <Gauge
                  class="h-12 w-12 text-zinc-200 dark:text-zinc-800 mb-4"
                />
                <p>No readings recorded yet.</p>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead
                    class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800"
                  >
                    <tr>
                      <th
                        class="h-10 px-4 text-left align-middle font-medium text-zinc-500"
                      >
                        Date
                      </th>
                      <th
                        class="h-10 px-4 text-left align-middle font-medium text-zinc-500"
                      >
                        Reading
                      </th>
                      <th
                        class="h-10 px-4 text-left align-middle font-medium text-zinc-500"
                      >
                        Unit
                      </th>
                      <th
                        class="h-10 px-4 text-right align-middle font-medium text-zinc-500"
                      >
                        Increment
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <tr
                      v-for="(reading, index) in asset.readings"
                      :key="reading.id"
                      class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/10 transition-colors"
                    >
                      <td class="p-4 align-middle font-medium">
                        {{
                          new Date(reading.recorded_at).toLocaleDateString(
                            "en-US",
                            { year: "numeric", month: "short", day: "numeric" },
                          )
                        }}
                      </td>
                      <td class="p-4 align-middle font-mono">
                        {{ reading.reading_value.toLocaleString() }}
                      </td>
                      <td class="p-4 align-middle">
                        <span
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700"
                        >
                          {{ reading.reading_unit }}
                        </span>
                      </td>
                      <td
                        class="p-4 align-middle text-right text-emerald-600 font-medium"
                      >
                        <span
                          v-if="
                            reading.reading_unit ===
                              asset.readings[index + 1]?.reading_unit &&
                            index < asset.readings.length - 1
                          "
                        >
                          +{{
                            (
                              reading.reading_value -
                              asset.readings[index + 1].reading_value
                            ).toLocaleString()
                          }}
                        </span>
                        <span v-else class="text-zinc-400">—</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
