import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AsyncState } from '../components/AsyncState'
import { ProductCard } from '../components/ProductCard'
import logo from '../assets/seef_logo_ecomerce.png'
import { api, firstError } from '../services/api'

export function HomePage() {
  const [products, setProducts] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api.get('/products?featured=1&per_page=4')
      .then((payload) => setProducts(payload.data))
      .catch((reason) => setError(firstError(reason)))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    const elements = document.querySelectorAll('[data-reveal]')
    if (!('IntersectionObserver' in window)) {
      elements.forEach((element) => element.classList.add('is-visible'))
      return undefined
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible')
          observer.unobserve(entry.target)
        }
      })
    }, { threshold: 0.12 })

    elements.forEach((element) => observer.observe(element))
    return () => observer.disconnect()
  }, [products])

  return (
    <>
      <section className="hero-grid home-hero">
        <div className="hero-copy">
          <img className="hero-logo" src={logo} alt="Seef E-commerce" />
          <p className="eyebrow hero-eyebrow">Boutique en ligne · Collection Automne 2026</p>
          <h1 className="hero-title">Intemporel,<br /><em>par nature.</em></h1>
          <p className="lead hero-lead">Découvrez une sélection e-commerce de pièces conçues pour durer — matières naturelles, coupes épurées et savoir-faire artisanal.</p>
          <div className="button-row hero-actions"><Link className="button brand-cta" to="/shop">Découvrir la boutique</Link><a className="button secondary" href="#values">Nos valeurs</a></div>
        </div>
        <div className="hero-media hero-visual"><img src="https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=900&h=1100&fit=crop&auto=format" alt="Sélection de vêtements dans la boutique Seef" /><div className="hero-seal"><img src={logo} alt="" /><span>Style · Qualité · Confiance</span></div></div>
      </section>

      <section className="section reveal-section" id="catalogue" data-reveal>
        <div className="section-heading"><div><p className="eyebrow">Notre sélection</p><h2>Le catalogue</h2></div><Link to="/shop">Voir tout</Link></div>
        <AsyncState loading={loading} error={error} empty={!products.length}>
          <div className="product-grid">{products.map((product) => <ProductCard key={product.id} product={product} />)}</div>
        </AsyncState>
      </section>

      <section className="capsule reveal-section" id="values" data-reveal>
        <div><p className="eyebrow">Édition limitée</p><h2>La capsule<br /><em>Hiver 2026</em></h2><p>Douze pièces exclusives en collaboration avec des artisans locaux.</p></div>
        <div className="stats"><div><b>12</b><span>Pièces</span></div><div><b>4</b><span>Artisans</span></div><div><b>100%</b><span>Bio</span></div></div>
      </section>
    </>
  )
}
