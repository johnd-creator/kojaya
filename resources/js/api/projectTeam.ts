import axios from "axios";
import { availability } from "@/actions/App/Http/Controllers/ProjectTeamController";

export interface ProjectTeamAvailabilityParams {
  [key: string]: string | number | undefined;
  employee_id: string | number;
  start_date: string;
  end_date?: string;
}

export const fetchProjectTeamAvailability = async (
  projectId: string,
  params: ProjectTeamAvailabilityParams,
) => {
  const { data } = await axios.get(
    availability.url(projectId, { query: params }),
  );

  return data;
};
