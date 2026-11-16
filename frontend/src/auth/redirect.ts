/**
 * Where to go after logging in (the ?redirect= parameter). Only paths on this site are accepted: a
 * crafted link must not send the user to another site after they log in ("open redirect").
 */
export function safeRedirectPath(value: string | null): string {
  if (value && value.startsWith('/') && !value.startsWith('//') && !value.startsWith('/\\')) {
    return value
  }
  return '/'
}

/** The login URL that brings the user back to `path` afterwards. */
export function loginPathFor(path: string): string {
  return `/login?redirect=${encodeURIComponent(path)}`
}
