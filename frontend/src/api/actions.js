import api from './client'

export const getActions = async (documentId, params = {}) => {
  const { data } = await api.get(`/documents/${documentId}/actions`, { params })
  return data
}

export const getHistory = async (documentId) => {
  const { data } = await api.get(`/documents/${documentId}/history`)
  return data
}

export const undo = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/undo`)
  return data
}

export const redo = async (documentId) => {
  const { data } = await api.post(`/documents/${documentId}/redo`)
  return data
}
