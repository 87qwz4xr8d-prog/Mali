const DEV_FALLBACK_SECRET = "mali-dev-secret-change-me";
const PLACEHOLDER_SECRETS = new Set([
  "",
  "change-me-in-production",
  DEV_FALLBACK_SECRET,
]);

function isBuildPhase() {
  return process.env.NEXT_PHASE === "phase-production-build";
}

export function isProduction() {
  return process.env.NODE_ENV === "production";
}

export function sessionSecret() {
  const value = process.env.MALI_SESSION_SECRET || "";
  if (isProduction() && !isBuildPhase() && PLACEHOLDER_SECRETS.has(value)) {
    throw new Error(
      "ตั้ง MALI_SESSION_SECRET เป็นค่าสุ่มที่แข็งแรงในโปรดักชัน (อย่าใช้ค่าตัวอย่างใน .env.example)",
    );
  }
  return value || DEV_FALLBACK_SECRET;
}

export function requireProductionSecret() {
  if (isBuildPhase()) return;
  sessionSecret();
}
