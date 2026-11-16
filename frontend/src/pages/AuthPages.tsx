import { useMutation } from '@tanstack/react-query'
import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router'
import { ApiError, apiPost } from '../api/client'
import { safeRedirectPath } from '../auth/redirect'
import { startSession, useSession, type Session } from '../auth/session'
import { TextField } from '../components/form/TextField'

type AuthMode = 'login' | 'register'

// Login and registration share everything but their fields: after either one, the new session is
// stored and the user goes back to where they were sent from (?redirect=), e.g. checkout.
export function AuthPage({ mode }: { mode: AuthMode }) {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const session = useSession()
  const redirectTo = safeRedirectPath(searchParams.get('redirect'))
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })

  const submit = useMutation({
    mutationFn: () =>
      apiPost<{ data: Session }>(
        mode === 'login' ? '/api/login' : '/api/register',
        mode === 'login' ? { email: form.email, password: form.password } : form,
      ),
    onSuccess: (response) => {
      startSession(response.data)
      void navigate(redirectTo, { replace: true })
    },
  })

  if (session !== null && !submit.isSuccess) {
    return <Navigate to={redirectTo} replace />
  }

  const fieldErrors = submit.error instanceof ApiError ? submit.error.fieldErrors : {}
  const generalError = submit.isError && Object.keys(fieldErrors).length === 0 ? 'Something went wrong. Please try again.' : null
  const field = (name: keyof typeof form) => ({
    value: form[name],
    errors: fieldErrors[name],
    onChange: (event: { target: { value: string } }) => setForm((current) => ({ ...current, [name]: event.target.value })),
  })
  const otherMode = mode === 'login' ? 'register' : 'login'
  const redirectQuery = searchParams.has('redirect') ? `?redirect=${encodeURIComponent(redirectTo)}` : ''

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    submit.mutate()
  }

  return (
    <div className="mx-auto max-w-md px-4 py-10">
      <title>{`${mode === 'login' ? 'Log in' : 'Register'} · anabe`}</title>
      <h1 className="mb-6 text-3xl font-extrabold">{mode === 'login' ? 'Log in' : 'Create an account'}</h1>

      <form onSubmit={handleSubmit} className="flex flex-col gap-4 bg-panel p-6" noValidate>
        {mode === 'register' && <TextField label="Name" autoComplete="name" required {...field('name')} />}
        <TextField label="Email" type="email" autoComplete="email" required {...field('email')} />
        <TextField
          label="Password"
          type="password"
          autoComplete={mode === 'login' ? 'current-password' : 'new-password'}
          required
          {...field('password')}
        />
        {mode === 'register' && (
          <TextField label="Repeat password" type="password" autoComplete="new-password" required {...field('password_confirmation')} />
        )}

        {generalError && <p className="font-semibold text-red-300">{generalError}</p>}

        <button type="submit" disabled={submit.isPending} className="bg-accent py-3 font-extrabold text-card disabled:opacity-60">
          {submit.isPending ? 'Please wait…' : mode === 'login' ? 'Log in' : 'Create account'}
        </button>
      </form>

      <p className="mt-4 text-sm">
        {mode === 'login' ? 'New here? ' : 'Already have an account? '}
        <Link to={`/${otherMode}${redirectQuery}`} className="font-bold text-accent hover:underline">
          {otherMode === 'register' ? 'Create an account' : 'Log in'}
        </Link>
      </p>
      {mode === 'login' && <DemoAccountHint />}
    </div>
  )
}

function DemoAccountHint(): ReactNode {
  return (
    <p className="mt-6 bg-card p-4 text-sm text-white/80">
      Reviewing the demo? Log in as <strong>demo@example.com</strong> with the password <strong>anabe-demo</strong>, or create
      your own account. To try the admin pages: <strong>admin@example.com</strong> / <strong>anabe-admin</strong>.
    </p>
  )
}
