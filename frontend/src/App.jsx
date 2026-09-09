import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './app/AuthContext'
import { CartProvider } from './app/CartContext'
import { Layout } from './components/Layout'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AccountPage } from './pages/AccountPage'
import { AddressesPage } from './pages/AddressesPage'
import { AdminDashboardPage } from './pages/AdminDashboardPage'
import { AuthPage } from './pages/AuthPage'
import { CartPage } from './pages/CartPage'
import { CatalogPage } from './pages/CatalogPage'
import { CheckoutPage } from './pages/CheckoutPage'
import { CheckoutSuccessPage } from './pages/CheckoutSuccessPage'
import { HomePage } from './pages/HomePage'
import { NotFoundPage } from './pages/NotFoundPage'
import { OrderDetailPage } from './pages/OrderDetailPage'
import { OrdersPage } from './pages/OrdersPage'
import { PasswordPage } from './pages/PasswordPage'
import { ProductPage } from './pages/ProductPage'
import { WishlistPage } from './pages/WishlistPage'

function App() {
  return <BrowserRouter><AuthProvider><CartProvider><Routes><Route element={<Layout />}><Route index element={<HomePage />} /><Route path="shop" element={<CatalogPage />} /><Route path="products/:slug" element={<ProductPage />} /><Route path="cart" element={<CartPage />} /><Route path="checkout" element={<CheckoutPage />} /><Route path="checkout/success" element={<CheckoutSuccessPage />} /><Route path="login" element={<AuthPage />} /><Route path="register" element={<AuthPage register />} /><Route path="forgot-password" element={<PasswordPage />} /><Route path="reset-password" element={<PasswordPage reset />} /><Route element={<ProtectedRoute />}><Route path="wishlist" element={<WishlistPage />} /><Route path="account" element={<AccountPage />} /><Route path="account/addresses" element={<AddressesPage />} /><Route path="account/orders" element={<OrdersPage />} /><Route path="account/orders/:id" element={<OrderDetailPage />} /></Route><Route element={<ProtectedRoute admin />}><Route path="admin" element={<AdminDashboardPage />} /></Route><Route path="*" element={<NotFoundPage />} /></Route></Routes></CartProvider></AuthProvider></BrowserRouter>
}

export default App
