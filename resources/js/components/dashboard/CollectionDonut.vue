<script setup lang="ts">
import {
  ArcElement,
  Chart,
  DoughnutController,
  Tooltip
  
} from "chart.js";
import type {ChartConfiguration} from "chart.js";
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { formatCurrency } from "@/lib/formatters";

Chart.register(DoughnutController, ArcElement, Tooltip);

const props = withDefaults(
  defineProps<{
    paid: number;
    outstanding: number;
    rate: number;
  }>(),
  {
    paid: 0,
    outstanding: 0,
    rate: 0,
  },
);

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart<"doughnut"> | null = null;

const buildConfig = (): ChartConfiguration<"doughnut"> => ({
  type: "doughnut",
  data: {
    labels: ["Sudah dibayar", "Outstanding"],
    datasets: [
      {
        data: [props.paid, props.outstanding],
        backgroundColor: ["rgb(16 185 129)", "rgb(244 63 94)"],
        hoverBackgroundColor: ["rgb(5 150 105)", "rgb(225 29 72)"],
        borderWidth: 0,
        borderRadius: 6,
        spacing: 4,
        hoverOffset: 6,
      },
    ],
  },
  options: {
    cutout: "74%",
    responsive: true,
    maintainAspectRatio: false,
    animation: {
      duration: 700,
      easing: "easeOutCubic",
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: "rgb(24 24 27 / 0.95)",
        titleColor: "rgb(255 255 255)",
        bodyColor: "rgb(228 228 231)",
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
        callbacks: {
          label: (ctx) =>
            ` ${ctx.label}: ${formatCurrency(Number(ctx.parsed))}`,
        },
      },
    },
  },
});

const render = () => {
  if (!canvas.value) {
    return;
  }
  if (chart) {
    chart.data.datasets[0].data = [props.paid, props.outstanding];
    chart.update("none");
    return;
  }
  chart = new Chart(canvas.value, buildConfig());
};

onMounted(() => {
  render();
});

watch(
  () => [props.paid, props.outstanding],
  () => render(),
);

onBeforeUnmount(() => {
  chart?.destroy();
  chart = null;
});
</script>

<template>
  <div class="relative h-44 w-full">
    <canvas ref="canvas" aria-hidden="true" />
    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
      <span class="text-2xl font-bold tabular-nums text-zinc-950 dark:text-white">
        {{ rate.toFixed(1) }}%
      </span>
      <span class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
        Tertagih
      </span>
    </div>
    <table class="sr-only">
      <caption>Komposisi koleksi iuran</caption>
      <thead>
        <tr>
          <th>Kategori</th>
          <th>Nilai</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Sudah dibayar</td>
          <td>{{ formatCurrency(paid) }}</td>
        </tr>
        <tr>
          <td>Outstanding</td>
          <td>{{ formatCurrency(outstanding) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
