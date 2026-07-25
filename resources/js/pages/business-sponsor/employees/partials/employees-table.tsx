import { EllipsisIcon } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type EmployeeStatus = 'Active' | 'Pending' | 'Suspended';

export type Employee = {
    id: number;
    name: string;
    role: string;
    employeeId: string;
    department: string;
    status: EmployeeStatus;
    seatUsage: string;
    avatar: string;
};

const statusVariants = {
    Active: 'success',
    Pending: 'warning',
    Suspended: 'destructive',
} as const;

export function EmployeesTable({
    employees,
    onAction,
}: {
    employees: Employee[];
    onAction: (message: string) => void;
}) {
    return (
        <Card className="mt-4 overflow-hidden">
            <CardHeader className="h-14 justify-center border-b px-6 py-0">
                <CardTitle className="text-base leading-6 font-semibold">
                    All Employees ({employees.length})
                </CardTitle>
            </CardHeader>
            <CardContent className="overflow-x-auto p-0">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow className="hover:bg-muted/40">
                            <TableHead className="w-[18%] pl-8">
                                Beneficiary
                            </TableHead>
                            <TableHead className="w-[20%]">ID</TableHead>
                            <TableHead className="w-[24%]">
                                Department
                            </TableHead>
                            <TableHead className="w-[13%]">Status</TableHead>
                            <TableHead className="w-[20%]">
                                Seat usage
                            </TableHead>
                            <TableHead className="pr-8 text-right">
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {employees.map((employee) => (
                            <TableRow key={employee.id}>
                                <TableCell className="h-[69px] pl-8">
                                    <div className="flex items-center gap-2">
                                        <Avatar size="lg">
                                            <AvatarImage
                                                src={employee.avatar}
                                                alt=""
                                            />
                                            <AvatarFallback>DS</AvatarFallback>
                                        </Avatar>
                                        <div className="grid leading-5">
                                            <span className="font-medium text-foreground">
                                                {employee.name}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {employee.role}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell className="h-[69px] text-muted-foreground">
                                    {employee.employeeId}
                                </TableCell>
                                <TableCell className="h-[69px] text-muted-foreground">
                                    {employee.department}
                                </TableCell>
                                <TableCell className="h-[69px]">
                                    <Badge
                                        variant={
                                            statusVariants[employee.status]
                                        }
                                    >
                                        {employee.status}
                                    </Badge>
                                </TableCell>
                                <TableCell className="h-[69px] text-muted-foreground">
                                    {employee.seatUsage}
                                </TableCell>
                                <TableCell className="h-[69px] pr-8 text-right">
                                    <EmployeeActions
                                        employee={employee}
                                        onAction={onAction}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}

function EmployeeActions({
    employee,
    onAction,
}: {
    employee: Employee;
    onAction: (message: string) => void;
}) {
    const isSuspended = employee.status === 'Suspended';

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                render={
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label={`Actions for ${employee.name}`}
                    />
                }
            >
                <EllipsisIcon className="size-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                <DropdownMenuItem
                    onClick={() => onAction(`Viewing ${employee.name}`)}
                >
                    View employee
                </DropdownMenuItem>
                <DropdownMenuItem
                    variant={isSuspended ? 'default' : 'destructive'}
                    onClick={() =>
                        onAction(
                            `${employee.name} selected to ${
                                isSuspended ? 'reactivate' : 'suspend'
                            }`,
                        )
                    }
                >
                    {isSuspended ? 'Reactivate employee' : 'Suspend employee'}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
