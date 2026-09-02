import api from './client'

export const listStyleProfiles = async () => {
  const { data } = await api.get('/style-profiles')
  return data
}

export const createStyleProfile = async (profile) => {
  const { data } = await api.post('/style-profiles', profile)
  return data
}

export const getStyleProfile = async (id) => {
  const { data } = await api.get(`/style-profiles/${id}`)
  return data
}

export const updateStyleProfile = async (id, profile) => {
  const { data } = await api.put(`/style-profiles/${id}`, profile)
  return data
}

export const deleteStyleProfile = async (id) => {
  await api.delete(`/style-profiles/${id}`)
}

export const exportStyleProfile = async (id) => {
  const { data } = await api.get(`/style-profiles/${id}/export`)
  return data
}

export const importStyleProfile = async (file) => {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await api.post('/style-profiles/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data
}
