import { login } from "@/lib/auth/login";
import { requireSameOrigin } from "@/lib/security/csrf";

type LoginBody = {
  identifier?: unknown;
  password?: unknown;
};

export async function POST(request: Request) {
  try {
    requireSameOrigin(request);
  } catch {
    return Response.json(
      {
        error: "Forbidden",
      },
      {
        status: 403,
      }
    );
  }

  let body: LoginBody;

  try {
    body = await request.json();
  } catch {
    return Response.json(
      {
        error: "Invalid JSON",
      },
      {
        status: 400,
      }
    );
  }

  if (
    typeof body.identifier !== "string" ||
    typeof body.password !== "string" ||
    !body.identifier.trim() ||
    !body.password
  ) {
    return Response.json(
      {
        error: "Identifier and password are required.",
      },
      {
        status: 400,
      }
    );
  }

  const user = await login(
    body.identifier,
    body.password,
    {
      ipAddress:
        request.headers.get("x-forwarded-for") ??
        request.headers.get("x-real-ip") ??
        undefined,
      userAgent:
        request.headers.get("user-agent") ??
        undefined,
    }
  );

  if (!user) {
    return Response.json(
      {
        error: "Invalid credentials.",
      },
      {
        status: 401,
      }
    );
  }

  return Response.json({
    authenticated: true,
    user: {
      id: user.id,
      email: user.email,
      username: user.username,
      status: user.status,
      emailVerifiedAt: user.emailVerifiedAt,
    },
  });
}
