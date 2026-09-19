import { render, waitFor } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { AuthContext } from '../app/auth-context'
import { CartContext } from '../app/cart-context'
import { CartAuthSync } from './CartAuthSync'

function SyncHarness({ user, refresh }) {
  return (
    <AuthContext.Provider value={{ user, loading: false }}>
      <CartContext.Provider value={{ refresh }}>
        <CartAuthSync />
      </CartContext.Provider>
    </AuthContext.Provider>
  )
}

describe('CartAuthSync', () => {
  it('refreshes the server cart when authentication changes', async () => {
    const refresh = vi.fn().mockResolvedValue(undefined)
    const { rerender } = render(<SyncHarness user={null} refresh={refresh} />)

    expect(refresh).not.toHaveBeenCalled()
    rerender(<SyncHarness user={{ id: 7 }} refresh={refresh} />)
    await waitFor(() => expect(refresh).toHaveBeenCalledTimes(1))

    rerender(<SyncHarness user={{ id: 7 }} refresh={refresh} />)
    expect(refresh).toHaveBeenCalledTimes(1)

    rerender(<SyncHarness user={null} refresh={refresh} />)
    await waitFor(() => expect(refresh).toHaveBeenCalledTimes(2))
  })
})
