import { useState, useEffect, useCallback } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { Save, RotateCcw, ArrowLeft, Loader2 } from 'lucide-react'
import { getStyleProfile, createStyleProfile, updateStyleProfile } from '../../api/styleProfiles'
import { ELEMENT_TYPES, PROPERTY_GROUPS } from '../../components/style-profiles/constants'
import { PropertyField } from '../../components/style-profiles/PropertyField'
import { StylePreview } from '../../components/style-profiles/StylePreview'

const DEFAULT_RULES = {
  body: { font_family: 'Times New Roman', font_size: 11, color: '#000000', bold: false, italic: false, underline: false, alignment: 'justify', line_spacing: 1.5 },
  heading_1: { font_family: 'Times New Roman', font_size: 18, color: '#000000', bold: true, all_caps: true, alignment: 'center', spacing_before: 24, spacing_after: 12 },
  heading_2: { font_family: 'Times New Roman', font_size: 16, color: '#000000', small_caps: true, alignment: 'left', indentation: 0.25, spacing_before: 18, spacing_after: 6 },
  heading_3: { font_family: 'Times New Roman', font_size: 14, color: '#000000', alignment: 'left', indentation: 0.5, numbering: true, numbering_format: '1./2./3.', spacing_before: 12, spacing_after: 6 },
  heading_4: { font_family: 'Times New Roman', font_size: 12, color: '#000000', alignment: 'left', indentation: 0.75, numbering: true, numbering_format: '1.1/1.2', spacing_before: 12, spacing_after: 6 },
  heading_5: { font_family: 'Times New Roman', font_size: 12, color: '#000000', alignment: 'left', indentation: 1.0, numbering: true, numbering_format: '1.1.1/1.1.2', spacing_before: 12, spacing_after: 6 },
  heading_6: { font_family: 'Times New Roman', font_size: 12, color: '#000000', alignment: 'left', indentation: 1.0, numbering: true, numbering_format: '1.1.1.1', spacing_before: 12, spacing_after: 6 },
  captions: { font_family: 'Times New Roman', font_size: 10, color: '#808080', alignment: 'center' },
  sources: { font_family: 'Times New Roman', font_size: 10, color: '#808080', italic: true, underline: true, alignment: 'right' },
}

