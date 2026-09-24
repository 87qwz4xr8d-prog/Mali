import type { JobType, OperationalStatus, Role } from "./types";

export const APP_NAME = "Mali";
export const APP_NAME_TH = "มาลี";
export const COMPANY = "บจก.ยุธาภัคร์";

export const ROLES: Role[] = [
  "Requestor",
  "Technician",
  "Engineer Review",
  "Manager Approve",
  "Admin",
  "STORE",
];

export const ROLE_LABEL: Record<Role, string> = {
  Requestor: "ผู้แจ้งซ่อม",
  Technician: "ช่าง",
  "Engineer Review": "วิศวกรตรวจทาน",
  "Manager Approve": "หัวหน้าอนุมัติ",
  Admin: "ผู้ดูแลระบบ",
  STORE: "คลังอะไหล่",
};

export const JOB_TYPES: { code: JobType; label: string; hint: string }[] = [
  { code: "BD", label: "BD (Breakdown)", hint: "งานซ่อมฉุกเฉิน" },
  { code: "PM", label: "PM (Preventive Maintenance)", hint: "งาน PM" },
  { code: "CM", label: "CM (Corrective Maintenance)", hint: "งานซ่อมแก้ไข" },
  { code: "SV", label: "SV (Service)", hint: "งานบริการลูกค้า" },
  { code: "Drive", label: "Drive", hint: "งานขับรถกระเช้า" },
];

export const JOB_STATUSES = [
  "รอมอบหมายทีมซ่อม",
  "อยู่ระหว่างดำเนินการซ่อม",
  "รออะไหล่",
  "รออนุมัติ",
  "หัวหน้างานอนุมัติซ่อม",
  "ซ่อมเสร็จแล้ว",
  "ปิดจบงาน",
] as const;

export const PRIORITIES = ["ต่ำ", "ปกติ", "ด่วน", "ฉุกเฉิน"] as const;

export const LIFT_TYPES = ["Boom Lift", "Scissor Lift", "Mast Boom"] as const;

export const LIFT_TYPE_TH: Record<(typeof LIFT_TYPES)[number], string> = {
  "Boom Lift": "รถกระเช้าบูม",
  "Scissor Lift": "รถกระเช้ากรรไกร",
  "Mast Boom": "รถกระเช้าส่วนบุคคล",
};

export const OPERATIONAL_STATUSES: OperationalStatus[] = [
  "พร้อมใช้งาน",
  "รอการตรวจสอบ",
  "รถเบรคดาวน์ไม่พร้อมใช้",
  "รถอยู่หน้างาน",
];

export const BRANCHES = [
  "สาขา หนองใหญ่ ชลบุรี",
  "สาขา อมตะนคร ชลบุรี",
] as const;

export const PART_CATEGORIES = [
  { code: "E", label: "ไฟฟ้า (Electrical)" },
  { code: "M", label: "เครื่องกล (Mechanical)" },
  { code: "H", label: "ไฮดรอลิก (Hydraulic)" },
  { code: "O", label: "อื่น ๆ / สติ๊กเกอร์" },
] as const;

export const UNITS = ["PCS.", "SET.", "CM.", "L.", "EA."] as const;

export const SESSION_COOKIE = "mali_session";
export const SESSION_DAYS = 7;
