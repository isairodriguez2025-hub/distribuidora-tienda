import { redirect } from "next/navigation";
import { auth } from "@/lib/auth";
import { homeFor } from "@/lib/roles";

export default async function AppIndex() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  redirect(homeFor(session.user.role));
}

