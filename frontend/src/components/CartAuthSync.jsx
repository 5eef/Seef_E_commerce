import { useEffect, useRef } from 'react'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'

export function CartAuthSync() {
  const { user, loading } = useAuth()
  const { refresh } = useCart()
  const previousUserId = useRef(undefined)

  useEffect(() => {
    if (loading) return

    const userId = user?.id ?? null

    if (previousUserId.current === undefined) {
      previousUserId.current = userId
      return
    }

    if (previousUserId.current !== userId) {
      previousUserId.current = userId
      void refresh()
    }
  }, [loading, refresh, user?.id])

  return null
}
