import { useEffect, useState } from "react"

type OnChangeType = ((state: boolean) => void) | null

export const ToggleDefaults = {
    state: false,
    open: () => {},
    close: () => {},
    toggle: () => {}
}

export interface IToggle {
    state: boolean
    open: () => void
    close: () => void
    toggle: () => void
}

export default function useToggle (defaultState = false, onChange: OnChangeType = null) {
    const [state, setState] = useState(defaultState)

    useEffect(() => {
        if(onChange) onChange(state)
    }, [state])

    const open = () => setState(true)
    const close = () => setState(false)

    const toggle = () => setState(!state)

    return {open, close, state, toggle}
}