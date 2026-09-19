import { act, renderHook, waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useCart } from '../hooks/useCart'
import { api } from '../services/api'
import { CartProvider } from './CartContext'

vi.mock('../services/api', () => ({
  api: { get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

function wrapper({ children }) {
  return <CartProvider>{children}</CartProvider>
}

describe('CartProvider', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    api.get.mockResolvedValue({ data: { id: 1, items: [], items_count: 0 } })
  })

  it('loads and refreshes the server-owned cart', async () => {
    const refreshed = { id: 1, items: [{ id: 8 }], items_count: 1 }
    const { result } = renderHook(() => useCart(), { wrapper })
    await waitFor(() => expect(result.current.loading).toBe(false))
    api.get.mockResolvedValueOnce({ data: refreshed })

    await act(() => result.current.refresh())

    expect(api.get).toHaveBeenCalledWith('/cart')
    expect(result.current.cart).toEqual(refreshed)
  })

  it('uses API responses as the source of truth for cart mutations', async () => {
    const added = { id: 1, items_count: 1 }
    const updated = { id: 1, items_count: 2 }
    const emptied = { id: 1, items_count: 0 }
    api.post.mockResolvedValue({ data: added })
    api.patch.mockResolvedValue({ data: updated })
    api.delete.mockResolvedValue({ data: emptied })
    const { result } = renderHook(() => useCart(), { wrapper })
    await waitFor(() => expect(result.current.loading).toBe(false))

    await act(() => result.current.add(12, 1))
    expect(api.post).toHaveBeenCalledWith('/cart/items', { product_variant_id: 12, quantity: 1 })
    expect(result.current.cart).toEqual(added)

    await act(() => result.current.update(4, 2))
    expect(api.patch).toHaveBeenCalledWith('/cart/items/4', { quantity: 2 })
    expect(result.current.cart).toEqual(updated)

    await act(() => result.current.remove(4))
    expect(api.delete).toHaveBeenCalledWith('/cart/items/4')
    expect(result.current.cart).toEqual(emptied)
  })
})
