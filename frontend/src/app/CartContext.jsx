import { useCallback, useEffect, useMemo, useState } from 'react'
import { api } from '../services/api'
import { CartContext } from './cart-context'

export function CartProvider({ children }) {
  const [cart, setCart] = useState(null)
  const [loading, setLoading] = useState(true)

  const refresh = useCallback(async () => {
    setLoading(true)
    try {
      const payload = await api.get('/cart')
      setCart(payload.data)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    let active = true
    api.get('/cart')
      .then((payload) => active && setCart(payload.data))
      .catch(console.error)
      .finally(() => active && setLoading(false))

    return () => { active = false }
  }, [])

  const value = useMemo(() => ({
    cart,
    loading,
    refresh,
    async add(productVariantId, quantity = 1) {
      const payload = await api.post('/cart/items', { product_variant_id: productVariantId, quantity })
      setCart(payload.data)
    },
    async update(itemId, quantity) {
      const payload = await api.patch(`/cart/items/${itemId}`, { quantity })
      setCart(payload.data)
    },
    async remove(itemId) {
      const payload = await api.delete(`/cart/items/${itemId}`)
      setCart(payload.data)
    },
    clearLocal() { setCart(null) },
  }), [cart, loading, refresh])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
