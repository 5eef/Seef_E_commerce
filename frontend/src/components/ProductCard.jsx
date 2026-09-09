import { Link } from 'react-router-dom'
import { firstError, mediaUrl } from '../services/api'
import { useCart } from '../hooks/useCart'

export function ProductCard({ product }) {
  const { add } = useCart()
  const image = product.images?.[0]
  const variant = product.variants?.find((item) => item.availability?.in_stock)
  const price = product.price?.sale || product.price?.base

  async function addToCart(event) {
    event.preventDefault()
    if (!variant) return
    try {
      await add(variant.id)
    } catch (error) {
      window.alert(firstError(error))
    }
  }

  return (
    <article className="product-card">
      <Link to={`/products/${product.slug}`}>
        <div className="product-image">
          {image ? <img src={mediaUrl(image.path)} alt={image.alt_text || product.name} /> : <div className="image-placeholder">Seef</div>}
          {product.price?.sale && <span className="badge">Soldes</span>}
          <button className="quick-add" type="button" disabled={!variant} onClick={addToCart}>{variant ? 'Ajouter au panier' : 'Épuisé'}</button>
        </div>
        <h3>{product.name}</h3>
        <p className="price">{price} MAD {product.price?.sale && <del>{product.price.base} MAD</del>}</p>
      </Link>
    </article>
  )
}
