import { requireRole } from "@/lib/guard";

export default async function Layout({ children }: { children: React.ReactNode }) {
  await requireRole("COMERCIO");
  return <>{children}</>;
}
