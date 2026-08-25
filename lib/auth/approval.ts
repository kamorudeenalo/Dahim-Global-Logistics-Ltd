import "server-only";

import { prisma } from "@/lib/db";

export async function approveAccount(
  userId: string,
  approvedById: string
) {
  const user = await prisma.user.findUnique({
    where: {
      id: userId,
    },
    select: {
      id: true,
      status: true,
      emailVerifiedAt: true,
    },
  });

  if (!user || user.status !== "PENDING") {
    return null;
  }

  const approval = await prisma.accountApproval.findFirst({
    where: {
      userId,
      status: "PENDING",
    },
    orderBy: {
      createdAt: "desc",
    },
  });

  if (!approval) {
    return null;
  }

  const updatedApproval = await prisma.accountApproval.update({
    where: {
      id: approval.id,
    },
    data: {
      status: "APPROVED",
      approvedById,
      reviewedAt: new Date(),
    },
  });

  const updatedUser = await prisma.user.update({
    where: {
      id: userId,
    },
    data: {
      status: user.emailVerifiedAt ? "ACTIVE" : "PENDING",
    },
    select: {
      id: true,
      email: true,
      username: true,
      status: true,
      emailVerifiedAt: true,
    },
  });

  return {
    user: updatedUser,
    approval: updatedApproval,
  };
}

export async function rejectAccount(
  userId: string,
  approvedById: string,
  reason?: string
) {
  const user = await prisma.user.findUnique({
    where: {
      id: userId,
    },
    select: {
      id: true,
      status: true,
    },
  });

  if (!user || user.status !== "PENDING") {
    return null;
  }

  const approval = await prisma.accountApproval.findFirst({
    where: {
      userId,
      status: "PENDING",
    },
    orderBy: {
      createdAt: "desc",
    },
  });

  if (!approval) {
    return null;
  }

  const updatedApproval = await prisma.accountApproval.update({
    where: {
      id: approval.id,
    },
    data: {
      status: "REJECTED",
      approvedById,
      reason,
      reviewedAt: new Date(),
    },
  });

  const updatedUser = await prisma.user.update({
    where: {
      id: userId,
    },
    data: {
      status: "REJECTED",
    },
    select: {
      id: true,
      email: true,
      username: true,
      status: true,
      emailVerifiedAt: true,
    },
  });

  return {
    user: updatedUser,
    approval: updatedApproval,
  };
}
