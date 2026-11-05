import { redirect } from "next/navigation";
import { auth } from "@/lib/auth";
import { homeFor, type Role } from "@/lib/roles";

/**
 * Exige una sesión con el rol indicado. Si el usuario tiene otro rol,
 * lo devuelve a su propio panel en lugar de mostrarle un 403 con datos.
 */
export async function requireRole(role: Role) {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== role) redirect(homeFor(session.user.role));
  return session;
}
