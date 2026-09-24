import type { Role } from "./types";

export function canCreateJobRequest(role: Role) {
  return ["Requestor", "Engineer Review", "Manager Approve", "Admin"].includes(role);
}

export function canEditJobRequest(role: Role, closed: boolean) {
  if (closed) return role === "Admin" || role === "Engineer Review";
  return ["Requestor", "Engineer Review", "Manager Approve", "Admin"].includes(role);
}

export function canConvertToWorkOrder(role: Role) {
  return ["Engineer Review", "Manager Approve", "Admin"].includes(role);
}

export function canAssignWorkOrder(role: Role) {
  return ["Engineer Review", "Manager Approve", "Admin"].includes(role);
}

export function canEnterRepair(role: Role) {
  return ["Technician", "Engineer Review", "Admin"].includes(role);
}

export function canSetWaitingParts(role: Role) {
  return ["Technician", "Engineer Review", "STORE", "Admin"].includes(role);
}

export function canApproveClose(role: Role) {
  return role === "Manager Approve" || role === "Admin";
}

export function canEditClosedJobs(role: Role) {
  return role === "Admin" || role === "Engineer Review";
}

export function canManageMaster(role: Role) {
  return role === "Admin" || role === "Engineer Review";
}

export function canManageUsers(role: Role) {
  return role === "Admin" || role === "Engineer Review";
}

export function canManageParts(role: Role) {
  return role === "STORE" || role === "Admin" || role === "Engineer Review";
}

export function canIssueParts(role: Role) {
  return ["STORE", "Technician", "Engineer Review", "Admin"].includes(role);
}

export function canManageAssets(role: Role) {
  return ["Admin", "Engineer Review", "Manager Approve"].includes(role);
}

export function canViewReports(role: Role) {
  return role !== "Requestor";
}

export function navForRole(role: Role) {
  const all = [
    { href: "/", label: "แดชบอร์ด", icon: "layout" },
    { href: "/job-requests", label: "ใบแจ้งซ่อม", icon: "clipboard" },
    { href: "/work-orders", label: "ใบงานซ่อม", icon: "wrench" },
    { href: "/pm", label: "แผน PM", icon: "calendar" },
    { href: "/assets", label: "เครื่องจักร", icon: "lift" },
    { href: "/parts", label: "อะไหล่", icon: "box" },
    { href: "/master", label: "ข้อมูลหลัก", icon: "database" },
    { href: "/reports", label: "รายงาน", icon: "chart" },
    { href: "/ops", label: "สถานะรถ", icon: "activity" },
  ];
  if (role === "Requestor") {
    return all.filter((i) => ["/", "/job-requests", "/assets"].includes(i.href));
  }
  if (role === "Technician") {
    return all.filter((i) =>
      ["/", "/job-requests", "/work-orders", "/pm", "/assets", "/parts", "/ops"].includes(
        i.href,
      ),
    );
  }
  if (role === "STORE") {
    return all.filter((i) => ["/", "/parts", "/work-orders", "/reports"].includes(i.href));
  }
  if (role === "Manager Approve") {
    return all.filter((i) => i.href !== "/master");
  }
  return all;
}
