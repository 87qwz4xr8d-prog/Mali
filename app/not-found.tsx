import { Button } from "@/components/ui";

export default function NotFound() {
  return (
    <div className="min-h-[50vh] flex flex-col items-center justify-center text-center px-4">
      <p className="text-sm text-muted">404</p>
      <h1 className="text-2xl font-semibold mt-1">ไม่พบหน้าที่ต้องการ</h1>
      <p className="text-sm text-muted mt-2">อาจถูกลบ หรือเลขที่ใบงานไม่ถูกต้อง</p>
      <div className="mt-4">
        <Button href="/">กลับแดชบอร์ด</Button>
      </div>
    </div>
  );
}
