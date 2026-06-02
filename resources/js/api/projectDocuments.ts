import axios from "axios";
import { store } from "@/actions/App/Http/Controllers/ProjectDocumentController";

export const uploadProjectDocument = async (
  projectId: string,
  formData: FormData,
) => {
  await axios.post(store.url(projectId), formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });
};
