import { resetPassword } from "@/lib/auth/password-reset";
import { requireSameOrigin } from "@/lib/security/csrf";

type PasswordResetCompleteBody = {
  token?: unknown;
  newPassword?: unknown;
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

  let body: PasswordResetCompleteBody;

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
    !body.token.trim() ||
    typeof body.newPassword !== "string" ||
    !body.newPassword
  ) {
    return Response.json(
      {
        error:
          "Reset token and new password are required.",
      },
      { status: 400 }
    );
  }

  const user = await resetPassword(
    body.token,
    body.newPassword
  );

  if (!user) {
    return Response.json(
      {
        error: "Invalid or expired reset token.",
      },
      { status: 400 }
    );
  }

  return Response.json({
    reset: true,
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
    },
  });
}
