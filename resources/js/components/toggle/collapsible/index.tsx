import { CollapsibleContext } from './context'
import useToggle from '@/hooks/useToggle'

export default function Collapsible() {

    const toggle = useToggle()

    return (
        <CollapsibleContext.Provider value={toggle} >
            {<div>Collapsible</div>}
        </CollapsibleContext.Provider>
    )
}
