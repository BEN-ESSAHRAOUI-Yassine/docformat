import { RotateCcw } from 'lucide-react'
import { cn } from '../../lib/utils'

function PropertyField({ property, value, defaultValue, onChange, onReset }) {
  const isChanged = JSON.stringify(value) !== JSON.stringify(defaultValue)
  const hasDefault = defaultValue !== undefined && defaultValue !== null

  const handleReset = () => {
    onReset(property.key, defaultValue)
  }

  return (
    <div className="flex items-center gap-3 py-2">
      <label className="w-32 text-sm font-medium text-slate-700 shrink-0">{property.label}</label>
      <div className="flex-1 flex items-center gap-2">
        <FieldInput property={property} value={value} onChange={onChange} />
        {isChanged && hasDefault && (
          <button
            onClick={handleReset}
            title={`Reset to default: ${defaultValue}`}
            className="p-1 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded transition"
          >
            <RotateCcw size={14} />
          </button>
        )}
      </div>
      {isChanged && hasDefault && (
        <span className="text-xs text-slate-400 shrink-0" title={`Default: ${defaultValue}`}>
          default: {String(defaultValue)}
        </span>
      )}
    </div>
  )
}

function FieldInput({ property, value, onChange }) {
  const handleChange = (e) => {
    let newValue = e.target.value
    if (property.type === 'number') {
      newValue = newValue === '' ? '' : Number(newValue)
    }
    onChange(property.key, newValue)
  }

  switch (property.type) {
    case 'boolean':
      return (
        <button
          type="button"
          onClick={() => onChange(property.key, !value)}
          className={cn(
            'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
            value ? 'bg-blue-600' : 'bg-slate-200'
          )}
        >
          <span
            className={cn(
              'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform',
              value ? 'translate-x-5' : 'translate-x-0'
            )}
          />
        </button>
      )

    case 'select':
      return (
        <select
          value={value ?? ''}
          onChange={handleChange}
          className="flex-1 h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Not set</option>
          {property.options.map((opt) => (
            <option key={typeof opt === 'string' ? opt : opt.value} value={typeof opt === 'string' ? opt : opt.value}>
              {typeof opt === 'string' ? opt : opt.label}
            </option>
          ))}
        </select>
      )

    case 'color':
      return (
        <div className="flex items-center gap-2 flex-1">
          <input
            type="color"
            value={value || '#000000'}
            onChange={handleChange}
            className="h-9 w-9 rounded border border-slate-300 cursor-pointer"
          />
          <input
            type="text"
            value={value || ''}
            onChange={handleChange}
            placeholder="#000000"
            className="flex-1 h-9 rounded-md border border-slate-300 bg-white px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      )

    case 'number':
      return (
        <input
          type="number"
          value={value ?? ''}
          onChange={handleChange}
          min={property.min}
          max={property.max}
          step={property.step}
          placeholder="Not set"
          className="flex-1 h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      )

    case 'text':
    default:
      return (
        <input
          type="text"
          value={value ?? ''}
          onChange={handleChange}
          placeholder={property.placeholder || 'Not set'}
          className="flex-1 h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      )
  }
}

export { PropertyField }
