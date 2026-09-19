import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError, api, csrf, firstError, mediaUrl } from './api'

function jsonResponse(payload, init = {}) {
  return new Response(JSON.stringify(payload), {
    status: 200,
    headers: { 'content-type': 'application/json' },
    ...init,
  })
}

describe('API client', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    document.cookie = 'XSRF-TOKEN=secure%20token; path=/'
  })

  it('sends JSON, credentials, and the decoded CSRF token', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      jsonResponse({ data: { id: 7 } }),
    )

    await expect(api.post('/cart/items', { quantity: 2 })).resolves.toEqual({ data: { id: 7 } })

    const [url, options] = fetchMock.mock.calls[0]
    expect(url).toMatch(/\/api\/cart\/items$/)
    expect(options).toMatchObject({
      method: 'POST',
      credentials: 'include',
      body: JSON.stringify({ quantity: 2 }),
    })
    expect(options.headers.get('Accept')).toBe('application/json')
    expect(options.headers.get('Content-Type')).toBe('application/json')
    expect(options.headers.get('X-XSRF-TOKEN')).toBe('secure token')
  })

  it('preserves validation details in a typed API error', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(jsonResponse(
      { message: 'Invalid data.', errors: { email: ['Email is invalid.'] } },
      { status: 422 },
    ))

    const request = api.post('/checkout', { email: 'invalid' })
    await expect(request).rejects.toBeInstanceOf(ApiError)
    await expect(request).rejects.toMatchObject({
      message: 'Invalid data.',
      status: 422,
      errors: { email: ['Email is invalid.'] },
    })
  })

  it('returns null for a successful empty response', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(null, { status: 204 }))
    await expect(api.delete('/cart/items/1')).resolves.toBeNull()
  })

  it('normalizes network failures without leaking implementation errors', async () => {
    vi.spyOn(globalThis, 'fetch').mockRejectedValue(new TypeError('socket closed'))
    await expect(api.get('/products')).rejects.toMatchObject({
      message: 'Unable to reach the store. Check your connection.',
      status: 0,
    })
  })

  it('initializes Sanctum through the same-origin endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(null, { status: 204 }))
    await csrf()
    expect(fetchMock).toHaveBeenCalledWith(expect.stringMatching(/\/sanctum\/csrf-cookie$/), {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })
  })
})

describe('API helpers', () => {
  it('selects the first validation error and safe fallbacks', () => {
    expect(firstError({ errors: { email: ['First'], name: ['Second'] } })).toBe('First')
    expect(firstError(new Error('Readable'))).toBe('Readable')
    expect(firstError(null)).toBe('Something went wrong.')
  })

  it('keeps external media URLs and maps storage paths', () => {
    expect(mediaUrl('https://images.example/product.jpg')).toBe('https://images.example/product.jpg')
    expect(mediaUrl('/products/image.jpg')).toMatch(/\/storage\/products\/image\.jpg$/)
    expect(mediaUrl(null)).toBeNull()
  })
})
