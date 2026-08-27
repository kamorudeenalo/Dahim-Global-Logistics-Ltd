import "server-only";

import { prisma } from "@/lib/db";

type JsonPrimitive = string | number | boolean;

type JsonValue =
  | JsonPrimitive
  | JsonValue[]
  | { [key: string]: JsonValue };

type AuditContext = {
  userId?: string;
  ipAddress?: string;
  userAgent?: string;
  metadata?: JsonValue;
};

export async function writeAuditLog(
  action:
    | "LOGIN_SUCCESS"
    | "LOGIN_FAILED"
    | "LOGOUT"
    | "PASSWORD_CHANGED"
    | "PASSWORD_RESET_REQUESTED"
    | "PASSWORD_RESET_COMPLETED"
    | "EMAIL_VERIFICATION_REQUESTED"
    | "EMAIL_VERIFIED"
    | "ACCOUNT_CREATED"
    | "ACCOUNT_APPROVED"
    | "ACCOUNT_REJECTED"
    | "ACCOUNT_SUSPENDED"
    | "ACCOUNT_DISABLED"
    | "ROLE_ASSIGNED"
    | "ROLE_REMOVED"
    | "PERMISSION_CHANGED",
  context: AuditContext = {}
) {
  return prisma.auditLog.create({
    data: {
      action,
      ...(context.userId !== undefined && {
        userId: context.userId,
      }),
      ...(context.ipAddress !== undefined && {
        ipAddress: context.ipAddress,
      }),
      ...(context.userAgent !== undefined && {
        userAgent: context.userAgent,
      }),
      ...(context.metadata !== undefined && {
        metadata: context.metadata,
      }),
    },
  });
}
