import { useMemo } from 'react'
import { Disclose } from '../toggle/disclose'
import { cn } from '@/lib/utils'
import { Avatar, AvatarBadge, AvatarFallback, AvatarImage } from '../ui/avatar'

interface AvatarProps {
    indicator?: boolean,
    image?: string | null
    title?: string,
    className?: string
    size?: "default" | "sm" | "lg" | undefined
}

export default function ({indicator = false, size = 'default', image, title, className }: AvatarProps) {

    const defaultImage = useMemo(() => {
        return image
    }, [image])

    return (
        <Avatar size={size} >
            {image && <AvatarImage src={image} /> }
            <AvatarFallback>
                {title?.split(' ').map(item => item.charAt(0)).slice(0, 2).join('')}
            </AvatarFallback>
            {indicator && <AvatarBadge className="bg-green-600 dark:bg-green-800" />}
        </Avatar>
    )
}