export default function StyleProfileEditor() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isNew = id === 'new'

  const [loading, setLoading] = useState(!isNew)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [activeTab, setActiveTab] = useState('body')
  const [hasChanges, setHasChanges] = useState(false)

  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [type, setType] = useState('custom')
  const [language, setLanguage] = useState('fr-FR')
  const [rules, setRules] = useState(() => JSON.parse(JSON.stringify(DEFAULT_RULES)))
  const [originalRules, setOriginalRules] = useState(null)

  useEffect(() => {
    if (!isNew) loadProfile()
  }, [id])

  const loadProfile = async () => {
    setLoading(true)
    try {
      const data = await getStyleProfile(id)
      const profile = data.data || data
      setName(profile.name)
      setDescription(profile.description || '')
      setType(profile.type)
      setLanguage(profile.language || 'fr-FR')
      const loadedRules = profile.rules || {}
      setRules(loadedRules)
      setOriginalRules(JSON.parse(JSON.stringify(loadedRules)))
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load profile')
    } finally {
      setLoading(false)
    }
  }

  const updateRule = useCallback((elementKey, propertyKey, value) => {
    setRules((prev) => ({
      ...prev,
      [elementKey]: {
        ...(prev[elementKey] || {}),
        [propertyKey]: value,
      },
    }))
    setHasChanges(true)
  }, [])

  const resetProperty = useCallback((elementKey, propertyKey, defaultValue) => {
    setRules((prev) => ({
      ...prev,
      [elementKey]: {
        ...(prev[elementKey] || {}),
        [propertyKey]: defaultValue,
      },
    }))
    setHasChanges(true)
  }, [])

  const resetElement = useCallback((elementKey) => {
    setRules((prev) => ({
      ...prev,
      [elementKey]: { ...(DEFAULT_RULES[elementKey] || {}) },
    }))
    setHasChanges(true)
  }, [])

  const resetAll = useCallback(() => {
    if (!confirm('Reset all rules to defaults?')) return
    setRules(JSON.parse(JSON.stringify(DEFAULT_RULES)))
    setHasChanges(true)
  }, [])

  const handleSave = async () => {
    if (!name.trim()) {
      setError('Profile name is required')
      return
    }
    setSaving(true)
    setError('')
    try {
      const payload = { name, description, type, language, rules }
      if (isNew) {
        const data = await createStyleProfile(payload)
        navigate(`/style-profiles/${data.data.id}/edit`, { replace: true })
      } else {
        await updateStyleProfile(id, payload)
        setOriginalRules(JSON.parse(JSON.stringify(rules)))
        setHasChanges(false)
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save profile')
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="h-8 w-8 text-blue-600 animate-spin" />
      </div>
    )
  }

  const activeElement = ELEMENT_TYPES.find((e) => e.key === activeTab)
  const activeRule = rules[activeTab] || {}
  const defaultRule = DEFAULT_RULES[activeTab] || {}

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate('/style-profiles')} className="p-2 hover:bg-slate-100 rounded-md transition">
            <ArrowLeft size={20} className="text-slate-600" />
          </button>
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">
              {isNew ? 'Create Style Profile' : 'Edit Style Profile'}
            </h1>
            {!isNew && hasChanges && (
              <p className="text-sm text-amber-600 mt-0.5">Unsaved changes</p>
            )}
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={resetAll}
            className="inline-flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-md transition"
          >
            <RotateCcw size={16} />
            Reset All
          </button>
          <button
            onClick={handleSave}
            disabled={saving}
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
          >
            {saving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
            {isNew ? 'Create Profile' : 'Save Changes'}
          </button>
        </div>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">{error}</div>
      )}

      <div className="grid grid-cols-12 gap-6">
        {/* Left: Metadata + Element Tabs */}
        <div className="col-span-12 lg:col-span-8">
          {/* Profile metadata */}
          <div className="bg-white rounded-lg border border-slate-200 p-5 mb-6">
            <h3 className="text-sm font-semibold text-slate-900 mb-4">Profile Details</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <label className="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => { setName(e.target.value); setHasChanges(true) }}
                  placeholder="e.g. My University Style"
                  className="w-full h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div className="col-span-2">
                <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea
                  value={description}
                  onChange={(e) => { setDescription(e.target.value); setHasChanges(true) }}
                  rows={2}
                  placeholder="Optional description"
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Type</label>
                <select
                  value={type}
                  onChange={(e) => { setType(e.target.value); setHasChanges(true) }}
                  className="w-full h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="university">University</option>
                  <option value="thesis">Thesis</option>
                  <option value="report">Report</option>
                  <option value="article">Article</option>
                  <option value="custom">Custom</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Language</label>
                <select
                  value={language}
                  onChange={(e) => { setLanguage(e.target.value); setHasChanges(true) }}
                  className="w-full h-9 rounded-md border border-slate-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="fr-FR">Francais</option>
                  <option value="en-US">English (US)</option>
                  <option value="en-GB">English (UK)</option>
                </select>
              </div>
            </div>
          </div>

          {/* Element type tabs */}
          <div className="bg-white rounded-lg border border-slate-200">
            <div className="border-b border-slate-200 overflow-x-auto">
              <div className="flex min-w-max">
                {ELEMENT_TYPES.map((el) => (
                  <button
                    key={el.key}
                    onClick={() => setActiveTab(el.key)}
                    className={`px-4 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap ${
                      activeTab === el.key
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                    }`}
                  >
                    {el.label}
                  </button>
                ))}
              </div>
            </div>

            <div className="p-5">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h3 className="text-sm font-semibold text-slate-900">{activeElement?.label}</h3>
                  <p className="text-xs text-slate-500">{activeElement?.description}</p>
                </div>
                <button
                  onClick={() => resetElement(activeTab)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 rounded-md transition"
                >
                  <RotateCcw size={12} />
                  Reset to Default
                </button>
              </div>

              <div className="space-y-1">
                {Object.entries(PROPERTY_GROUPS).map(([groupKey, group]) => (
                  <div key={groupKey} className="mb-4">
                    <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{group.label}</h4>
                    <div className="pl-2 border-l-2 border-slate-100">
                      {group.properties.map((prop) => (
                        <PropertyField
                          key={prop.key}
                          property={prop}
                          value={activeRule[prop.key]}
                          defaultValue={DEFAULT_RULES[activeTab]?.[prop.key]}
                          onChange={(pk, val) => updateRule(activeTab, pk, val)}
                          onReset={(pk, def) => resetProperty(activeTab, pk, def)}
                        />
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Right: Live Preview */}
        <div className="col-span-12 lg:col-span-4">
          <div className="sticky top-6">
            <StylePreview
              elementKey={activeTab}
              rules={activeRule}
              defaultRules={DEFAULT_RULES[activeTab]}
            />

            {/* Quick summary of all elements */}
            <div className="mt-4 bg-white rounded-lg border border-slate-200 p-4">
              <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">All Elements</h4>
              <div className="space-y-1.5">
                {ELEMENT_TYPES.map((el) => {
                  const rule = rules[el.key] || {}
                  const def = DEFAULT_RULES[el.key] || {}
                  const font = rule.font_family || def.font_family || '-'
                  const size = rule.font_size || def.font_size || '-'
                  return (
                    <button
                      key={el.key}
                      onClick={() => setActiveTab(el.key)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-sm rounded transition ${
                        activeTab === el.key
                          ? 'bg-blue-50 text-blue-700'
                          : 'text-slate-600 hover:bg-slate-50'
                      }`}
                    >
                      <span className="font-medium">{el.label}</span>
                      <span className="text-xs text-slate-400">{font} {size}pt</span>
                    </button>
                  )
                })}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
