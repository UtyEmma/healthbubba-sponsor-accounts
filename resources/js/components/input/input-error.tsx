import React from 'react'

export default function InputError({error, className, ...props}: React.PropsWithChildren<React.HTMLAttributes<HTMLParagraphElement> & { error?: string }>) {
    return (
        <>
            {error && error.length > 0 && <p {...props} className={`text-red-600 !text-sm ${className}`}>{error}</p> }
        </>
    )
}
