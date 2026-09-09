import { useEffect, useState } from 'react'
import { AsyncState } from '../components/AsyncState'
import { api, firstError } from '../services/api'

export function AdminDashboardPage() {
  const [data, setData] = useState(null); const [error, setError] = useState('')
  useEffect(() => { api.get('/admin/dashboard').then((payload) => setData(payload.data)).catch((reason) => setError(firstError(reason))) }, [])
  return <AsyncState loading={!data && !error} error={error} empty={false}>{data && <section className="section page-section"><div className="page-title"><p className="eyebrow">Administration</p><h1>Dashboard</h1></div><div className="metric-grid"><article className="panel"><span>Revenu</span><strong>{data.revenue} MAD</strong></article><article className="panel"><span>Commandes</span><strong>{data.orders}</strong></article><article className="panel"><span>Clients</span><strong>{data.customers}</strong></article><article className="panel"><span>Produits</span><strong>{data.products}</strong></article><article className="panel"><span>Stock faible</span><strong>{data.low_stock}</strong></article></div><div className="panel"><h2>Commandes récentes</h2>{data.recent_orders.map((order) => <div className="order-line" key={order.id}><span>{order.order_number}</span><span className="status">{order.status}</span><strong>{order.grand_total} MAD</strong></div>)}</div></section>}</AsyncState>
}
