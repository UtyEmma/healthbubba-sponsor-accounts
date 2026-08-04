import React, { type ComponentProps, type ElementType, type HTMLAttributes, type JSX, type PropsWithChildren } from 'react'

interface IDiscloseProps extends PropsWithChildren {
    show: boolean
    as?: ElementType
    fallback?: JSX.Element
}

export const Disclose = React.forwardRef(({show, children, as: Element, fallback: Fallback, ...props} : IDiscloseProps & HTMLAttributes<ElementType> & ComponentProps<ElementType>) => {
    return (
        show 
        
        ?

        <>
            {
                Element

                ?

                <Element {...props}>
                    {children}
                </Element>

                :

                children 
            }
        </>

        :

        Fallback ?? null
    )
})