import { AccountType } from "./billing"
import { Wallet } from "./wallet"

export interface Workspace {
    id: number
    name: string
    logo?: string
    description?: string
    type: AccountType
    wallet: Wallet
}