import { createBrowserRouter } from 'react-router'
import { RequireAuth } from './auth/RequireAuth'
import { Layout } from './components/layout/Layout'
import { AdminPage } from './pages/AdminPage'
import { AuthPage } from './pages/AuthPages'
import { CartPage } from './pages/CartPage'
import { CheckoutPage } from './pages/CheckoutPage'
import { OrderPage, OrdersPage } from './pages/OrderPages'
import { HomePage } from './pages/HomePage'
import { NotFoundPage } from './pages/NotFoundPage'
import { ProductPage } from './pages/ProductPage'
import { StorePage } from './pages/StorePage'

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'store', element: <StorePage mode="store" /> },
      { path: 'search', element: <StorePage mode="search" /> },
      { path: 'product/:slug', element: <ProductPage /> },
      { path: 'login', element: <AuthPage mode="login" /> },
      { path: 'register', element: <AuthPage mode="register" /> },
      { path: 'cart', element: <CartPage /> },
      { path: 'checkout', element: <RequireAuth><CheckoutPage /></RequireAuth> },
      { path: 'orders', element: <RequireAuth><OrdersPage /></RequireAuth> },
      { path: 'orders/:id', element: <RequireAuth><OrderPage /></RequireAuth> },
      { path: 'admin', element: <RequireAuth><AdminPage /></RequireAuth> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
