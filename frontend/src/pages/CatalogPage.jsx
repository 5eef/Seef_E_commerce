import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { ProductCard } from '../components/ProductCard'
import { api, firstError } from '../services/api'

export function CatalogPage() {
  const [params, setParams] = useSearchParams()
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const query = params.toString()

  useEffect(() => {
    Promise.all([api.get(`/products?${query}`), api.get('/categories')])
      .then(([productPayload, categoryPayload]) => {
        setProducts(productPayload.data)
        setCategories(categoryPayload.data)
      })
      .catch((reason) => setError(firstError(reason)))
      .finally(() => setLoading(false))
  }, [query])

  function update(name, value) {
    const next = new URLSearchParams(params)
    value ? next.set(name, value) : next.delete(name)
    setParams(next)
  }

  return (
    <section className="section page-section">
      <div className="page-title"><p className="eyebrow">Notre sélection</p><h1>La boutique</h1></div>
      <div className="filters">
        <label>Rechercher<input type="search" value={params.get('q') || ''} onChange={(event) => update('q', event.target.value)} /></label>
        <label>Catégorie<select value={params.get('category') || ''} onChange={(event) => update('category', event.target.value)}><option value="">Toutes</option>{categories.map((category) => <option key={category.id} value={category.slug}>{category.name}</option>)}</select></label>
        <label>Trier<select value={params.get('sort') || 'newest'} onChange={(event) => update('sort', event.target.value)}><option value="newest">Nouveautés</option><option value="price_asc">Prix croissant</option><option value="price_desc">Prix décroissant</option><option value="name_asc">Nom</option></select></label>
      </div>
      <AsyncState loading={loading} error={error} empty={!products.length}>
        <div className="product-grid">{products.map((product) => <ProductCard key={product.id} product={product} />)}</div>
      </AsyncState>
    </section>
  )
}
