import { useEffect, useState } from 'react'
import { ProductCard } from '../components/ProductCard'
import { AsyncState } from '../components/AsyncState'
import { api, firstError } from '../services/api'

export function WishlistPage() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  useEffect(() => { api.get('/wishlist').then((payload) => setItems(payload.data.items)).catch((reason) => setError(firstError(reason))).finally(() => setLoading(false)) }, [])
  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Vos favoris</p><h1>Wishlist</h1></div><AsyncState loading={loading} error={error} empty={!items.length}><div className="product-grid">{items.map((item) => <div key={item.id}><ProductCard product={item.product} /><button className="link-button danger" type="button" onClick={async () => { await api.delete(`/wishlist/items/${item.product.id}`); setItems((current) => current.filter((value) => value.id !== item.id)) }}>Retirer</button></div>)}</div></AsyncState></section>
}
