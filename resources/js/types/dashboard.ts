export type PlatformDashboardPayload = {
  workspace: "platform";
  summary: {
    today_sales: number;
    today_transactions: number;
    pending_payments: number;
    low_stock_products: number;
    active_members: number;
    unpaid_dues_amount: number;
  };
  workQueue: {
    pending_members: number;
    pending_payments: number;
    unpaid_dues: number;
    low_stock_products: number;
  };
  collections: {
    period: string;
    total_due: number;
    paid: number;
    outstanding: number;
    collection_rate: number;
    pending_payment_amount: number;
    saving_balance: number;
    member_credit_balance: number;
  };
  pos: {
    today_sales: number;
    today_transactions: number;
    monthly_sales: number;
    monthly_transactions: number;
    annual_gross_profit: number;
    member_transactions: number;
    top_products: Array<{
      id: number;
      name: string;
      category?: string | null;
      quantity: number;
      revenue: number;
      gross_profit: number;
    }>;
  };
  inventory: {
    low_stock_count: number;
    critical_products: Array<{
      id: number;
      sku: string;
      name: string;
      category?: string | null;
      stock: number;
      minimum_stock: number;
    }>;
  };
  members: {
    active: number;
    pending: number;
    resigned: number;
    new_this_month: number;
  };
  shu: {
    year: number;
    annual_pos_profit: number;
    annual_pos_points: number;
    latest_closed_year?: number | null;
    latest_closed_total: number;
  };
  generatedAt: string;
};

export type AdminCooperativeDashboardPayload = {
  workspace: "admin-koperasi";
  organization?: { id?: string | number; name?: string; code?: string } | null;
  summary: {
    today_sales: number;
    today_transactions: number;
    pending_members: number;
    revision_members: number;
    pending_payments: number;
    low_stock_products: number;
    unpaid_dues_count: number;
    unpaid_dues_amount: number;
    active_members: number;
  };
  work_queue: {
    pending_payments: number;
    pending_members: number;
    revision_members: number;
    unpaid_dues: number;
    low_stock_products: number;
    pending_resignations?: number | null;
  };
  collections: {
    period: string;
    total_due: number;
    paid: number;
    outstanding: number;
    collection_rate: number;
    pending_payment_amount: number;
  };
  generated_at?: string;
  generatedAt?: string;
};

export type DashboardPayload =
  | PlatformDashboardPayload
  | AdminCooperativeDashboardPayload;

export function isAdminDashboardPayload(
  payload: DashboardPayload,
): payload is AdminCooperativeDashboardPayload {
  return payload.workspace === "admin-koperasi";
}
