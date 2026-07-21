import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

export type GettingStartedItem = {
    title: string;
    description: string;
    icon: string;
};

export function GettingStartedStep({
    item,
    number,
    featured = false,
}: {
    item: GettingStartedItem;
    number: number;
    featured?: boolean;
}) {
    return (
        <Card className="min-h-[104px]">
            <CardContent className="flex items-start gap-3 p-5">
                <span
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                        featured ? 'bg-success' : 'bg-muted',
                    )}
                >
                    <img
                        src={`/images/sponsor/${item.icon}`}
                        alt=""
                        className="size-5"
                    />
                </span>
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium text-muted-foreground">
                            {number}.
                        </span>
                        <h3 className="text-base leading-6 font-medium">
                            {item.title}
                        </h3>
                    </div>
                    <p className="text-sm leading-5 text-muted-foreground">
                        {item.description}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

export function SponsorBenefitCard({ item }: { item: GettingStartedItem }) {
    return (
        <Card className="min-h-[86px]">
            <CardContent className="flex items-start gap-3 p-5">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-success-muted">
                    <img
                        src={`/images/sponsor/${item.icon}`}
                        alt=""
                        className="size-5"
                    />
                </span>
                <div className="min-w-0">
                    <h3 className="text-base leading-6 font-medium">
                        {item.title}
                    </h3>
                    <p className="text-sm leading-5 text-muted-foreground">
                        {item.description}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
