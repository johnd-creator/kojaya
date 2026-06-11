<script setup lang="ts">
import {
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  LinearScale,
  Tooltip
  
} from "chart.js";
import type {ChartConfiguration} from "chart.js";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { formatCurrency } from "@/lib/formatters";

Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
);

type Product = {
  id: number;
  name: string;
  revenue: number;
  quantity?: number;
};

const props = defineProps<{
  products: Product[];
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart<"bar"> | null = null;

const sorted = (): Product[] =>
  [...props.products]
    .sort((a, b) => b.revenue - a.revenue)
    .slice(0, 5);

const buildConfig = (): ChartConfiguration<"bar"> => {
  const items = sorted();
  return {
    type: "bar",
    data: {
      labels: items.map((p) =>
        p.name.length > 18 ? `${p.name.slice(0, 18)}…` : p.name,
      ),
      datasets: [
        {
          data: items.map((p) => p.revenue),
          backgroundColor: (ctx) => {
            const chart = ctx.chart;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) {
              return "rgb(16 185 129)";
            }
            const g = c.createLinearGradient(
              chartArea.left,
              0,
              chartArea.right,
              0,
            );
            g.addColorStop(0, "rgb(5 150 105)");
            g.addColorStop(1, "rgb(16 185 129)");
            return g;
          },
          hoverBackgroundColor: "rgb(4 120 87)",
          borderRadius: 6,
          barThickness: 14,
          borderSkipped: false,
        },
      ],
    },
    options: {
      indexAxis: "y",
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 8 } },
      animation: {
        duration: 700,
        easing: "easeOutCubic",
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: "rgb(228 228 231 / 0.5)",
            drawTicks: false,
          },
          border: { display: false },
          ticks: {
            color: "rgb(113 113 122)",
            font: { size: 11 },
            padding: 6,
            callback: (value) => {
              const num = Number(value);
              if (num >= 1_000_000) {
                return `Rp ${(num / 1_000_000).toFixed(1)}jt`;
              }
              if (num >= 1_000) {
                return `Rp ${(num / 1_000).toFixed(0)}rb`;
              }
              return `Rp ${num}`;
            },
          },
        },
        y: {
          grid: { display: false },
          border: { display: false },
          ticks: {
            color: "rgb(82 82 91)",
            font: { size: 12, weight: 500 },
            padding: 8,
          },
        },
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
            label: (ctx) => ` Omzet: ${formatCurrency(Number(ctx.parsed.x))}`,
          },
        },
      },
    },
  };
};

onMounted(() => {
  if (canvas.value) {
    chart = new Chart(canvas.value, buildConfig());
  }
});

onBeforeUnmount(() => {
  chart?.destroy();
  chart = null;
});
</script>

<template>
  <div class="h-60 w-full">
    <canvas ref="canvas" aria-hidden="true" />
  </div>
</template>
