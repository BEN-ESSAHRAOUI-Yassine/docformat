const ELEMENT_TYPES = [
  { key: 'body', label: 'Body', description: 'Default paragraph text' },
  { key: 'heading_1', label: 'Heading 1', description: 'Top-level heading' },
  { key: 'heading_2', label: 'Heading 2', description: 'Second-level heading' },
  { key: 'heading_3', label: 'Heading 3', description: 'Third-level heading' },
  { key: 'heading_4', label: 'Heading 4', description: 'Fourth-level heading' },
  { key: 'heading_5', label: 'Heading 5', description: 'Fifth-level heading' },
  { key: 'heading_6', label: 'Heading 6', description: 'Sixth-level heading' },
  { key: 'captions', label: 'Captions', description: 'Figure/table captions' },
  { key: 'sources', label: 'Sources', description: 'Source references' },
]

const FONT_FAMILIES = [
  'Times New Roman', 'Arial', 'Calibri', 'Cambria', 'Georgia',
  'Garamond', 'Helvetica', 'Courier New', 'Verdana', 'Tahoma',
]

const ALIGNMENTS = [
  { value: 'left', label: 'Left' },
  { value: 'center', label: 'Center' },
  { value: 'right', label: 'Right' },
  { value: 'justify', label: 'Justify' },
]

const LINE_SPACINGS = [
  { value: 1.0, label: 'Single' },
  { value: 1.15, label: '1.15' },
  { value: 1.5, label: '1.5' },
  { value: 2.0, label: 'Double' },
  { value: 2.5, label: '2.5' },
  { value: 3.0, label: 'Triple' },
]

const PROPERTY_GROUPS = {
  font: {
    label: 'Font',
    properties: [
      { key: 'font_family', label: 'Font Family', type: 'select', options: FONT_FAMILIES },
      { key: 'font_size', label: 'Font Size (pt)', type: 'number', min: 6, max: 72, step: 1 },
      { key: 'color', label: 'Color', type: 'color' },
    ],
  },
  formatting: {
    label: 'Formatting',
    properties: [
      { key: 'bold', label: 'Bold', type: 'boolean' },
      { key: 'italic', label: 'Italic', type: 'boolean' },
      { key: 'underline', label: 'Underline', type: 'boolean' },
    ],
  },
  capitalization: {
    label: 'Capitalization',
    properties: [
      { key: 'all_caps', label: 'All Caps', type: 'boolean' },
      { key: 'small_caps', label: 'Small Caps', type: 'boolean' },
    ],
  },
  paragraph: {
    label: 'Paragraph',
    properties: [
      { key: 'alignment', label: 'Alignment', type: 'select', options: ALIGNMENTS },
      { key: 'line_spacing', label: 'Line Spacing', type: 'select', options: LINE_SPACINGS },
      { key: 'indentation', label: 'Indentation (in)', type: 'number', min: 0, max: 3, step: 0.25 },
    ],
  },
  spacing: {
    label: 'Spacing',
    properties: [
      { key: 'spacing_before', label: 'Before (pt)', type: 'number', min: 0, max: 72, step: 1 },
      { key: 'spacing_after', label: 'After (pt)', type: 'number', min: 0, max: 72, step: 1 },
    ],
  },
  numbering: {
    label: 'Numbering',
    properties: [
      { key: 'numbering', label: 'Enable Numbering', type: 'boolean' },
      { key: 'numbering_format', label: 'Format', type: 'text', placeholder: 'e.g. 1./2./3.' },
    ],
  },
}

export { ELEMENT_TYPES, FONT_FAMILIES, ALIGNMENTS, LINE_SPACINGS, PROPERTY_GROUPS }
