import { prisma } from "@/lib/db";
import { createPasswordResetToken } from "@/lib/auth/password-reset";
import { requireSameOrigin } from "@/lib/security/csrf";

type PasswordResetRequestBody = {
  email?: unknown;
};

export async function POST(request: Request) {
  try {
    requireSameOrigin(request);
  } catch {
    return Response.json(
      { error: "Forbidden" },
      { status: 403 }
    );
  }

  let body: PasswordResetRequestBody;

  try {
    body = await request.json();
  } catch {
    return Response.json(
      { error: "Invalid JSON" },
      { status: 400 }
    );
  }

  if (
    typeof body.email !== "string" ||
    !body.email.trim()
  ) {
    return Response.json(
      { error: "Email is required." },
      { status: 400 }
    );
  }

  const email = body.email.trim().toLowerCase();

  const user = await prisma.user.findUnique({
    where: { email },
    select: { id: true },
  });

  if (user) {
    await createPasswordResetToken(user.id);
  }

  return Response.json({
    accepted: true,
  });
}
