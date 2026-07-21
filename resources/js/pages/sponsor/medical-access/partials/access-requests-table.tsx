import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type AccessRequestStatus = 'Active' | 'Pending' | 'Expired';

export type AccessRequest = {
    id: number;
    beneficiary: string;
    dataType: string;
    requested: string;
    expires: string;
    status: AccessRequestStatus;
};

const statusVariants = {
    Active: 'success',
    Pending: 'warning',
    Expired: 'destructive',
} as const;

export function AccessRequestsTable({
    requests,
    onView,
}: {
    requests: AccessRequest[];
    onView: (request: AccessRequest) => void;
}) {
    return (
        <Card>
            <CardHeader className="gap-1 px-6 py-4">
                <CardTitle className="text-base leading-6 font-semibold">
                    Access requests
                </CardTitle>
                <CardDescription className="text-sm leading-5">
                    Beneficiaries approve or deny from their Patient app.
                    Unanswered requests expire after 30 days.
                </CardDescription>
            </CardHeader>
            <CardContent className="overflow-x-auto p-0">
                <Table className="min-w-[900px]">
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead className="w-[15%] pl-8">
                                Beneficiaries
                            </TableHead>
                            <TableHead className="w-[25%]">Data Type</TableHead>
                            <TableHead className="w-[15%]">Requested</TableHead>
                            <TableHead className="w-[15%]">Expires</TableHead>
                            <TableHead className="w-[15%]">Status</TableHead>
                            <TableHead className="pr-8 text-right">
                                Action
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {requests.map((request) => (
                            <TableRow key={request.id}>
                                <TableCell className="h-[61px] pl-8 font-medium">
                                    {request.beneficiary}
                                </TableCell>
                                <TableCell className="h-[61px] text-muted-foreground">
                                    {request.dataType}
                                </TableCell>
                                <TableCell className="h-[61px] text-muted-foreground">
                                    {request.requested}
                                </TableCell>
                                <TableCell className="h-[61px] text-muted-foreground">
                                    {request.expires}
                                </TableCell>
                                <TableCell className="h-[61px]">
                                    <Badge
                                        variant={statusVariants[request.status]}
                                    >
                                        {request.status}
                                    </Badge>
                                </TableCell>
                                <TableCell className="h-[61px] pr-8 text-right">
                                    {request.status === 'Active' && (
                                        <Button
                                            variant="outline"
                                            size="compact"
                                            onClick={() => onView(request)}
                                            aria-label={`View ${request.beneficiary}'s ${request.dataType}`}
                                        >
                                            View
                                        </Button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
