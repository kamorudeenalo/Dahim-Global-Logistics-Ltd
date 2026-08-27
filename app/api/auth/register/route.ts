import { registerUser } from "@/lib/auth/register";
import { requireSameOrigin } from "@/lib/security/csrf";

type RegisterBody = {
  email?: unknown;
  username?: unknown;
  password?: unknown;
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

  let body: RegisterBody;

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
    typeof body.username !== "string" ||
    typeof body.password !== "string"
  ) {
    return Response.json(
      {
        error:
          "Email, username and password are required.",
      },
      { status: 400 }
    );
  }

  try {
    const result = await registerUser(
      body.email,
      body.username,
      body.password
    );

    if (!result) {
      return Response.json(
        {
          error:
            "Unable to create this account.",
        },
        { status: 400 }
      );
    }

    return Response.json(
      {
        registered: true,
        user: {
          id: result.user.id,
          email: result.user.email,
          username: result.user.username,
          status: result.user.status,
          emailVerifiedAt:
            result.user.emailVerifiedAt,
          createdAt: result.user.createdAt,
        },
      },
      { status: 201 }
    );
  } catch {
    return Response.json(
      {
        error:
          "Unable to create this account.",
      },
      { status: 400 }
    );
  }
}
