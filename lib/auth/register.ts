import "server-only";

import { prisma } from "@/lib/db";
import { hashPassword } from "@/lib/auth/password";
import { createEmailVerificationToken } from "@/lib/auth/email-verification";
import { writeAuditLog } from "@/lib/auth/audit";

export async function registerUser(
  email: string,
  username: string,
  password: string
) {
  const normalizedEmail = email.trim().toLowerCase();
  const normalizedUsername = username.trim().toLowerCase();

  if (!normalizedEmail || !normalizedUsername || !password) {
    return null;
  }

  const existingUser = await prisma.user.findFirst({
    where: {
      OR: [
        { email: normalizedEmail },
        { username: normalizedUsername },
      ],
    },
    select: {
      id: true,
    },
  });

  if (existingUser) {
    return null;
  }

  const passwordHash = await hashPassword(password);

  const user = await prisma.user.create({
    data: {
      email: normalizedEmail,
      username: normalizedUsername,
      passwordHash,
      status: "PENDING",
    },
    select: {
      id: true,
      email: true,
      username: true,
      status: true,
      emailVerifiedAt: true,
      createdAt: true,
    },
  });

  const verification =
    await createEmailVerificationToken(user.id);

  if (!verification) {
    await prisma.user.delete({
      where: {
        id: user.id,
      },
    });

    return null;
  }

  await prisma.accountApproval.create({
    data: {
      userId: user.id,
      status: "PENDING",
    },
  });

  await writeAuditLog("ACCOUNT_CREATED", {
    userId: user.id,
    metadata: {
      username: user.username,
    },
  });

  return {
    user,
    verificationToken: verification.token,
  };
}
