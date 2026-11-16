import { Component, type ErrorInfo, type ReactNode } from 'react'
import { BlockUnavailable } from './BlockUnavailable'

type Props = { title: string; children: ReactNode }
type State = { hasCrashed: boolean }

/**
 * Catches an error thrown while a block renders (for example data in an unexpected shape), so one
 * broken block shows its fallback instead of blanking the whole page. A failed request does not throw
 * during rendering, so that case is handled separately, through the query's isError.
 * React has no hook for this yet; error boundaries must be class components.
 */
export class BlockErrorBoundary extends Component<Props, State> {
  state: State = { hasCrashed: false }

  static getDerivedStateFromError(): State {
    return { hasCrashed: true }
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error(`Homepage block "${this.props.title}" failed to render`, error, info.componentStack)
  }

  render() {
    return this.state.hasCrashed ? <BlockUnavailable title={this.props.title} /> : this.props.children
  }
}
