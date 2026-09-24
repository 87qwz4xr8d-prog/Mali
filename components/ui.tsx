import { cn } from "@/lib/format";
import type { ReactNode } from "react";

export function PageHeader({
  title,
  description,
  actions,
}: {
  title: string;
  description?: string;
  actions?: ReactNode;
}) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
        {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
      </div>
      {actions ? <div className="flex flex-wrap gap-2">{actions}</div> : null}
    </div>
  );
}

export function Button({
  children,
  href,
  variant = "primary",
  type = "button",
  disabled,
  className,
}: {
  children: ReactNode;
  href?: string;
  variant?: "primary" | "secondary" | "ghost" | "danger";
  type?: "button" | "submit";
  disabled?: boolean;
  className?: string;
}) {
  const styles = {
    primary: "bg-accent text-white hover:bg-[#b56a10]",
    secondary: "bg-white border border-line text-ink hover:bg-paper",
    ghost: "text-ink hover:bg-white/60",
    danger: "bg-danger text-white hover:bg-[#931c14]",
  }[variant];
  const cls = cn(
    "inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition disabled:opacity-50",
    styles,
    className,
  );
  if (href) {
    return (
      <a href={href} className={cls}>
        {children}
      </a>
    );
  }
  return (
    <button type={type} disabled={disabled} className={cls}>
      {children}
    </button>
  );
}

export function Card({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <div className={cn("rounded-xl border border-line bg-card shadow-[0_1px_0_rgba(28,36,48,0.04)]", className)}>
      {children}
    </div>
  );
}

export function Field({
  label,
  name,
  children,
  required,
  hint,
}: {
  label: string;
  name?: string;
  children?: ReactNode;
  required?: boolean;
  hint?: string;
}) {
  return (
    <label className="block">
      <span className="block text-sm font-medium mb-1">
        {label}
        {required ? <span className="text-danger"> *</span> : null}
      </span>
      {children}
      {hint ? <span className="mt-1 block text-xs text-muted">{hint}</span> : null}
      {name ? null : null}
    </label>
  );
}

export function inputClass(extra?: string) {
  return cn(
    "w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-muted/70",
    extra,
  );
}

export function Empty({
  title,
  description,
  action,
}: {
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <div className="rounded-xl border border-dashed border-line bg-white/60 px-6 py-14 text-center">
      <p className="font-medium">{title}</p>
      {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
      {action ? <div className="mt-4">{action}</div> : null}
    </div>
  );
}

export function Alert({
  tone = "error",
  children,
}: {
  tone?: "error" | "ok";
  children: ReactNode;
}) {
  return (
    <div
      role="alert"
      className={cn(
        "rounded-lg px-3 py-2 text-sm",
        tone === "error" ? "bg-red-50 text-danger border border-red-200" : "bg-emerald-50 text-ok border border-emerald-200",
      )}
    >
      {children}
    </div>
  );
}

export function Badge({
  children,
  tone = "neutral",
}: {
  children: ReactNode;
  tone?: "neutral" | "ok" | "warn" | "danger" | "info" | "accent";
}) {
  const map = {
    neutral: "bg-zinc-100 text-zinc-700",
    ok: "bg-emerald-50 text-ok",
    warn: "bg-amber-50 text-warn",
    danger: "bg-red-50 text-danger",
    info: "bg-sky-50 text-info",
    accent: "bg-orange-50 text-accent",
  }[tone];
  return (
    <span className={cn("inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium", map)}>
      {children}
    </span>
  );
}

export function statusTone(status: string): "ok" | "warn" | "danger" | "info" | "neutral" | "accent" {
  if (status.includes("ปิดจบ") || status.includes("เสร็จ") || status === "พร้อมใช้งาน") return "ok";
  if (status.includes("รออะไหล่") || status.includes("รอการตรวจสอบ") || status.includes("รอมอบหมาย")) return "warn";
  if (status.includes("เบรคดาวน์") || status.includes("ฉุกเฉิน")) return "danger";
  if (status.includes("อนุมัติ") || status.includes("ดำเนินการ")) return "info";
  if (status.includes("หน้างาน")) return "accent";
  return "neutral";
}

export function jobTypeTone(type: string) {
  if (type === "BD") return "danger" as const;
  if (type === "PM") return "info" as const;
  if (type === "CM") return "warn" as const;
  if (type === "SV") return "accent" as const;
  return "neutral" as const;
}
