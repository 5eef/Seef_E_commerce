import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { useCart } from '../hooks/useCart'
import { api, firstError, mediaUrl } from '../services/api'

export function ProductPage() {
  const { slug } = useParams()
  const { add } = useCart()
  const [product, setProduct] = useState(null)
  const [variantId, setVariantId] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    api.get(`/products/${slug}`)
      .then((payload) => {
        setProduct(payload.data)
        setVariantId(String(payload.data.variants?.find((item) => item.availability?.in_stock)?.id || ''))
      })
      .catch((reason) => setError(firstError(reason)))
  }, [slug])

  async function submit(event) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try { await add(Number(variantId), 1) } catch (reason) { setError(firstError(reason)) } finally { setBusy(false) }
  }

  return (
    <AsyncState loading={!product && !error} error={error && !product ? error : ''} empty={false}>
      {product && <section className="section product-detail">
        <div className="product-gallery">{product.images?.length ? product.images.map((image) => <img key={image.id} src={mediaUrl(image.path)} alt={image.alt_text || product.name} />) : <div className="image-placeholder">Seef</div>}</div>
        <div className="product-info"><p className="eyebrow">{product.categories?.[0]?.name || 'Collection'}</p><h1>{product.name}</h1><p className="price large">{product.price.sale || product.price.base} MAD</p><p className="lead">{product.description || product.short_description}</p>
          <form onSubmit={submit} className="stack"><label>Variante<select value={variantId} onChange={(event) => setVariantId(event.target.value)} required>{product.variants.map((variant) => <option key={variant.id} value={variant.id} disabled={!variant.availability?.in_stock}>{variant.name || variant.sku}{!variant.availability?.in_stock ? ' — Épuisé' : ''}</option>)}</select></label>{error && <p className="form-error">{error}</p>}<button className="button" disabled={busy || !variantId}>{busy ? 'Ajout…' : 'Ajouter au panier'}</button></form>
        </div>
      </section>}
    </AsyncState>
  )
}
