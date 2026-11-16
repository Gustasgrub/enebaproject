import { useId, type InputHTMLAttributes } from 'react'

type TextFieldProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string
  /** Messages from the API's validation errors for this field. */
  errors?: string[]
}

export function TextField({ label, errors = [], ...inputProps }: TextFieldProps) {
  const id = useId()
  const errorId = `${id}-error`

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-sm font-semibold">
        {label}
      </label>
      <input
        id={id}
        aria-invalid={errors.length > 0}
        aria-describedby={errors.length > 0 ? errorId : undefined}
        className="bg-field px-4 py-3 placeholder:text-white/50 focus:outline-2 focus:outline-accent aria-invalid:outline-2 aria-invalid:outline-red-400"
        {...inputProps}
      />
      {errors.length > 0 && (
        <p id={errorId} className="text-sm font-semibold text-red-300">
          {errors.join(' ')}
        </p>
      )}
    </div>
  )
}
