import { verifyEmail } from "@/lib/auth/email-verification";
import { requireSameOrigin } from "@/lib/security/csrf";

type VerifyEmailBody = {
  token?: unknown;
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

  let body: VerifyEmailBody;

  try {
    body = await request.json();
  } catch {
    return Response.json(
      { error: "Invalid JSON" },
      { status: 400 }
    );
  }

  if (
    typeof body.token !== "string" ||
    !body.token.trim()
  ) {
    return Response.json(
      { error: "Verification token is required." },
      { status: 400 }
    );
  }

  const user = await verifyEmail(body.token);

  if (!user) {
    return Response.json(
      { error: "Invalid or expired verification token." },
      { status: 400 }
    );
  }

  return Response.json({
    verified: true,
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
    },
  });
}
