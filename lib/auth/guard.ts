import "server-only";

import { getCurrentUser } from "@/lib/auth/current-user";
import { hasPermission } from "@/lib/auth/authorization";

export async function requireAuthenticatedUser() {
  const user = await getCurrentUser();

  if (!user) {
    throw new Error("UNAUTHORIZED");
  }

  return user;
}

export async function requirePermission(
  permissionName: string
) {
  const user = await requireAuthenticatedUser();

  const allowed = await hasPermission(
    user.id,
    permissionName
  );

  if (!allowed) {
    throw new Error("FORBIDDEN");
  }

  return user;
}
