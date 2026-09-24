export type Role =
  | "Requestor"
  | "Technician"
  | "Engineer Review"
  | "Manager Approve"
  | "Admin"
  | "STORE";

export type JobType = "BD" | "PM" | "CM" | "SV" | "Drive";

export type InternalStatus =
  | "New"
  | "Wait"
  | "Assign"
  | "Review"
  | "Approve"
  | "Renew"
  | "Closed";

export type JobResult = "InProcess" | "Finished";

export type OperationalStatus =
  | "พร้อมใช้งาน"
  | "รอการตรวจสอบ"
  | "รถเบรคดาวน์ไม่พร้อมใช้"
  | "รถอยู่หน้างาน";

export type User = {
  id: number;
  username: string;
  password_hash: string;
  name_th: string;
  name_en: string;
  email: string | null;
  role: Role;
  team_id: number | null;
  site_id: number | null;
  employee_code: string | null;
  position: string | null;
  department: string | null;
  phone: string | null;
  started_at: string | null;
  active: number;
  created_at: string;
};

export type PublicUser = Omit<User, "password_hash">;

export type Site = {
  id: number;
  company_name: string;
  customer_name: string | null;
  site_name: string | null;
  notes: string | null;
};

export type Team = {
  id: number;
  name: string;
  repair_group: string | null;
  scope: string | null;
  email: string | null;
  notes: string | null;
};

export type Asset = {
  id: number;
  site_id: number;
  type: string;
  brand: string | null;
  code: string;
  model: string | null;
  serial: string | null;
  manufacturer: string | null;
  received_at: string | null;
  warranty_until: string | null;
  asset_number: string | null;
  description: string | null;
  price: number | null;
  contact_name: string | null;
  contact_phone: string | null;
  notes: string | null;
  hour_meter: number;
  operational_status: OperationalStatus;
  created_at: string;
};

export type AssetRow = Asset & {
  company_name: string;
  customer_name: string | null;
  site_name: string | null;
};

export type Part = {
  id: number;
  code: string;
  category: string | null;
  oem_code: string | null;
  name: string;
  qty: number;
  unit_cost: number;
  unit: string;
  used_on: string | null;
  location: string | null;
  lead_time: string | null;
  min_qty: number;
  reorder_qty: number;
  max_qty: number;
  notes: string | null;
};

export type Vendor = {
  id: number;
  code: string;
  company_name: string;
  description: string | null;
  contact: string | null;
  phone: string | null;
  address: string | null;
  email: string | null;
  notes: string | null;
};

export type JobRequest = {
  id: number;
  number: string;
  asset_id: number;
  site_id: number;
  job_type: JobType;
  status: string;
  internal_status: InternalStatus;
  result: JobResult;
  priority: string;
  symptom: string | null;
  requester_id: number;
  requester_phone: string | null;
  hour_meter: number | null;
  location_note: string | null;
  signature_name: string | null;
  signed_at: string | null;
  work_order_id: number | null;
  created_at: string;
  updated_at: string;
};

export type JobRequestRow = JobRequest & {
  asset_code: string;
  asset_model: string | null;
  asset_type: string;
  site_name: string | null;
  company_name: string;
  requester_name: string;
  work_order_number: string | null;
};

export type WorkOrder = {
  id: number;
  number: string;
  job_request_id: number;
  asset_id: number;
  job_type: JobType;
  status: string;
  internal_status: InternalStatus;
  result: JobResult;
  team_id: number | null;
  technician_id: number | null;
  special_info: string | null;
  countermeasure: string | null;
  started_at: string | null;
  completed_at: string | null;
  signature_name: string | null;
  signed_at: string | null;
  created_at: string;
  updated_at: string;
};

export type WorkOrderRow = WorkOrder & {
  asset_code: string;
  asset_model: string | null;
  asset_type: string;
  site_name: string | null;
  company_name: string;
  job_request_number: string;
  team_name: string | null;
  technician_name: string | null;
  symptom: string | null;
};

export type WorkOrderPart = {
  id: number;
  work_order_id: number;
  part_id: number;
  qty: number;
  cost_per_unit: number;
  total_price: number;
  part_code: string;
  part_name: string;
  unit: string;
};

export type PmSchedule = {
  id: number;
  asset_id: number;
  title: string;
  frequency_days: number;
  last_done_at: string | null;
  next_due_at: string;
  checklist_template_id: number | null;
  active: number;
  notes: string | null;
};

export type PmScheduleRow = PmSchedule & {
  asset_code: string;
  asset_model: string | null;
  asset_type: string;
  site_name: string | null;
};

export type ChecklistTemplate = {
  id: number;
  name: string;
  lift_type: string | null;
  kind: "request" | "mc" | "pm";
};

export type ChecklistItem = {
  id: number;
  template_id: number;
  code: string;
  label: string;
  sort_order: number;
};

export type Inspection = {
  id: number;
  template_id: number;
  job_request_id: number | null;
  work_order_id: number | null;
  pm_schedule_id: number | null;
  inspector_id: number | null;
  completed_at: string | null;
  created_at: string;
};

export type ChecklistResult = {
  id: number;
  inspection_id: number;
  item_id: number;
  result: "pass" | "fail" | "na" | "";
  notes: string | null;
  code: string;
  label: string;
  sort_order: number;
};

export type AssetStatusLog = {
  id: number;
  asset_id: number;
  status: OperationalStatus;
  branch: string | null;
  details: string | null;
  start_at: string | null;
  end_at: string | null;
  reported_by: number | null;
  created_at: string;
  reporter_name?: string | null;
};

export type RentalJob = {
  id: number;
  asset_id: number;
  job_no: string | null;
  customer_name: string | null;
  job_site: string | null;
  start_date: string | null;
  end_date: string | null;
  created_by: number | null;
  created_at: string;
};

export type ActionState = { error?: string; success?: string; id?: number };
