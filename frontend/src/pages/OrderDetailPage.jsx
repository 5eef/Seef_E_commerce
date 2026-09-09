import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { api, firstError } from '../services/api'

export function OrderDetailPage() {
  const { id } = useParams(); const [order, setOrder] = useState(null); const [error, setError] = useState('')
  useEffect(() => { api.get(`/orders/${id}`).then((payload) => setOrder(payload.data)).catch((reason) => setError(firstError(reason))) }, [id])
  return <AsyncState loading={!order && !error} error={error} empty={false}>{order && <section className="section page-section"><div className="page-title"><p className="eyebrow">Commande</p><h1>{order.order_number}</h1><span className="status">{order.status}</span></div><div className="cart-layout"><div className="panel stack"><h2>Articles</h2>{order.items.map((item) => <div className="order-line" key={item.id}><span>{item.product_name} × {item.quantity}</span><strong>{item.total} MAD</strong></div>)}</div><aside className="panel summary"><h2>Total</h2><p><span>Sous-total</span><strong>{order.subtotal} MAD</strong></p><p><span>Remise</span><strong>−{order.discount_total} MAD</strong></p><p><span>Total</span><strong>{order.grand_total} MAD</strong></p></aside></div></section>}</AsyncState>
}
