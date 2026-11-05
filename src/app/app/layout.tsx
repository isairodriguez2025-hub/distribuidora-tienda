import { redirect } from "next/navigation";
import { auth } from "@/lib/auth";
import { AppShell } from "@/components/app-shell";
import { isRole } from "@/lib/roles";

export default async function AppLayout({ children }: { children: React.ReactNode }) {
  const session = await auth();
  if (!session?.user) redirect("/login");

  const role = session.user.role;
  if (!isRole(role)) redirect("/login");

  return (
    <AppShell role={role} name={session.user.name ?? ""} businessName={session.user.businessName ?? ""}>
      {children}
    </AppShell>
  );
}
