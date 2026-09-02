import * as React from 'react'
import { cn } from '../../lib/utils'

const badgeVariants = {
  default: 'bg-blue-100 text-blue-800',
  secondary: 'bg-slate-100 text-slate-800',
  destructive: 'bg-red-100 text-red-800',
  outline: 'border border-slate-300 text-slate-700',
  success: 'bg-green-100 text-green-800',
  warning: 'bg-amber-100 text-amber-800',
}

function Badge({ className, variant = 'default', ...props }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
        badgeVariants[variant],
        className
      )}
      {...props}
    />
  )
}

export { Badge }
