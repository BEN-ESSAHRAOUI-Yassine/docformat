function StylePreview({ elementKey, rules, defaultRules }) {
  const rule = rules || {}
  const defaultRule = defaultRules || {}

  const resolve = (key) => rule[key] ?? defaultRule[key] ?? null

  const fontFamily = resolve('font_family')
  const fontSize = resolve('font_size')
  const color = resolve('color')
  const bold = resolve('bold')
  const italic = resolve('italic')
  const underline = resolve('underline')
  const allCaps = resolve('all_caps')
  const smallCaps = resolve('small_caps')
  const alignment = resolve('alignment')
  const lineSpacing = resolve('line_spacing')
  const indentation = resolve('indentation')
  const spacingBefore = resolve('spacing_before')
  const spacingAfter = resolve('spacing_after')
  const numbering = resolve('numbering')
  const numberingFormat = resolve('numbering_format')

  const previewStyle = {
    fontFamily: fontFamily || 'inherit',
    fontSize: fontSize ? `${fontSize}pt` : 'inherit',
    color: color || 'inherit',
    fontWeight: bold ? 'bold' : 'normal',
    fontStyle: italic ? 'italic' : 'normal',
    textDecoration: underline ? 'underline' : 'none',
    textTransform: allCaps ? 'uppercase' : 'none',
    fontVariant: smallCaps ? 'small-caps' : 'normal',
    textAlign: alignment || 'left',
    lineHeight: lineSpacing || 'normal',
    paddingLeft: indentation ? `${indentation * 96}px` : undefined,
    marginTop: spacingBefore ? `${spacingBefore}pt` : undefined,
    marginBottom: spacingAfter ? `${spacingAfter}pt` : undefined,
  }

  const sampleText = getSampleText(elementKey, numbering, numberingFormat)

  return (
    <div className="bg-white border border-slate-200 rounded-lg p-5">
      <h4 className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Preview</h4>
      <div className="bg-slate-50 rounded-md p-4 min-h-[80px]">
        <p style={previewStyle}>{sampleText}</p>
      </div>
      <div className="mt-3 flex flex-wrap gap-1.5">
        {fontFamily && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{fontFamily}</span>}
        {fontSize && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{fontSize}pt</span>}
        {color && (
          <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded flex items-center gap-1">
            <span className="w-3 h-3 rounded-full border border-slate-300 inline-block" style={{ backgroundColor: color }} />
            {color}
          </span>
        )}
        {bold && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-bold">Bold</span>}
        {italic && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded italic">Italic</span>}
        {underline && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded underline">Underline</span>}
        {allCaps && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">ALL CAPS</span>}
        {smallCaps && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded" style={{ fontVariant: 'small-caps' }}>Small Caps</span>}
        {alignment && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{alignment}</span>}
        {lineSpacing && <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">Line: {lineSpacing}</span>}
        {numbering && <span className="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">Numbered ({numberingFormat || 'auto'})</span>}
      </div>
    </div>
  )
}

function getSampleText(elementKey, numbering, numberingFormat) {
  if (elementKey === 'body') return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
  if (elementKey === 'captions') return 'Figure 1 : Sample caption text'
  if (elementKey === 'sources') return 'Source: Author, Title, Publisher, 2024.'
  if (numbering) {
    const prefix = numberingFormat ? numberingFormat.split('/')[0].replace(/\.$/, '') : '1.'
    return `${prefix} Sample ${elementKey.replace('_', ' ')} text`
  }
  return `Sample ${elementKey.replace('_', ' ')} text`
}

export { StylePreview }
