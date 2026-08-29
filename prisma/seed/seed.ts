import "dotenv/config";

import { PrismaPg } from "@prisma/adapter-pg";
import { PrismaClient } from "../../generated/prisma/client";

const adapter = new PrismaPg({
  connectionString: process.env.DATABASE_URL!,
});

const prisma = new PrismaClient({
  adapter,
});

async function main() {
  const permissions = [
    {
      name: "account.approve",
      description: "Approve pending user accounts.",
    },
    {
      name: "account.reject",
      description: "Reject pending user accounts.",
    },
  ];

  const permissionRecords = [];

  for (const permission of permissions) {
    const record = await prisma.permission.upsert({
      where: {
        name: permission.name,
      },
      update: {
        description: permission.description,
      },
      create: permission,
    });

    permissionRecords.push(record);
  }

  const adminRole = await prisma.role.upsert({
    where: {
      name: "ADMIN",
    },
    update: {
      description: "Platform administrator.",
    },
    create: {
      name: "ADMIN",
      description: "Platform administrator.",
    },
  });

  for (const permission of permissionRecords) {
    await prisma.rolePermission.upsert({
      where: {
        roleId_permissionId: {
          roleId: adminRole.id,
          permissionId: permission.id,
        },
      },
      update: {},
      create: {
        roleId: adminRole.id,
        permissionId: permission.id,
      },
    });
  }

  console.log("RBAC seed completed.");
  console.log(`Role: ${adminRole.name}`);
  console.log(
    `Permissions: ${permissionRecords
      .map((permission) => permission.name)
      .join(", ")}`
  );
}

main()
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
