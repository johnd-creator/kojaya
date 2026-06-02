import axios from "axios";
import {
  destroyLink,
  getData,
  storeLink,
} from "@/actions/App/Http/Controllers/ProjectGanttController";
import {
  destroy as destroyTaskRoute,
  store as storeTaskRoute,
  update as updateTaskRoute,
} from "@/actions/App/Http/Controllers/ProjectTaskController";

export interface GanttLinkPayload {
  source: string | number;
  target: string | number;
  type?: string | number | null;
}

export interface GanttTaskPayload {
  text: string;
  start_date: string;
  end_date: string;
  parent_task_id?: string | number | null;
  progress?: number;
}

export const fetchProjectGantt = async (projectId: string) => {
  const { data } = await axios.get(getData.url(projectId));

  return data;
};

export const createProjectGanttLink = async (
  projectId: string,
  payload: GanttLinkPayload,
) => {
  const { data } = await axios.post(storeLink.url(projectId), payload);

  return data;
};

export const deleteProjectGanttLink = async (
  projectId: string,
  linkId: string | number,
) => {
  await axios.delete(
    destroyLink.url({ project: projectId, link: String(linkId) }),
  );
};

export const createProjectTask = async (
  projectId: string,
  payload: GanttTaskPayload,
) => {
  const { data } = await axios.post(storeTaskRoute.url(projectId), payload);

  return data;
};

export const updateProjectTask = async (
  projectId: string,
  taskId: string | number,
  payload: GanttTaskPayload,
) => {
  await axios.put(
    updateTaskRoute.url({ project: projectId, task: String(taskId) }),
    payload,
  );
};

export const deleteProjectTask = async (
  projectId: string,
  taskId: string | number,
) => {
  await axios.delete(
    destroyTaskRoute.url({ project: projectId, task: String(taskId) }),
  );
};
