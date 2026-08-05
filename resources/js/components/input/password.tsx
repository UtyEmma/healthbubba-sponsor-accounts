import { useState, type ComponentProps } from 'react'
import { InputGroup, InputGroupAddon, InputGroupInput } from '../ui/input-group'
import { Disclose } from '../toggle/disclose'
import { EyeIcon, EyeOffIcon } from 'lucide-react'

export default function InputPassword(props: ComponentProps<'input'>) {

    const [type, setType] = useState<'password'|'text'>('password')
    
    return (
        <InputGroup className='overflow-hidden'>
            <InputGroupInput {...props} type={type} />
            <InputGroupAddon  align="inline-end">
                <button type='button' onClick={() => setType(type == 'password' ? 'text' : 'password')} >
                    <Disclose show={type == 'text'}> 
                        <EyeOffIcon />
                    </Disclose>
                    <Disclose show={type == 'password'}> 
                        <EyeIcon />
                    </Disclose>
                </button>
            </InputGroupAddon>
        </InputGroup>
    )
}
