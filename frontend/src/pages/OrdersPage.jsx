import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { api, firstError } from '../services/api'

export function OrdersPage() {
  const [orders, setOrders] = useState([])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  useEffect(() => { api.get('/orders').then((payload) => setOrders(payload.data)).catch((reason) => setError(firstError(reason))).finally(() => setLoading(false)) }, [])
  return <section className="section page-section"><div className="page-title"><p className="eyebrow">Espace client</p><h1>Mes commandes</h1></div><AsyncState loading={loading} error={error} empty={!orders.length}><div className="stack">{orders.map((order) => <Link className="panel order-row" key={order.id} to={`/account/orders/${order.id}`}><div><strong>{order.order_number}</strong><p>{new Date(order.created_at).toLocaleDateString('fr-FR')}</p></div><span className="status">{order.status}</span><strong>{order.grand_total} {order.currency}</strong></Link>)}</div></AsyncState></section>
}
