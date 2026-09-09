import { Link } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { useCart } from '../hooks/useCart'
import { firstError } from '../services/api'
import { useState } from 'react'

export function CartPage() {
  const { cart, loading, update, remove } = useCart()
  const [error, setError] = useState('')
  async function change(item, quantity) {
    try { quantity < 1 ? await remove(item.id) : await update(item.id, quantity) } catch (reason) { setError(firstError(reason)) }
  }

  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Votre sélection</p><h1>Panier</h1></div><AsyncState loading={loading} error={error} empty={!cart?.items?.length}><div className="cart-layout"><div className="stack">{cart?.items?.map((item) => <article className="line-item" key={item.id}><div><h3>{item.product?.name}</h3><p>{item.variant?.name || item.variant?.sku}</p></div><label>Quantité<input type="number" min="1" max={item.available_quantity} value={item.quantity} onChange={(event) => change(item, Number(event.target.value))} /></label><strong>{item.line_total} MAD</strong><button className="link-button danger" type="button" onClick={() => remove(item.id)}>Retirer</button></article>)}</div><aside className="panel summary"><h2>Résumé</h2><p><span>Sous-total</span><strong>{cart?.subtotal} MAD</strong></p><p className="muted">Livraison et taxes calculées au checkout.</p><Link className="button" to="/checkout">Commander</Link></aside></div></AsyncState></section>
}
