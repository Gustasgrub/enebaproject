import { createBrowserRouter, Navigate } from 'react-router'
import { Layout } from './components/layout/Layout'
import { NotFoundPage } from './pages/NotFoundPage'
import { StorePage } from './pages/StorePage'

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { index: true, element: <Navigate to="/store" replace /> },
      { path: 'store', element: <StorePage mode="store" /> },
      { path: 'search', element: <StorePage mode="search" /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
