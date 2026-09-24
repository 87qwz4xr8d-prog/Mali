"use client";

export default function ErrorPage({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <div className="rounded-xl border border-red-200 bg-red-50 p-6">
      <h1 className="font-semibold text-danger">เกิดข้อผิดพลาด</h1>
      <p className="mt-2 text-sm text-ink">{error.message || "ไม่สามารถโหลดหน้านี้ได้"}</p>
      <button
        type="button"
        onClick={reset}
        className="mt-4 rounded-lg bg-accent text-white px-3 py-2 text-sm"
      >
        ลองอีกครั้ง
      </button>
    </div>
  );
}
