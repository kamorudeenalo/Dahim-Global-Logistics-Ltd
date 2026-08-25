import "server-only";

import { prisma } from "@/lib/db";
import { verifyPassword } from "@/lib/auth/password";

type SafeUser = {
  id: string;
  email: string;
  username: string;
  status: "PENDING" | "ACTIVE" | "SUSPENDED" | "DISABLED" | "REJECTED";
  emailVerifiedAt: Date | null;
  lastLoginAt: Date | null;
  createdAt: Date;
  updatedAt: Date;
};

function normalizeIdentifier(identifier: string): string {
  return identifier.trim().toLowerCase();
}

export async function authenticateUser(
  identifier: string,
  password: string
): Promise<SafeUser | null> {
  const normalizedIdentifier = normalizeIdentifier(identifier);

  if (!normalizedIdentifier || !password) {
    return null;
  }

  const user = await prisma.user.findFirst({
    where: {
      OR: [
        { email: normalizedIdentifier },
        { username: normalizedIdentifier },
      ],
    },
    select: {
      id: true,
      email: true,
      username: true,
      passwordHash: true,
      status: true,
      emailVerifiedAt: true,
      lastLoginAt: true,
      createdAt: true,
      updatedAt: true,
    },
  });

  if (!user) {
    return null;
  }

  const passwordValid = await verifyPassword(
    password,
    user.passwordHash
  );

  if (!passwordValid) {
    return null;
  }

  if (user.status !== "ACTIVE") {
    return null;
  }

  if (!user.emailVerifiedAt) {
    return null;
  }

  const { passwordHash: _passwordHash, ...safeUser } = user;

  return safeUser;
}
