import { createBrowserRouter } from 'react-router'
import { Layout } from './components/layout/Layout'
import { AuthPage } from './pages/AuthPages'
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
      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
