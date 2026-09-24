export default function Loading() {
  return (
    <div className="space-y-4 animate-pulse">
      <div className="h-8 w-48 bg-line rounded" />
      <div className="grid gap-3 sm:grid-cols-3">
        <div className="h-24 bg-line/70 rounded-xl" />
        <div className="h-24 bg-line/70 rounded-xl" />
        <div className="h-24 bg-line/70 rounded-xl" />
      </div>
      <div className="h-64 bg-line/50 rounded-xl" />
    </div>
  );
}
