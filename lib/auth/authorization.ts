import "server-only";

import { prisma } from "@/lib/db";

export async function hasPermission(
  userId: string,
  permissionName: string
): Promise<boolean> {
  if (!userId || !permissionName) {
    return false;
  }

  const assignment = await prisma.userRole.findFirst({
    where: {
      userId,
      role: {
        permissions: {
          some: {
            permission: {
              name: permissionName,
            },
          },
        },
      },
    },
    select: {
      userId: true,
    },
  });

  return assignment !== null;
}

export async function requirePermission(
  userId: string,
  permissionName: string
): Promise<void> {
  const allowed = await hasPermission(userId, permissionName);

  if (!allowed) {
    throw new Error("FORBIDDEN");
  }
}
