const API_BASE = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '')
const BACKEND_BASE = API_BASE.replace(/\/api$/, '')

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

async function parseResponse(response) {
  if (response.status === 204) return null

  const contentType = response.headers.get('content-type') || ''
  const payload = contentType.includes('application/json')
    ? await response.json()
    : { message: await response.text() }

  if (!response.ok) {
    const fallback = response.status === 429
      ? 'Too many requests. Please wait and try again.'
      : 'The request could not be completed.'
    throw new ApiError(payload.message || fallback, response.status, payload.errors || {})
  }

  return payload
}

async function request(path, options = {}) {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }

  try {
    const response = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers,
      credentials: 'include',
      body: options.body && !(options.body instanceof FormData)
        ? JSON.stringify(options.body)
        : options.body,
    })

    return await parseResponse(response)
  } catch (error) {
    if (error instanceof ApiError) throw error
    throw new ApiError('Unable to reach the store. Check your connection.', 0)
  }
}

export async function csrf() {
  const response = await fetch(`${BACKEND_BASE}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
  if (!response.ok) throw new ApiError('Unable to initialize the secure session.', response.status)
}

export const api = {
  get: (path) => request(path),
  post: (path, body = {}) => request(path, { method: 'POST', body }),
  patch: (path, body = {}) => request(path, { method: 'PATCH', body }),
  delete: (path) => request(path, { method: 'DELETE' }),
}

export function firstError(error) {
  const messages = Object.values(error?.errors || {}).flat()
  return messages[0] || error?.message || 'Something went wrong.'
}

export function mediaUrl(path) {
  if (!path) return null
  if (/^https?:\/\//.test(path)) return path
  return `${BACKEND_BASE}/storage/${path.replace(/^\//, '')}`
}
