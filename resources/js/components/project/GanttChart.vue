<script setup lang="ts">
import axios from "axios";
import { gantt } from "dhtmlx-gantt";
import { onMounted, onUnmounted, ref } from "vue";
import "dhtmlx-gantt/codebase/dhtmlxgantt.css";

const props = defineProps<{
  projectId: string;
  tasks?: any[];
  readOnly?: boolean;
}>();

const ganttContainer = ref<HTMLElement | null>(null);
let eventIds: string[] = [];

const formatDate = (value: Date) => {
  const year = value.getFullYear();
  const month = `${value.getMonth() + 1}`.padStart(2, "0");
  const day = `${value.getDate()}`.padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const calcEndDate = (start: Date, durationDays: number) => {
  const copy = new Date(start);
  copy.setHours(0, 0, 0, 0);
  const days = Math.max(1, Number.isFinite(durationDays) ? durationDays : 1);
  copy.setDate(copy.getDate() + days - 1);
  return copy;
};

const toDate = (value: unknown) => {
  const date = value instanceof Date ? value : new Date(value as any);
  return Number.isNaN(date.getTime()) ? new Date() : date;
};

const toDuration = (value: unknown) => {
  if (typeof value === "number" && Number.isFinite(value)) return value;
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 1;
};

const loadData = async () => {
  gantt.clearAll();
  const { data } = await axios.get(`/projects/${props.projectId}/gantt-data`);
  gantt.parse(data);
};

const init = () => {
  gantt.config.date_format = "%Y-%m-%d %H:%i";
  gantt.config.readonly = Boolean(props.readOnly);
  gantt.config.columns = [
    { name: "text", label: "Task", width: "*", tree: true },
    { name: "start_date", label: "Start", align: "center" },
    { name: "duration", label: "Days", align: "center" },
    { name: "add", label: "", width: 44 },
  ];
  gantt.config.scales = [
    { unit: "month", step: 1, format: "%F %Y" },
    { unit: "day", step: 1, format: "%j %D" },
  ];
  gantt.init(ganttContainer.value!);
};

const attachEvents = () => {
  if (props.readOnly) return;

  eventIds.push(
    gantt.attachEvent("onAfterLinkAdd", async (tempId, link) => {
      try {
        const res = await axios.post(
          `/projects/${props.projectId}/gantt-link`,
          {
            source: link.source,
            target: link.target,
            type: link.type,
          },
        );
        if (res.data?.tid) {
          gantt.changeLinkId(tempId, res.data.tid);
        }
      } catch {
        gantt.deleteLink(tempId);
      }
    }),
  );

  eventIds.push(
    gantt.attachEvent("onAfterLinkDelete", async (id) => {
      try {
        await axios.delete(`/projects/${props.projectId}/gantt-link/${id}`);
      } catch {
        await loadData();
      }
    }),
  );

  eventIds.push(
    gantt.attachEvent("onAfterTaskAdd", async (tempId, task) => {
      try {
        const start = toDate((task as any).start_date);
        const end = calcEndDate(start, toDuration((task as any).duration));
        const res = await axios.post(`/projects/${props.projectId}/tasks`, {
          text: task.text,
          start_date: formatDate(start),
          end_date: formatDate(end),
          parent_task_id: task.parent && task.parent !== 0 ? task.parent : null,
        });
        if (res.data?.id) {
          gantt.changeTaskId(tempId, res.data.id);
        }
      } catch {
        gantt.deleteTask(tempId);
      }
    }),
  );

  eventIds.push(
    gantt.attachEvent("onAfterTaskUpdate", async (id, task) => {
      try {
        const start = toDate((task as any).start_date);
        const end = (task as any).end_date
          ? toDate((task as any).end_date)
          : calcEndDate(start, toDuration((task as any).duration));
        await axios.put(`/projects/${props.projectId}/tasks/${id}`, {
          text: task.text,
          start_date: formatDate(start),
          end_date: formatDate(end),
          parent_task_id: task.parent && task.parent !== 0 ? task.parent : null,
          progress: task.progress,
        });
      } catch {
        await loadData();
      }
    }),
  );

  eventIds.push(
    gantt.attachEvent("onAfterTaskDelete", async (id) => {
      try {
        await axios.delete(`/projects/${props.projectId}/tasks/${id}`);
      } catch {
        await loadData();
      }
    }),
  );
};

onMounted(async () => {
  init();
  attachEvents();
  await loadData();
});

onUnmounted(() => {
  eventIds.forEach((id) => gantt.detachEvent(id));
  eventIds = [];
  gantt.clearAll();
});
</script>

<template>
  <div
    ref="ganttContainer"
    class="h-[600px] w-full overflow-hidden rounded-lg border"
  ></div>
</template>
