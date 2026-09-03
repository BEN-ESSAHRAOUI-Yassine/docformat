import api from './client'

export const createPageBreak = async (documentId, payload) => {
  const { data } = await api.post(`/documents/${documentId}/page-breaks`, payload)
  return data
}

export const deletePageBreak = async (documentId, elementId) => {
  const { data } = await api.delete(`/documents/${documentId}/page-breaks/${elementId}`)
  return data
}
