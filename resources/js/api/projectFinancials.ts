import axios from "axios";
import {
  budgetAnalysis,
  summary,
  transactions,
} from "@/actions/App/Http/Controllers/ProjectFinanceController";

export const fetchProjectFinancials = async (projectId: string) => {
  const [summaryRes, budgetRes, transRes] = await Promise.all([
    axios.get(summary.url(projectId)),
    axios.get(budgetAnalysis.url(projectId)),
    axios.get(transactions.url(projectId, { query: { limit: 10 } })),
  ]);

  return {
    summary: summaryRes.data,
    budgetAnalysis: budgetRes.data,
    transactions: transRes.data.data,
  };
};
